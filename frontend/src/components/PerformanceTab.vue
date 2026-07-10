<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '../lib/api'
import { useAuthStore } from '../stores/auth'

const props = defineProps({ project: Object })
const auth = useAuthStore()
const router = useRouter()

const reports = ref([])
const loading = ref(true)
const showCreate = ref(false)
const creating = ref(false)
const today = new Date().toISOString().slice(0, 10)

const form = ref({
  stage: 'original_template',
  lighthouse_mobile: '',
  lighthouse_desktop: '',
  pagespeed_url: '',
  screaming_frog_report_url: '',
  plugin_count: '',
  notes: '',
  measured_at: today,
})

const runUrl = ref(props.project.site_url || '')
const runStage = ref('final_site')
const running = ref(false)
const runError = ref('')
const openIssues = ref({})

const stageLabels = { original_template: 'القالب الأصلي (Envato Demo)', final_site: 'الموقع النهائي' }

const originalReport = computed(() => reports.value.find((r) => r.stage === 'original_template'))
const finalReport = computed(() => [...reports.value].reverse().find((r) => r.stage === 'final_site'))

async function load() {
  loading.value = true
  const { data } = await api.get(`/projects/${props.project.id}/performance-reports`)
  reports.value = data
  loading.value = false
}

async function runPageSpeed() {
  runError.value = ''
  if (!runUrl.value) {
    runError.value = 'أدخل رابط الموقع أولاً.'
    return
  }
  running.value = true
  try {
    await api.post(`/projects/${props.project.id}/performance-reports/run-pagespeed`, {
      url: runUrl.value,
      stage: runStage.value,
    })
    await load()
  } catch (e) {
    runError.value = e.response?.data?.message || 'تعذر تشغيل الفحص.'
  } finally {
    running.value = false
  }
}

async function createReport() {
  creating.value = true
  try {
    await api.post(`/projects/${props.project.id}/performance-reports`, form.value)
    showCreate.value = false
    form.value = { stage: 'original_template', lighthouse_mobile: '', lighthouse_desktop: '', pagespeed_url: '', screaming_frog_report_url: '', plugin_count: '', notes: '', measured_at: today }
    await load()
  } finally {
    creating.value = false
  }
}

async function removeReport(report) {
  await api.delete(`/projects/${props.project.id}/performance-reports/${report.id}`)
  await load()
}

function toggleIssues(reportId, device) {
  const key = `${reportId}-${device}`
  openIssues.value[key] = !openIssues.value[key]
}

function scoreColor(score) {
  if (score === null || score === undefined) return 'text-slate-400'
  if (score >= 90) return 'text-emerald-600'
  if (score >= 50) return 'text-amber-600'
  return 'text-red-600'
}

onMounted(load)
</script>

