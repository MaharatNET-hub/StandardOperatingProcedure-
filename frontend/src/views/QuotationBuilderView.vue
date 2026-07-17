<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '../lib/api'

const props = defineProps({ id: [String, Number] })
const router = useRouter()

const isNew = computed(() => !props.id)
const loading = ref(!isNew.value)
const scanning = ref(false)
const saving = ref(false)
const exporting = ref(false)
const scanError = ref('')
const saveError = ref('')
const copied = ref(false)

const cycleLabels = { monthly: 'شهري', yearly: 'سنوي', lifetime: 'مدى الحياة' }
const statusLabels = { draft: 'مسودة', sent: 'مُرسل للعميل', accepted: 'مقبول', rejected: 'مرفوض' }
const businessLabels = {
  ecommerce: 'متجر إلكتروني', restaurant: 'مطعم / طلب وتوصيل', medical: 'قطاع طبي', real_estate: 'قطاع عقاري',
  education: 'منصة تعليمية', blog_news: 'مدونة / إخباري', legal: 'مكتب محاماة', corporate: 'موقع تعريفي لشركة',
  unknown: 'غير محدد بدقة',
}

const scanUrl = ref('')
const analyzed = ref(false)

const emptyQuotation = () => ({
  url: '', client_name: '', status: 'draft',
  detected_framework: null, detected_signals: [], meta_title: null, meta_description: null,
  business_type: null, business_summary: null, infrastructure: {}, recommended_platform: null, recommendation_reason: null,
  project_summary: '', technical_scope: '', cost_items: [], currency: 'USD',
  domain_cost: null, hosting_cost: null, hosting_cycle: 'yearly', support_months: 1,
})

const quotation = ref(emptyQuotation())

async function loadExisting() {
  loading.value = true
  const { data } = await api.get(`/quotations/${props.id}`)
  quotation.value = { ...emptyQuotation(), ...data }
  analyzed.value = true
  loading.value = false
}

async function runScan() {
  if (!scanUrl.value.trim()) return
  scanError.value = ''
  scanning.value = true
  try {
    const { data } = await api.post('/technical-analysis/scan', { url: scanUrl.value.trim() })
    Object.assign(quotation.value, data)
    if (!quotation.value.project_summary) quotation.value.project_summary = data.business_summary
    analyzed.value = true
  } catch (e) {
    scanError.value = e.response?.data?.message || 'تعذر تحليل الرابط. تحقق من الرابط وحاول مجدداً.'
  } finally {
    scanning.value = false
  }
}

function addCostItem() {
  quotation.value.cost_items.push({ name: '', type: 'Plugin', price: 0, cycle: 'yearly' })
}
function removeCostItem(i) {
  quotation.value.cost_items.splice(i, 1)
}

async function save() {
  saveError.value = ''
  saving.value = true
  try {
    if (isNew.value) {
      const { data } = await api.post('/quotations', quotation.value)
      router.replace({ name: 'quotation-edit', params: { id: data.id } })
      quotation.value = { ...emptyQuotation(), ...data }
    } else {
      const { data } = await api.patch(`/quotations/${props.id}`, quotation.value)
      quotation.value = { ...emptyQuotation(), ...data }
    }
  } catch (e) {
    saveError.value = e.response?.data?.message || 'تعذر حفظ عرض السعر — تحقق من الحقول المطلوبة.'
  } finally {
    saving.value = false
  }
}

async function exportPdf() {
  exporting.value = true
  try {
    const response = await api.get(`/quotations/${props.id}/pdf`, { responseType: 'blob' })
    const url = window.URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }))
    const link = document.createElement('a')
    link.href = url
    link.download = `عرض-سعر-${props.id}.pdf`
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch (e) {
    alert('تعذر تصدير ملف الـ PDF.')
  } finally {
    exporting.value = false
  }
}

