<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '../lib/api'
import { useAuthStore } from '../stores/auth'
import {
  noteTypeLabels as typeLabels,
  noteTypeColors as typeColors,
  noteStatusLabels as statusLabels,
  noteStatusColors as statusColors,
  formatDateTime,
} from '../lib/labels'

const props = defineProps({ project: { type: Object, required: true } })
const emit = defineEmits(['reload'])

const auth = useAuthStore()
const notes = ref([])
const loading = ref(true)
const saving = ref(false)
const error = ref('')
const filterType = ref('')

const form = ref({ title: '', type: 'internal_note', body: '', status: 'new' })


const visibleNotes = computed(() =>
  filterType.value ? notes.value.filter((n) => n.type === filterType.value) : notes.value,
)

async function load() {
  loading.value = true
  const { data } = await api.get(`/projects/${props.project.id}/notes`)
  notes.value = data.data
  loading.value = false
}

async function addNote() {
  if (!form.value.title.trim()) {
    error.value = 'عنوان الملاحظة مطلوب.'
    return
  }
  saving.value = true
  error.value = ''
  try {
    await api.post(`/projects/${props.project.id}/notes`, form.value)
    form.value = { title: '', type: 'internal_note', body: '', status: 'new' }
    await load()
    // يحدّث عدّاد الملاحظات على التبويب وحالة ملاحظات العميل على المشروع
    emit('reload')
  } catch (e) {
    error.value = e.response?.data?.message || 'تعذر حفظ الملاحظة.'
  } finally {
    saving.value = false
  }
}

async function setStatus(note, status) {
  await api.patch(`/project-notes/${note.id}`, { status })
  note.status = status
}

async function remove(note) {
  if (!confirm('حذف هذه الملاحظة نهائياً؟')) return
  await api.delete(`/project-notes/${note.id}`)
  notes.value = notes.value.filter((n) => n.id !== note.id)
  emit('reload')
}

onMounted(load)
</script>

<template>
  <div class="space-y-6">
    <div class="bg-white rounded-xl border border-slate-200 p-5">
      <h3 class="font-semibold text-slate-900 mb-1">إضافة ملاحظة جديدة</h3>
      <p class="text-xs text-slate-500 mb-4">
        كل ملاحظة تُحفظ كسجل مستقل بتاريخها وصاحبها — الملاحظات القديمة لا تُستبدل أبداً.
      </p>

      <div class="grid md:grid-cols-3 gap-3">
        <input
          v-model="form.title"
          type="text"
          placeholder="عنوان الملاحظة"
          class="md:col-span-2 border border-slate-300 rounded-lg px-3 py-2 text-sm"
        />
        <select v-model="form.type" class="border border-slate-300 rounded-lg px-3 py-2 text-sm">
          <option v-for="(label, key) in typeLabels" :key="key" :value="key">{{ label }}</option>
        </select>
      </div>

      <textarea
        v-model="form.body"
        rows="3"
        placeholder="تفاصيل الملاحظة أو القرار..."
        class="mt-3 w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"
      ></textarea>

      <div class="flex items-center gap-3 mt-3">
        <select v-model="form.status" class="border border-slate-300 rounded-lg px-3 py-2 text-sm">
          <option v-for="(label, key) in statusLabels" :key="key" :value="key">{{ label }}</option>
        </select>
        <button
          :disabled="saving"
          class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm rounded-lg px-4 py-2 disabled:opacity-60"
          @click="addNote"
        >
          {{ saving ? '...جاري الحفظ' : 'إضافة الملاحظة' }}
        </button>
        <span v-if="error" class="text-sm text-red-600">{{ error }}</span>
      </div>
    </div>

    <div class="flex items-center gap-2 flex-wrap">
      <button
        class="px-3 py-1.5 rounded-full text-xs font-medium border"
        :class="filterType === '' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-slate-600 border-slate-300'"
        @click="filterType = ''"
      >
        الكل ({{ notes.length }})
      </button>
      <button
        v-for="(label, key) in typeLabels"
        :key="key"
        class="px-3 py-1.5 rounded-full text-xs font-medium border"
        :class="filterType === key ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-slate-600 border-slate-300'"
        @click="filterType = key"
      >
        {{ label }}
      </button>
    </div>

    <div v-if="loading" class="text-slate-500">...جاري التحميل</div>

    <div v-else-if="!visibleNotes.length" class="text-center text-slate-400 py-10 bg-white rounded-xl border border-slate-200">
      لا توجد ملاحظات مسجّلة بعد.
    </div>

    <ol v-else class="relative border-s-2 border-slate-200 ms-3 space-y-4">
      <li v-for="note in visibleNotes" :key="note.id" class="ms-5">
        <span class="absolute -start-[7px] mt-2 w-3 h-3 rounded-full bg-indigo-500"></span>
        <div class="bg-white rounded-xl border border-slate-200 p-4">
          <div class="flex items-start justify-between gap-3 flex-wrap">
            <div>
              <span class="font-semibold text-slate-900">{{ note.title }}</span>
              <span class="px-2 py-0.5 rounded-full text-xs font-medium ms-2" :class="typeColors[note.type]">
                {{ typeLabels[note.type] || note.type }}
              </span>
            </div>
            <span class="px-2 py-0.5 rounded-full text-xs font-medium" :class="statusColors[note.status]">
              {{ statusLabels[note.status] }}
            </span>
          </div>

          <p v-if="note.body" class="text-sm text-slate-600 mt-2 whitespace-pre-line">{{ note.body }}</p>

          <div class="flex items-center justify-between gap-3 flex-wrap mt-3 text-xs text-slate-400">
            <div>{{ note.author?.name || 'غير معروف' }} · {{ formatDateTime(note.created_at) }}</div>
            <div class="flex items-center gap-2">
              <button
                v-if="note.status !== 'in_progress'"
                class="hover:text-indigo-600"
                @click="setStatus(note, 'in_progress')"
              >
                قيد التنفيذ
              </button>
              <button v-if="note.status !== 'done'" class="hover:text-emerald-600" @click="setStatus(note, 'done')">
                تعليم كمنجزة
              </button>
              <button v-if="auth.canManageProjects" class="hover:text-red-600" @click="remove(note)">حذف</button>
            </div>
          </div>
        </div>
      </li>
    </ol>
  </div>
</template>
