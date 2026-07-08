<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '../lib/api'

const props = defineProps({ project: Object })

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

const stageLabels = { original_template: 'القالب الأصلي (Envato Demo)', final_site: 'الموقع النهائي' }

const originalReport = computed(() => reports.value.find((r) => r.stage === 'original_template'))
const finalReport = computed(() => [...reports.value].reverse().find((r) => r.stage === 'final_site'))

async function load() {
  loading.value = true
  const { data } = await api.get(`/projects/${props.project.id}/performance-reports`)
  reports.value = data
  loading.value = false
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
          <span
            class="text-2xl font-bold"
            :class="(finalReport.lighthouse_mobile ?? 0) >= 85 ? 'text-emerald-600' : 'text-amber-600'"
          >
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

    <div class="flex justify-end mb-4">
      <button class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg px-4 py-2" @click="showCreate = true">
        + تسجيل قياس أداء
      </button>
    </div>

    <div v-if="loading" class="text-slate-500">...جاري التحميل</div>

    <div v-else class="space-y-3">
      <div v-for="r in reports" :key="r.id" class="bg-white rounded-xl border border-slate-200 p-4">
        <div class="flex items-center justify-between mb-2">
          <span class="font-semibold text-slate-900">{{ stageLabels[r.stage] }}</span>
          <span class="text-xs text-slate-400">{{ new Date(r.measured_at).toLocaleDateString('ar') }}</span>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
          <div><span class="text-slate-400">Lighthouse موبايل:</span> {{ r.lighthouse_mobile ?? '—' }}</div>
          <div><span class="text-slate-400">Lighthouse ديسكتوب:</span> {{ r.lighthouse_desktop ?? '—' }}</div>
          <div><span class="text-slate-400">عدد الإضافات:</span> {{ r.plugin_count ?? '—' }}</div>
          <div><span class="text-slate-400">قِيسَ بواسطة:</span> {{ r.measurer?.name }}</div>
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
        <h2 class="text-lg font-bold text-slate-900 mb-4">تسجيل قياس أداء</h2>
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