function copyText() {
  const q = quotation.value
  const lines = [
    'MAHARAT NET — مهارات نت',
    'عرض سعر فني',
    '',
    `الموقع: ${q.url}`,
    q.client_name ? `العميل: ${q.client_name}` : null,
    '',
    'ملخص المشروع:',
    q.project_summary || q.business_summary || '—',
    '',
    'التحليل الفني:',
    `- المنصة الحالية: ${q.detected_framework || '—'}`,
    `- طبيعة النشاط: ${businessLabels[q.business_type] || '—'}`,
    `- الاستضافة/الحماية: ${q.infrastructure?.cdn_or_firewall || '—'}`,
    '',
    'التوصية الفنية:',
    `${q.recommended_platform || '—'}`,
    q.recommendation_reason || '',
    '',
    q.technical_scope ? `التوصيف التقني:\n${q.technical_scope}\n` : null,
    'جدول التكاليف التشغيلية:',
    ...(q.cost_items?.length
      ? q.cost_items.map((i) => `- ${i.name} (${i.type || '—'}) — ${i.price} ${q.currency} / ${cycleLabels[i.cycle] || i.cycle}`)
      : ['- لا توجد إضافات مدفوعة مطلوبة']),
    '',
    `الدومين (تقديري): ${q.domain_cost != null ? q.domain_cost + ' ' + q.currency + ' / سنوياً' : '—'}`,
    `الاستضافة (تقديري): ${q.hosting_cost != null ? q.hosting_cost + ' ' + q.currency + ' / ' + cycleLabels[q.hosting_cycle] : '—'}`,
    `الدعم الفني المجاني: ${q.support_months ? q.support_months + ' شهر' : '—'}`,
  ].filter((l) => l !== null)

  navigator.clipboard.writeText(lines.join('\n')).then(() => {
    copied.value = true
    setTimeout(() => (copied.value = false), 2000)
  })
}

onMounted(() => {
  if (!isNew.value) loadExisting()
})
</script>

