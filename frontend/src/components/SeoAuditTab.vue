<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '../lib/api'

const props = defineProps({ project: Object })

const audits = ref([])
const loading = ref(true)
const uploading = ref(false)
const error = ref('')
const openChecks = ref({})
const selectedAuditId = ref(null)

const latestAudit = computed(() => audits.value.find((a) => a.id === selectedAuditId.value) || audits.value[0])

const passCount = computed(() => latestAudit.value?.results.filter((c) => c.status === 'pass').length || 0)
const totalChecks = computed(() => latestAudit.value?.results.length || 0)

async function load() {
  loading.value = true
  const { data } = await api.get(`/projects/${props.project.id}/seo-audits`)
  audits.value = data
  if (data.length) selectedAuditId.value = data[0].id
  loading.value = false
}

async function upload(event) {
  const file = event.target.files[0]
  if (!file) return
  error.value = ''
  uploading.value = true
  try {
    await api.post(`/projects/${props.project.id}/seo-audits`, { file }, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    await load()
  } catch (e) {
    error.value = e.response?.data?.message || 'تعذر تحليل الملف — تأكد أنه تصدير Internal (All أو HTML) من Screaming Frog.'
  } finally {
    uploading.value = false
    event.target.value = ''
  }
}

function toggle(key) {
  openChecks.value[key] = !openChecks.value[key]
}

onMounted(load)
</script>

<template>
  <div>
    <div class="bg-indigo-50 text-indigo-900 text-sm rounded-xl p-4 mb-5 space-y-1">
      <p class="font-medium">استيراد تقرير Screaming Frog</p>
      <p>
        سوّي الفحص على Screaming Frog متل العادة، بعدين من تبويب <strong>Internal</strong> اختر فلتر <strong>All</strong>
        (يشمل HTML وCSS وJS والصور والخطوط وكل الملفات) واضغط <strong>Export</strong> لتصدير CSV، وارفعه هون. فلتر
        <strong>HTML</strong> لسا مدعوم لو بدك فحص أسرع بدون الأصول. النظام بيفحص تلقائياً: عدد H1، وسم noindex، Canonical
        الذاتي، الروابط المكسورة (على كل أنواع الملفات)، التحويلات المؤقتة، طول Title/Meta Description وتكرارها، المحتوى
        المكرر، أحجام الصور، وعدد الإضافات المكتشفة من روابط wp-content/plugins — وبيحدّث بنود قائمة التحقق المطابقة (الأقسام 3
        و4 و5) تلقائياً.
      </p>
      <p class="pt-2 border-t border-indigo-100 mt-2">
        <strong>فحص اكتمال الترجمة (اختياري):</strong> لو الموقع بلغتين (عربي/إنكليزي)، فعّل من
        <strong>Configuration → Custom → Extraction</strong> استخراج باسم <strong>BodyText</strong> بنوع
        <strong>Extract Text</strong> وXPath <code>//body</code>، وأعد الزحف قبل التصدير. النظام رح يفحص كل صفحة عربي
        (الروابط اللي مش فيها <code>/en/</code>) وينبّهك على نص إنكليزي متبقٍ (زي "Checkout" أو "Wishlist" بدون ترجمة) أو
        مصطلحات مترجمة غلط (متل "الأمنيات" بدل "المفضلة"). بدون هالإعداد بيتخطى النظام هذا الفحص تلقائياً.
      </p>
    </div>

    <div class="flex items-center gap-3 mb-6">
      <label class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg px-4 py-2 cursor-pointer">
        {{ uploading ? '...جاري التحليل' : '+ رفع ملف CSV' }}
        <input type="file" accept=".csv,text/csv" class="hidden" :disabled="uploading" @change="upload" />
      </label>
      <span v-if="error" class="text-sm text-red-600">{{ error }}</span>
    </div>

    <div v-if="loading" class="text-slate-500">...جاري التحميل</div>

    <template v-else-if="latestAudit">
      <div class="bg-white rounded-xl border border-slate-200 p-5 mb-6">
        <div class="flex items-center justify-between">
          <div>
            <h2 class="font-semibold text-slate-900">{{ latestAudit.source_filename }}</h2>
            <p class="text-xs text-slate-400 mt-1">
              {{ latestAudit.total_urls }} رابط مفحوص — {{ new Date(latestAudit.created_at).toLocaleString('ar') }} —
              {{ latestAudit.importer?.name }}
            </p>
          </div>
          <span
            class="px-3 py-1.5 rounded-full text-sm font-medium"
            :class="passCount === totalChecks ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'"
          >
            {{ passCount }} / {{ totalChecks }} فحص ناجح
          </span>
        </div>

        <select v-if="audits.length > 1" v-model="selectedAuditId" class="mt-3 text-xs rounded-lg border border-slate-300 px-2 py-1">
          <option v-for="a in audits" :key="a.id" :value="a.id">
            {{ a.source_filename }} — {{ new Date(a.created_at).toLocaleDateString('ar') }}
          </option>
        </select>
      </div>

      <div class="space-y-3">
        <div v-for="check in latestAudit.results" :key="check.key" class="bg-white rounded-xl border border-slate-200 overflow-hidden">
          <button class="w-full flex items-center justify-between p-4 hover:bg-slate-50 transition" @click="toggle(check.key)">
            <div class="text-right flex items-center gap-2">
              <span
                class="w-2.5 h-2.5 rounded-full shrink-0"
                :class="check.status === 'pass' ? 'bg-emerald-500' : 'bg-red-500'"
              ></span>
              <span class="font-medium text-slate-900">{{ check.label_ar }}</span>
              <span class="text-xs text-slate-400">({{ check.category_code }})</span>
            </div>
            <div class="flex items-center gap-3 shrink-0">
              <span class="text-xs text-slate-500">{{ check.summary }}</span>
              <span v-if="check.affected_total" class="text-slate-400">{{ openChecks[check.key] ? '−' : '+' }}</span>
            </div>
          </button>
          <div v-if="openChecks[check.key] && check.affected?.length" class="border-t border-slate-100 p-4 space-y-1">
            <div v-for="(item, i) in check.affected" :key="i" class="text-xs text-slate-600 break-all">{{ item }}</div>
            <p v-if="check.affected_total > check.affected.length" class="text-xs text-slate-400 mt-2">
              و{{ check.affected_total - check.affected.length }} رابط إضافي...
            </p>
          </div>
        </div>
      </div>
    </template>

    <div v-else class="text-center text-slate-400 py-8">لا توجد فحوصات مستوردة بعد.</div>
  </div>
</template>
