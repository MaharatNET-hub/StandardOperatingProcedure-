<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '../lib/api'
import ChecklistItemRow from './ChecklistItemRow.vue'

const props = defineProps({ project: Object })

const categories = ref([])
const loading = ref(true)
const openCategory = ref(null)

const groupLabels = {
  quality: 'محاكاة التصميم بكود نظيف',
  seo: 'الوقاية من مشاكل SEO',
  media: 'الصور، الترجمة، والـ Responsive',
  launch: 'قائمة الإطلاق',
}

const overallProgress = computed(() => {
  const total = categories.value.reduce((sum, c) => sum + c.progress.total, 0)
  const done = categories.value.reduce((sum, c) => sum + c.progress.done, 0)
  return { total, done, percent: total ? Math.round((done / total) * 100) : 0 }
})

const grouped = computed(() => {
  const byGroup = {}
  for (const cat of categories.value) {
    byGroup[cat.group] = byGroup[cat.group] || []
    byGroup[cat.group].push(cat)
  }
  return byGroup
})

async function load() {
  loading.value = true
  const { data } = await api.get(`/projects/${props.project.id}/checklist`)
  categories.value = data
  loading.value = false
  if (!openCategory.value && data.length) openCategory.value = data[0].code
}

function onItemUpdated(category, updated) {
  const idx = category.items.findIndex((i) => i.id === updated.id)
  if (idx !== -1) category.items[idx] = updated
  category.progress.done = category.items.filter((i) => i.status === 'done').length
}

onMounted(load)
</script>

<template>
  <div v-if="loading" class="text-slate-500">...جاري التحميل</div>

  <div v-else>
    <div class="bg-white rounded-xl border border-slate-200 p-5 mb-6">
      <div class="flex items-center justify-between mb-2">
        <h2 class="font-semibold text-slate-900">نسبة إنجاز قائمة التحقق</h2>
        <span class="text-sm font-medium text-slate-600">{{ overallProgress.done }} / {{ overallProgress.total }}</span>
      </div>
      <div class="w-full h-2.5 bg-slate-100 rounded-full overflow-hidden">
        <div
          class="h-full bg-indigo-600 transition-all"
          :style="{ width: overallProgress.percent + '%' }"
        ></div>
      </div>
    </div>

    <div v-for="(cats, group) in grouped" :key="group" class="mb-6">
      <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wide mb-3">{{ groupLabels[group] || group }}</h2>

      <div class="space-y-3">
        <div v-for="cat in cats" :key="cat.code" class="bg-white rounded-xl border border-slate-200 overflow-hidden">
          <button
            class="w-full flex items-center justify-between p-4 hover:bg-slate-50 transition"
            @click="openCategory = openCategory === cat.code ? null : cat.code"
          >
            <div class="text-right">
              <span class="text-xs font-mono text-slate-400 me-2">{{ cat.code }}</span>
              <span class="font-medium text-slate-900">{{ cat.name_ar }}</span>
            </div>
            <div class="flex items-center gap-3 shrink-0">
              <span
                class="text-xs font-medium px-2 py-0.5 rounded-full"
                :class="cat.progress.done === cat.progress.total ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600'"
              >
                {{ cat.progress.done }} / {{ cat.progress.total }}
              </span>
              <span class="text-slate-400">{{ openCategory === cat.code ? '−' : '+' }}</span>
            </div>
          </button>

          <div v-if="openCategory === cat.code" class="border-t border-slate-100 p-4 space-y-2">
            <p v-if="cat.rule_note" class="text-xs bg-amber-50 text-amber-800 rounded-lg p-2.5 mb-2">
              {{ cat.rule_note }}
            </p>
            <ChecklistItemRow
              v-for="item in cat.items"
              :key="item.id"
              :item="item"
              @updated="(u) => onItemUpdated(cat, u)"
            />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
