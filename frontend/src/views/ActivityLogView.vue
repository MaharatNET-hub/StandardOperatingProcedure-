<script setup>
import { ref, onMounted, watch } from 'vue'
import api from '../lib/api'

const logs = ref([])
const loading = ref(true)
const page = ref(1)
const lastPage = ref(1)
const total = ref(0)
const projectFilter = ref('')
const actionFilter = ref('')
const projects = ref([])

const actionLabels = {
  project_created: 'إنشاء مشروع',
  project_updated: 'تحديث مشروع',
  project_deleted: 'حذف مشروع',
  phase_status_changed: 'تغيير حالة مرحلة',
  checklist_item_updated: 'تحديث بند تحقق',
  evidence_added: 'إضافة دليل',
  plugin_request_created: 'طلب إضافة جديد',
  plugin_request_decided: 'قرار بخصوص إضافة',
  license_added: 'إضافة ترخيص',
  performance_report_added: 'تقرير أداء',
  qa_review_started: 'بدء مراجعة جودة',
  qa_review_submitted: 'إنهاء مراجعة جودة',
  project_final_signoff: 'اعتماد نهائي',
}

async function loadProjects() {
  const { data } = await api.get('/projects', { params: { per_page: 100 } })
  projects.value = data.data
}

async function load() {
  loading.value = true
  const params = { page: page.value }
  if (projectFilter.value) params.project_id = projectFilter.value
  if (actionFilter.value) params.action = actionFilter.value
  const { data } = await api.get('/activity-logs', { params })
  logs.value = data.data
  lastPage.value = data.last_page
  total.value = data.total
  loading.value = false
}

watch([projectFilter, actionFilter], () => {
  page.value = 1
  load()
})
watch(page, load)

onMounted(() => {
  loadProjects()
  load()
})
</script>

<template>
  <div class="p-6 md:p-8 max-w-5xl mx-auto">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-slate-900">سجل النشاطات</h1>
      <span class="text-sm text-slate-400">{{ total }} نشاط</span>
    </div>

    <div class="flex flex-wrap gap-3 mb-4">
      <select v-model="projectFilter" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
        <option value="">كل المشاريع</option>
        <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
      </select>
      <select v-model="actionFilter" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
        <option value="">كل الأنواع</option>
        <option v-for="(label, key) in actionLabels" :key="key" :value="key">{{ label }}</option>
      </select>
    </div>

    <div v-if="loading" class="text-slate-500">...جاري التحميل</div>

    <div v-else class="bg-white rounded-xl border border-slate-200 overflow-hidden">
      <ul class="divide-y divide-slate-100">
        <li v-for="log in logs" :key="log.id" class="p-4 text-sm">
          <div class="flex justify-between">
            <span class="font-medium text-slate-800">{{ actionLabels[log.action] || log.action }}</span>
            <span class="text-xs text-slate-400">{{ new Date(log.created_at).toLocaleString('ar') }}</span>
          </div>
          <div class="text-xs text-slate-500 mt-1">
            {{ log.user?.name || 'النظام' }}
            <span v-if="log.project"> — {{ log.project.name }}</span>
            <span v-if="log.description"> — {{ log.description }}</span>
          </div>
        </li>
        <li v-if="!logs.length" class="p-8 text-center text-slate-400">لا توجد نشاطات مطابقة.</li>
      </ul>
    </div>

    <div v-if="lastPage > 1" class="flex justify-center gap-2 mt-4">
      <button
        v-for="p in lastPage"
        :key="p"
        class="w-8 h-8 rounded-lg text-sm"
        :class="p === page ? 'bg-indigo-600 text-white' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50'"
        @click="page = p"
      >
        {{ p }}
      </button>
    </div>
  </div>
</template>