<template>
  <div>
    <div v-if="originalReport && finalReport" class="grid md:grid-cols-2 gap-4 mb-6">
      <div class="bg-white rounded-xl border border-slate-200 p-5">
        <h3 class="text-sm font-semibold text-slate-500 mb-2">Lighthouse (موبايل)</h3>
        <div class="flex items-end gap-3">
          <span class="text-slate-400 text-lg">{{ originalReport.lighthouse_mobile ?? '—' }}</span>
          <span class="text-slate-300">→</span>
          <span class="text-2xl font-bold" :class="scoreColor(finalReport.lighthouse_mobile)">
            {{ finalReport.lighthouse_mobile ?? '—' }}
          </span>
          <span class="text-xs text-slate-400 mb-1">/ الهدف 85+</span>
        </div>
      </div>
      <div class="bg-white rounded-xl border border-slate-200 p-5">
        <h3 class="text-sm font-semibold text-slate-500 mb-2">عدد الإضافات</h3>
        <div class="flex items-end gap-3">
          <span class="text-slate-400 text-lg">{{ originalReport.plugin_count ?? '—' }}</span>
          <span class="text-slate-300">→</span>
          <span
            class="text-2xl font-bold"
            :class="(finalReport.plugin_count ?? 99) < 10 ? 'text-emerald-600' : 'text-red-600'"
          >
            {{ finalReport.plugin_count ?? '—' }}
          </span>
          <span class="text-xs text-slate-400 mb-1">/ الحد الأقصى 10</span>
        </div>
      </div>
    </div>

    <!-- Automated PageSpeed check -->
    <div class="bg-white rounded-xl border border-slate-200 p-5 mb-6">
      <h2 class="font-semibold text-slate-900 mb-1">فحص PageSpeed / Lighthouse تلقائي</h2>
      <p class="text-sm text-slate-500 mb-4">يجلب نتيجة السرعة الفعلية للموبايل والديسكتوب مباشرة من Google، مع تفاصيل المشاكل المكتشفة.</p>

      <div class="flex flex-col md:flex-row gap-3">
        <input
          v-model="runUrl"
          type="url"
          placeholder="https://example.com"
          class="flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm"
        />
        <select v-model="runStage" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
          <option value="original_template">القالب الأصلي (Envato Demo)</option>
          <option value="final_site">الموقع النهائي</option>
        </select>
        <button
          :disabled="running"
          class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg px-4 py-2 disabled:opacity-60 whitespace-nowrap"
          @click="runPageSpeed"
        >
          {{ running ? '...جاري الفحص (قد يستغرق دقيقة)' : 'تشغيل الفحص' }}
        </button>
      </div>

      <p v-if="runError" class="text-sm text-red-600 mt-3">
        {{ runError }}
        <button
          v-if="runError.includes('مفتاح') && auth.canManageSettings"
          class="text-indigo-600 hover:underline"
          @click="router.push({ name: 'settings' })"
        >
          الذهاب إلى الإعدادات
        </button>
      </p>
    </div>

    <div class="flex justify-end mb-4">
      <button class="text-sm text-indigo-600 hover:underline" @click="showCreate = true">
        + إدخال قياس يدوياً بدلاً من ذلك
      </button>
    </div>

    <div v-if="loading" class="text-slate-500">...جاري التحميل</div>

    <div v-else class="space-y-3">
      <div v-for="r in reports" :key="r.id" class="bg-white rounded-xl border border-slate-200 p-4">
        <div class="flex items-center justify-between mb-2">
          <span class="font-semibold text-slate-900">
            {{ stageLabels[r.stage] }}
            <span v-if="r.is_automated" class="text-xs font-normal text-indigo-500 bg-indigo-50 rounded-full px-2 py-0.5 ms-1">تلقائي</span>
          </span>
          <div class="flex items-center gap-3">
            <span class="text-xs text-slate-400">{{ new Date(r.measured_at).toLocaleDateString('ar') }}</span>
            <button v-if="auth.canManageProjects" class="text-xs text-slate-400 hover:text-red-500 hover:underline" @click="removeReport(r)">
              حذف
            </button>
          </div>
        </div>

        <div v-if="r.source_url" class="text-xs text-slate-400 mb-2 truncate">{{ r.source_url }}</div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm mb-2">
          <div>
            <span class="text-slate-400">Lighthouse موبايل:</span>
            <span class="font-semibold" :class="scoreColor(r.lighthouse_mobile)">{{ r.lighthouse_mobile ?? '—' }}</span>
          </div>
          <div>
            <span class="text-slate-400">Lighthouse ديسكتوب:</span>
            <span class="font-semibold" :class="scoreColor(r.lighthouse_desktop)">{{ r.lighthouse_desktop ?? '—' }}</span>
          </div>
          <div><span class="text-slate-400">عدد الإضافات:</span> {{ r.plugin_count ?? '—' }}</div>
          <div><span class="text-slate-400">قِيسَ بواسطة:</span> {{ r.measurer?.name }}</div>
        </div>

        <!-- Issues from automated checks -->
        <div v-if="r.issues" class="grid md:grid-cols-2 gap-3 mt-3">
          <div v-for="device in ['mobile', 'desktop']" :key="device">
            <button
              v-if="r.issues[device]?.length"
              class="text-xs font-medium text-amber-700 bg-amber-50 rounded-lg px-3 py-1.5 w-full text-right"
              @click="toggleIssues(r.id, device)"
            >
              {{ device === 'mobile' ? 'مشاكل الموبايل' : 'مشاكل الديسكتوب' }} ({{ r.issues[device].length }})
              {{ openIssues[`${r.id}-${device}`] ? '−' : '+' }}
            </button>
            <div v-if="openIssues[`${r.id}-${device}`]" class="mt-2 space-y-2">
              <div
                v-for="issue in r.issues[device]"
                :key="issue.id"
                class="text-xs border border-slate-100 rounded-lg p-2.5"
              >
                <div class="font-medium text-slate-800">
                  {{ issue.title }}
                  <span v-if="issue.display_value" class="text-amber-600">— {{ issue.display_value }}</span>
                </div>
                <p class="text-slate-500 mt-1 leading-relaxed">{{ issue.description }}</p>
              </div>
            </div>
          </div>
        </div>

        <div class="flex gap-4 mt-2 text-xs">
          <a v-if="r.pagespeed_url" :href="r.pagespeed_url" target="_blank" class="text-indigo-600 hover:underline">تقرير PageSpeed ↗</a>
          <a v-if="r.screaming_frog_report_url" :href="r.screaming_frog_report_url" target="_blank" class="text-indigo-600 hover:underline">تقرير Screaming Frog ↗</a>
        </div>
        <p v-if="r.notes" class="text-xs text-slate-500 mt-2">{{ r.notes }}</p>
      </div>
      <div v-if="!reports.length" class="text-center text-slate-400 py-8">لا توجد تقارير أداء مسجّلة بعد.</div>
    </div>

    <div v-if="showCreate" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4 overflow-y-auto">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg p-6 my-8">
        <h2 class="text-lg font-bold text-slate-900 mb-4">تسجيل قياس أداء يدوي</h2>
        <form class="space-y-4" @submit.prevent="createReport">
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">المرحلة</label>
            <select v-model="form.stage" class="w-full rounded-lg border border-slate-300 px-3 py-2">
              <option value="original_template">القالب الأصلي (Envato Demo)</option>
              <option value="final_site">الموقع النهائي</option>
            </select>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Lighthouse موبايل</label>
              <input v-model="form.lighthouse_mobile" type="number" min="0" max="100" class="w-full rounded-lg border border-slate-300 px-3 py-2" />
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Lighthouse ديسكتوب</label>
              <input v-model="form.lighthouse_desktop" type="number" min="0" max="100" class="w-full rounded-lg border border-slate-300 px-3 py-2" />
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">عدد الإضافات</label>
            <input v-model="form.plugin_count" type="number" min="0" class="w-full rounded-lg border border-slate-300 px-3 py-2" />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">رابط تقرير PageSpeed/GTmetrix</label>
            <input v-model="form.pagespeed_url" type="url" class="w-full rounded-lg border border-slate-300 px-3 py-2" />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">رابط تقرير Screaming Frog</label>
            <input v-model="form.screaming_frog_report_url" type="url" class="w-full rounded-lg border border-slate-300 px-3 py-2" />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">تاريخ القياس</label>
            <input v-model="form.measured_at" type="date" required class="w-full rounded-lg border border-slate-300 px-3 py-2" />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">ملاحظات</label>
            <textarea v-model="form.notes" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2"></textarea>
          </div>
          <div class="flex gap-3 justify-end pt-2">
            <button type="button" class="px-4 py-2 text-sm rounded-lg text-slate-600 hover:bg-slate-100" @click="showCreate = false">
              إلغاء
            </button>
            <button type="submit" :disabled="creating" class="px-4 py-2 text-sm rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-medium disabled:opacity-60">
              {{ creating ? '...جاري الحفظ' : 'حفظ' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
