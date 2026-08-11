<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '../lib/api'
import { useAuthStore } from '../stores/auth'
import PhasesTab from '../components/PhasesTab.vue'
import ChecklistTab from '../components/ChecklistTab.vue'
import PluginsTab from '../components/PluginsTab.vue'
import LicensesTab from '../components/LicensesTab.vue'
import PerformanceTab from '../components/PerformanceTab.vue'
import QaReviewTab from '../components/QaReviewTab.vue'
import SeoAuditTab from '../components/SeoAuditTab.vue'
import ProjectDetailsTab from '../components/ProjectDetailsTab.vue'
import ClientCommunicationTab from '../components/ClientCommunicationTab.vue'
import ReadinessTab from '../components/ReadinessTab.vue'

const props = defineProps({ id: [String, Number] })

const auth = useAuthStore()
const project = ref(null)
const loading = ref(true)
const tab = ref('checklist')
const exporting = ref(false)

const statusLabels = {
  in_progress: 'قيد التنفيذ',
  in_review: 'قيد مراجعة الجودة',
  changes_requested: 'طلب تعديلات',
  approved: 'معتمد',
  delivered: 'تم التسليم',
}
const statusColors = {
  in_progress: 'bg-slate-100 text-slate-700',
  in_review: 'bg-amber-100 text-amber-700',
  changes_requested: 'bg-red-100 text-red-700',
  approved: 'bg-emerald-100 text-emerald-700',
  delivered: 'bg-indigo-100 text-indigo-700',
}

const priorityLabels = { critical: 'حرجة', high: 'عالية', medium: 'متوسطة', low: 'منخفضة' }
const priorityColors = {
  critical: 'bg-red-100 text-red-700',
  high: 'bg-orange-100 text-orange-700',
  medium: 'bg-amber-100 text-amber-700',
  low: 'bg-slate-100 text-slate-600',
}

const tabs = computed(() => [
  { key: 'readiness', label: 'الجاهزية والمتطلبات' },
  { key: 'communication', label: 'التواصل مع العميل' },
  { key: 'details', label: 'تفاصيل إضافية' },
  { key: 'phases', label: 'مراحل العمل' },
  { key: 'checklist', label: 'قائمة التحقق' },
  { key: 'plugins', label: 'حوكمة الإضافات' },
  { key: 'licenses', label: 'التراخيص' },
  { key: 'performance', label: 'تقارير الأداء' },
  { key: 'seo', label: 'فحص SEO (Screaming Frog)' },
  { key: 'qa', label: 'مراجعة الجودة والاعتماد' },
])

async function loadProject() {
  loading.value = true
  const { data } = await api.get(`/projects/${props.id}`)
  project.value = data
  loading.value = false
}

async function exportPdf() {
  exporting.value = true
  try {
    const response = await api.get(`/projects/${props.id}/report-pdf`, { responseType: 'blob' })
    const url = window.URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }))
    const link = document.createElement('a')
    link.href = url
    link.download = `تقرير-${project.value.name}.pdf`
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch (e) {
    alert('تعذر تصدير التقرير. حاول مرة أخرى.')
  } finally {
    exporting.value = false
  }
}

onMounted(loadProject)
</script>

<template>
  <div v-if="loading" class="p-8 text-slate-500">...جاري التحميل</div>

  <div v-else-if="project" class="max-w-6xl mx-auto p-6 md:p-8">
    <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">{{ project.name }}</h1>
        <p class="text-sm text-slate-500 mt-1">
          العميل: {{ project.client_name }}
          <a
            v-if="project.envato_preview_url"
            :href="project.envato_preview_url"
            target="_blank"
            class="text-indigo-600 hover:underline ms-2"
          >
            رابط Live Preview ↗
          </a>
        </p>
      </div>
      <div class="flex items-center gap-3">
        <button
          :disabled="exporting"
          class="text-sm border border-slate-300 hover:bg-slate-50 text-slate-700 rounded-lg px-3 py-1.5 disabled:opacity-60"
          @click="exportPdf"
        >
          {{ exporting ? '...جاري التصدير' : 'تصدير تقرير PDF' }}
        </button>
        <span class="px-3 py-1.5 rounded-full text-sm font-medium" :class="statusColors[project.status]">
          {{ statusLabels[project.status] || project.status }}
        </span>
        <span
          v-if="project.priority"
          class="px-3 py-1.5 rounded-full text-sm font-medium"
          :class="priorityColors[project.priority.level]"
        >
          أولوية {{ priorityLabels[project.priority.level] }} ({{ project.priority.score }})
        </span>
        <span v-if="project.priority?.blocked" class="px-3 py-1.5 rounded-full text-sm font-medium bg-slate-100 text-slate-500">
          متوقف
        </span>
      </div>
    </div>

    <div v-if="project.readiness" class="mb-6 bg-white rounded-xl border border-slate-200 p-4">
      <div class="flex items-center justify-between text-sm mb-2">
        <span class="font-medium text-slate-700">جاهزية المشروع</span>
        <span class="font-bold" :class="project.readiness.percent >= 80 ? 'text-emerald-600' : project.readiness.percent >= 40 ? 'text-amber-600' : 'text-red-600'">
          {{ project.readiness.percent }}%
        </span>
      </div>
      <div class="w-full bg-slate-100 rounded-full h-2">
        <div
          class="h-2 rounded-full"
          :class="project.readiness.percent >= 80 ? 'bg-emerald-500' : project.readiness.percent >= 40 ? 'bg-amber-500' : 'bg-red-500'"
          :style="{ width: project.readiness.percent + '%' }"
        ></div>
      </div>
    </div>

    <div class="border-b border-slate-200 mb-6 overflow-x-auto">
      <nav class="flex gap-1 min-w-max">
        <button
          v-for="t in tabs"
          :key="t.key"
          class="px-4 py-2.5 text-sm font-medium border-b-2 transition"
          :class="tab === t.key ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-800'"
          @click="tab = t.key"
        >
          {{ t.label }}
        </button>
      </nav>
    </div>

    <ReadinessTab v-if="tab === 'readiness'" :project="project" />
    <ClientCommunicationTab v-else-if="tab === 'communication'" :project="project" @reload="loadProject" />
    <ProjectDetailsTab v-else-if="tab === 'details'" :project="project" @reload="loadProject" />
    <PhasesTab v-else-if="tab === 'phases'" :project="project" @reload="loadProject" />
    <ChecklistTab v-else-if="tab === 'checklist'" :project="project" />
    <PluginsTab v-else-if="tab === 'plugins'" :project="project" />
    <LicensesTab v-else-if="tab === 'licenses'" :project="project" />
    <PerformanceTab v-else-if="tab === 'performance'" :project="project" />
    <SeoAuditTab v-else-if="tab === 'seo'" :project="project" />
    <QaReviewTab v-else-if="tab === 'qa'" :project="project" @reload="loadProject" />
  </div>
</template>