<template>
  <div class="p-6 md:p-8 max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">{{ isNew ? 'تحليل موقع جديد' : 'تعديل عرض السعر' }}</h1>
        <p class="text-sm text-slate-500 mt-1">محاكاة Screaming Frog / Wappalyzer + إعداد عرض سعر بأسلوب Maharat Net.</p>
      </div>
      <router-link :to="{ name: 'quotations' }" class="text-sm text-indigo-600 hover:underline">← العودة للقائمة</router-link>
    </div>

    <div v-if="loading" class="text-slate-500">...جاري التحميل</div>

    <template v-else>
      <div v-if="isNew" class="bg-white rounded-xl border border-slate-200 p-5 mb-6">
        <label class="block text-sm font-medium text-slate-700 mb-2">رابط الموقع المراد فحصه</label>
        <div class="flex gap-3">
          <input
            v-model="scanUrl"
            type="text"
            placeholder="example.com"
            class="flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm"
            @keyup.enter="runScan"
          />
          <button
            class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg px-4 py-2 disabled:opacity-50"
            :disabled="scanning"
            @click="runScan"
          >
            {{ scanning ? '...جاري التحليل' : 'تحليل الرابط' }}
          </button>
        </div>
        <p v-if="scanError" class="text-sm text-red-600 mt-2">{{ scanError }}</p>
      </div>

      <template v-if="analyzed">
        <div class="bg-white rounded-xl border border-slate-200 p-5 mb-6">
          <h2 class="font-semibold text-slate-900 mb-3">نتائج التحليل الفني</h2>
          <div class="grid sm:grid-cols-3 gap-3 mb-4">
            <div class="bg-slate-50 rounded-lg p-3">
              <div class="text-xs text-slate-500 mb-1">إطار العمل / المنصة</div>
              <div class="text-sm font-bold text-slate-900">{{ quotation.detected_framework || '—' }}</div>
            </div>
            <div class="bg-slate-50 rounded-lg p-3">
              <div class="text-xs text-slate-500 mb-1">طبيعة النشاط</div>
              <div class="text-sm font-bold text-slate-900">{{ businessLabels[quotation.business_type] || '—' }}</div>
            </div>
            <div class="bg-slate-50 rounded-lg p-3">
              <div class="text-xs text-slate-500 mb-1">الحماية / CDN</div>
              <div class="text-sm font-bold text-slate-900">{{ quotation.infrastructure?.cdn_or_firewall || '—' }}</div>
            </div>
          </div>
          <div class="bg-indigo-50 rounded-lg p-4 text-sm">
            <div class="font-semibold text-indigo-900 mb-1">التوصية الفنية: {{ quotation.recommended_platform }}</div>
            <div class="text-indigo-800">{{ quotation.recommendation_reason }}</div>
          </div>
          <button v-if="!isNew" class="text-xs text-indigo-600 hover:underline mt-3" :disabled="scanning" @click="scanUrl = quotation.url; runScan()">
            {{ scanning ? '...جاري إعادة التحليل' : 'إعادة تحليل الرابط' }}
          </button>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5 mb-6 space-y-4">
          <h2 class="font-semibold text-slate-900">تقرير العرض (Quotation)</h2>

          <div class="grid sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-medium text-slate-600 mb-1">اسم العميل</label>
              <input v-model="quotation.client_name" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
            </div>
            <div>
              <label class="block text-xs font-medium text-slate-600 mb-1">حالة العرض</label>
              <select v-model="quotation.status" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option v-for="(label, key) in statusLabels" :key="key" :value="key">{{ label }}</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">ملخص المشروع (عن ماذا يتحدث الموقع)</label>
            <textarea v-model="quotation.project_summary" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea>
          </div>

          <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">التوصيف التقني للعمل المقترح</label>
            <textarea v-model="quotation.technical_scope" rows="4" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="مثال: إعادة بناء الموقع بالكامل على WordPress + Astra Pro، ترحيل المحتوى، تحسين الأداء والـ SEO..."></textarea>
          </div>

          <div>
            <div class="flex items-center justify-between mb-2">
              <label class="block text-xs font-medium text-slate-600">جدول التكاليف التشغيلية (الإضافات المدفوعة)</label>
              <button class="text-xs text-indigo-600 hover:underline" @click="addCostItem">+ إضافة بند</button>
            </div>
            <div v-if="!quotation.cost_items.length" class="text-xs text-slate-400 mb-2">لا توجد إضافات مدفوعة مضافة بعد.</div>
            <div v-for="(item, i) in quotation.cost_items" :key="i" class="grid grid-cols-12 gap-2 mb-2 items-center">
              <input v-model="item.name" type="text" placeholder="اسم الإضافة" class="col-span-5 rounded-lg border border-slate-300 px-2 py-1.5 text-sm" />
              <input v-model="item.type" type="text" placeholder="النوع" class="col-span-2 rounded-lg border border-slate-300 px-2 py-1.5 text-sm" />
              <input v-model.number="item.price" type="number" min="0" step="0.01" placeholder="السعر" class="col-span-2 rounded-lg border border-slate-300 px-2 py-1.5 text-sm" />
              <select v-model="item.cycle" class="col-span-2 rounded-lg border border-slate-300 px-2 py-1.5 text-sm">
                <option v-for="(label, key) in cycleLabels" :key="key" :value="key">{{ label }}</option>
              </select>
              <button class="col-span-1 text-red-500 hover:text-red-700 text-sm" @click="removeCostItem(i)">✕</button>
            </div>
          </div>

          <div class="grid sm:grid-cols-4 gap-4">
            <div>
              <label class="block text-xs font-medium text-slate-600 mb-1">العملة</label>
              <select v-model="quotation.currency" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="USD">USD</option>
                <option value="JOD">JOD</option>
                <option value="SAR">SAR</option>
                <option value="AED">AED</option>
                <option value="EGP">EGP</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-medium text-slate-600 mb-1">الدومين (سنوياً)</label>
              <input v-model.number="quotation.domain_cost" type="number" min="0" step="0.01" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
            </div>
            <div>
              <label class="block text-xs font-medium text-slate-600 mb-1">الاستضافة</label>
              <input v-model.number="quotation.hosting_cost" type="number" min="0" step="0.01" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
            </div>
            <div>
              <label class="block text-xs font-medium text-slate-600 mb-1">دورة الاستضافة</label>
              <select v-model="quotation.hosting_cycle" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="monthly">شهري</option>
                <option value="yearly">سنوي</option>
              </select>
            </div>
          </div>

          <div class="sm:w-1/4">
            <label class="block text-xs font-medium text-slate-600 mb-1">مدة الدعم الفني المجاني (بالأشهر)</label>
            <input v-model.number="quotation.support_months" type="number" min="0" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
          </div>

          <p v-if="saveError" class="text-sm text-red-600">{{ saveError }}</p>

          <div class="flex flex-wrap items-center gap-3 pt-2 border-t border-slate-100">
            <button
              class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg px-4 py-2 disabled:opacity-50"
              :disabled="saving"
              @click="save"
            >
              {{ saving ? '...جاري الحفظ' : 'حفظ عرض السعر' }}
            </button>
            <button
              v-if="!isNew"
              class="bg-slate-900 hover:bg-slate-800 text-white text-sm font-medium rounded-lg px-4 py-2 disabled:opacity-50"
              :disabled="exporting"
              @click="exportPdf"
            >
              {{ exporting ? '...جاري التصدير' : 'تحميل PDF' }}
            </button>
            <button v-if="!isNew" class="border border-slate-300 hover:bg-slate-50 text-sm font-medium rounded-lg px-4 py-2" @click="copyText">
              {{ copied ? 'تم النسخ ✓' : 'نسخ نص العرض' }}
            </button>
            <span v-if="isNew" class="text-xs text-slate-400">احفظ العرض أولاً لتفعيل تحميل الـ PDF والنسخ.</span>
          </div>
        </div>
      </template>
    </template>
  </div>
</template>
