<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import api from '../lib/api'
import {
  noteTypeLabels,
  noteTypeColors,
  noteStatusLabels,
  noteStatusColors,
  formatDateTime,
} from '../lib/labels'

// سجل الملاحظات (SRS §21): All Notes بترتيب زمني، و Notes by Project
// لمراجعة التاريخ الكامل لأي مشروع.
const grouping = ref('all')
const notes = ref([])
const projects = ref([])
const loading = ref(true)
const filters = ref({ project_id: '', type: '', status: '', search: '' })

let searchDebounce = null

const groupedByProject = computed(() => {
  const groups = new Map()
  for (const note of notes.value) {
    const key = note.project?.id ?? 0
    if (!groups.has(key)) {
      groups.set(key, { project: note.project, notes: [] })
    }
    groups.get(key).notes.push(note)
  }
  return [...groups.values()]
})

async function load() {
  loading.value = true
  const params = {}
  if (filters.value.project_id) params.project_id = filters.value.project_id
  if (filters.value.type) params.type = filters.value.type
  if (filters.value.status) params.status = filters.value.status
  if (filters.value.search) params.search = filters.value.search
  params.per_page = 100
  const { data } = await api.get('/project-notes', { params })
  notes.value = data.data
  loading.value = false
}

async function loadProjects() {
  const { data } = await api.get('/projects', { params: { per_page: 100 } })
  projects.value = data.data
}

watch(
  () => filters.value.search,
  () => {
    clearTimeout(searchDebounce)
    searchDebounce = setTimeout(load, 300)
  },
)
watch([() => filters.value.project_id, () => filters.value.type, () => filters.value.status], load)

onMounted(() => {
  loadProjects()
  load()
})
</script>

<template>
  <div class="p-6 md:p-8 max-w-5xl mx-auto">
    <h1 class="text-2xl font-bold text-slate-900 mb-1">سجل ملاحظات المشاريع</h1>
    <p class="text-sm text-slate-500 mb-5">
      سجل تاريخي كامل لكل ملاحظة وقرار واجتماع — ماذا حدث، متى، من أضافه، ولأي مشروع.
    </p>

    <div class="bg-white rounded-xl border border-slate-200 p-4 mb-5">
      <div class="grid md:grid-cols-4 gap-3">
        <input
          v-model="filters.search"
          type="text"
          placeholder="بحث في العناوين والمحتوى..."
          class="border border-slate-300 rounded-lg px-3 py-2 text-sm"
        />
        <select v-model="filters.project_id" class="border border-slate-300 rounded-lg px-3 py-2 text-sm">
          <option value="">كل المشاريع</option>
          <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
        </select>
        <select v-model="filters.type" class="border border-slate-300 rounded-lg px-3 py-2 text-sm">
          <option value="">كل الأنواع</option>
          <option v-for="(label, key) in noteTypeLabels" :key="key" :value="key">{{ label }}</option>
        </select>
        <select v-model="filters.status" class="border border-slate-300 rounded-lg px-3 py-2 text-sm">
          <option value="">كل الحالات</option>
          <option v-for="(label, key) in noteStatusLabels" :key="key" :value="key">{{ label }}</option>
        </select>
      </div>

      <div class="flex gap-2 mt-3">
        <button
          class="px-3 py-1.5 rounded-lg text-xs font-medium border"
          :class="grouping === 'all' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-slate-600 border-slate-300'"
          @click="grouping = 'all'"
        >
          All Notes — ترتيب زمني
        </button>
        <button
          class="px-3 py-1.5 rounded-lg text-xs font-medium border"
          :class="grouping === 'project' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-slate-600 border-slate-300'"
          @click="grouping = 'project'"
        >
          Notes by Project — حسب المشروع
        </button>
      </div>
    </div>

    <div v-if="loading" class="text-slate-500">...جاري التحميل</div>

    <div v-else-if="!notes.length" class="text-center text-slate-400 py-12 bg-white rounded-xl border border-slate-200">
      لا توجد ملاحظات مطابقة.
    </div>

    <!-- All Notes -->
    <div v-else-if="grouping === 'all'" class="space-y-3">
      <article v-for="note in notes" :key="note.id" class="bg-white rounded-xl border border-slate-200 p-4">
        <div class="flex items-start justify-between gap-3 flex-wrap">
          <div>
            <span class="font-semibold text-slate-900">{{ note.title }}</span>
            <span class="px-2 py-0.5 rounded-full text-xs font-medium ms-2" :class="noteTypeColors[note.type]">
              {{ noteTypeLabels[note.type] || note.type }}
            </span>
          </div>
          <span class="px-2 py-0.5 rounded-full text-xs font-medium" :class="noteStatusColors[note.status]">
            {{ noteStatusLabels[note.status] }}
          </span>
        </div>

        <p v-if="note.body" class="text-sm text-slate-600 mt-2 whitespace-pre-line">{{ note.body }}</p>

        <div class="flex items-center gap-2 flex-wrap mt-3 text-xs text-slate-400">
          <router-link
            v-if="note.project"
            :to="{ name: 'project-detail', params: { id: note.project.id } }"
            class="text-indigo-600 hover:underline"
          >
            {{ note.project.name }}
          </router-link>
          <span>· {{ note.author?.name || 'غير معروف' }}</span>
          <span>· {{ formatDateTime(note.created_at) }}</span>
        </div>
      </article>
    </div>

    <!-- Notes by Project -->
    <div v-else class="space-y-5">
      <section v-for="group in groupedByProject" :key="group.project?.id || 0" class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <header class="px-5 py-3 bg-slate-50 border-b border-slate-200 flex items-center justify-between gap-3 flex-wrap">
          <router-link
            v-if="group.project"
            :to="{ name: 'project-detail', params: { id: group.project.id } }"
            class="font-semibold text-slate-900 hover:text-indigo-600"
          >
            {{ group.project.name }}
            <span class="text-xs text-slate-500 ms-2">{{ group.project.client_name }}</span>
          </router-link>
          <span v-else class="font-semibold text-slate-500">مشروع محذوف</span>
          <span class="text-xs text-slate-500">{{ group.notes.length }} ملاحظة</span>
        </header>

        <ol class="divide-y divide-slate-100">
          <li v-for="note in group.notes" :key="note.id" class="px-5 py-3">
            <div class="flex items-start justify-between gap-3 flex-wrap">
              <div>
                <span class="font-medium text-slate-900">{{ note.title }}</span>
                <span class="px-2 py-0.5 rounded-full text-xs font-medium ms-2" :class="noteTypeColors[note.type]">
                  {{ noteTypeLabels[note.type] || note.type }}
                </span>
              </div>
              <span class="text-xs text-slate-400">{{ formatDateTime(note.created_at) }}</span>
            </div>
            <p v-if="note.body" class="text-sm text-slate-600 mt-1 whitespace-pre-line">{{ note.body }}</p>
            <div class="text-xs text-slate-400 mt-1">{{ note.author?.name || 'غير معروف' }}</div>
          </li>
        </ol>
      </section>
    </div>
  </div>
</template>
