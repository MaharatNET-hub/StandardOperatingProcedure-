<script setup>
import { ref, onMounted } from 'vue'
import api from '../lib/api'

const stats = ref(null)
const loading = ref(true)

const statusLabels = {
  in_progress: 'قيد التنفيذ',
  in_review: 'قيد مراجعة الجودة',
  changes_requested: 'طلب تعديلات',
  approved: 'معتمد',
  delivered: 'تم التسليم',
}

const actionLabels = {
  project_created: 'إنشاء مشروع',
  project_updated: 'تحديث مشروع',
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

onMounted(async () => {
  const { data } = await api.get('/dashboard')
  stats.value = data
  loading.value = false
})
</script>

<template>
  <div class="p-6 md:p-8 max-w-7xl mx-auto">
    <h1 class="text-2xl font-bold text-slate-900 mb-6">لوحة التحكم</h1>

    <router-link
      :to="{ name: 'quotation-new' }"
      class="block bg-indigo-950 hover:bg-indigo-900 transition text-white rounded-xl p-5 mb-8"
    >
      <div class="flex items-center justify-between gap-4 flex-wrap">
        <div>
          <div class="text-xs text-indigo-300 mb-1 tracking-wide">قسم جديد</div>
          <h2 class="font-semibold text-lg">التحليل الفني وعروض الأسعار</h2>
          <p class="text-sm text-indigo-200 mt-1">
            افحص أي رابط موقع (محاكاة Screaming Frog / Wappalyzer) لاستخراج المنصة التقنية ونشاطه التجاري وبنيته التحتية،
            واحصل على توصية فنية وعرض سعر رسمي بأسلوب Maharat Net جاهز للتحميل كـ PDF.
          </p>
        </div>
        <span class="shrink-0 bg-white text-indigo-950 text-sm font-medium rounded-lg px-4 py-2">+ تحليل موقع جديد</span>
      </div>
    </router-link>

    <div v-if="loading" class="text-slate-500">...جاري التحميل</div>

    <template v-else-if="stats">
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-xl border border-slate-200 p-5">
          <div class="text-xs text-slate-500 mb-1">إجمالي المشاريع</div>
          <div class="text-2xl font-bold text-slate-900">{{ stats.projects_total }}</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5">
          <div class="text-xs text-slate-500 mb-1">متوسط Lighthouse (موبايل)</div>
          <div class="text-2xl font-bold" :class="stats.avg_lighthouse_mobile >= stats.lighthouse_target ? 'text-emerald-600' : 'text-amber-600'">
            {{ stats.avg_lighthouse_mobile ?? '—' }}
            <span class="text-xs font-normal text-slate-400">/ الهدف {{ stats.lighthouse_target }}+</span>
          </div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5">
          <div class="text-xs text-slate-500 mb-1">متوسط عدد الإضافات</div>
          <div class="text-2xl font-bold" :class="stats.avg_plugin_count <= stats.plugin_count_target ? 'text-emerald-600' : 'text-red-600'">
            {{ stats.avg_plugin_count ?? '—' }}
            <span class="text-xs font-normal text-slate-400">/ الحد {{ stats.plugin_count_target }}</span>
          </div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5">
          <div class="text-xs text-slate-500 mb-1">طلبات إضافات معلّقة</div>
          <div class="text-2xl font-bold text-slate-900">{{ stats.pending_plugin_requests }}</div>
        </div>
      </div>

      <div class="grid md:grid-cols-3 gap-6">
        <div class="md:col-span-1 bg-white rounded-xl border border-slate-200 p-5">
          <h2 class="font-semibold text-slate-900 mb-3">المشاريع حسب الحالة</h2>
          <ul class="space-y-2">
            <li
              v-for="(count, status) in stats.projects_by_status"
              :key="status"
              class="flex justify-between text-sm"
            >
              <span class="text-slate-600">{{ statusLabels[status] || status }}</span>
              <span class="font-semibold text-slate-900">{{ count }}</span>
            </li>
          </ul>
        </div>

        <div class="md:col-span-2 bg-white rounded-xl border border-slate-200 p-5">
          <div class="flex items-center justify-between mb-3">
            <h2 class="font-semibold text-slate-900">آخر النشاطات</h2>
            <router-link :to="{ name: 'activity-log' }" class="text-xs text-indigo-600 hover:underline">عرض السجل كاملاً</router-link>
          </div>
          <ul class="space-y-3 max-h-96 overflow-y-auto">
            <li v-for="log in stats.recent_activity" :key="log.id" class="text-sm border-b border-slate-100 pb-2 last:border-0">
              <div class="flex justify-between">
                <span class="font-medium text-slate-800">{{ actionLabels[log.action] || log.action }}</span>
                <span class="text-xs text-slate-400">{{ new Date(log.created_at).toLocaleString('ar') }}</span>
              </div>
              <div class="text-xs text-slate-500 mt-0.5">
                {{ log.user?.name }}
                <span v-if="log.project"> — {{ log.project.name }}</span>
                <span v-if="log.description"> — {{ log.description }}</span>
              </div>
            </li>
          </ul>
        </div>
      </div>
    </template>
  </div>
</template>
