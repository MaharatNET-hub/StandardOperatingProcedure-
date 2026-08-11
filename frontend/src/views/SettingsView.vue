<script setup>
import { ref, onMounted } from 'vue'
import api from '../lib/api'

const loading = ref(true)
const saving = ref(false)
const error = ref('')
const success = ref('')

const keySet = ref(false)
const keyPreview = ref(null)
const apiKeyInput = ref('')

const weightLabels = {
  delivery_overdue: 'المشروع متأخر عن موعد التسليم',
  delivery_1_day: 'موعد التسليم خلال يوم',
  delivery_3_days: 'موعد التسليم خلال 3 أيام',
  delivery_1_week: 'موعد التسليم خلال أسبوع',
  update_overdue: 'موعد التحديث مع العميل متأخر',
  update_due_today: 'موعد التحديث مع العميل اليوم',
  feedback_new: 'يوجد ملاحظات جديدة من العميل',
  feedback_in_progress: 'يوجد تعديلات قيد التنفيذ من ملاحظات العميل',
  meeting_today: 'اجتماع العميل اليوم',
  meeting_tomorrow: 'اجتماع العميل غداً',
  missing_basic_data: 'توجد بيانات أساسية ناقصة',
}

const requirementLabels = {
  logo: 'الشعار',
  domain: 'الدومين',
  hosting: 'الاستضافة',
  content: 'المحتوى النصي',
  product_images: 'صور المنتجات',
  payment_gateway: 'بوابة الدفع',
  shipping_company: 'شركة الشحن',
  seo: 'SEO',
  google_analytics: 'Google Analytics',
  search_console: 'Search Console',
  social_media: 'حسابات التواصل',
}

const projectTypeLabels = {
  ecommerce: 'متجر إلكتروني',
  corporate: 'موقع شركة',
  landing_page: 'صفحة هبوط',
  portfolio: 'معرض أعمال',
  blog: 'مدونة',
  booking: 'موقع حجوزات',
  marketplace: 'سوق إلكتروني',
  custom: 'مخصص',
  mobile_app: 'تطبيق موبايل',
  other: 'أخرى',
}

const weights = ref({})
const requirements = ref({})
const weightsSaving = ref(false)
const weightsSuccess = ref('')
const requirementsSaving = ref(false)
const requirementsSuccess = ref('')

async function load() {
  loading.value = true
  const { data } = await api.get('/settings')
  keySet.value = data.pagespeed_api_key_set
  keyPreview.value = data.pagespeed_api_key_preview
  weights.value = { ...data.priority_weights }
  requirements.value = Object.fromEntries(
    Object.entries(data.project_type_requirements).map(([type, keys]) => [type, [...keys]])
  )
  loading.value = false
}

async function save() {
  error.value = ''
  success.value = ''
  if (!apiKeyInput.value) return
  saving.value = true
  try {
    await api.put('/settings', { pagespeed_api_key: apiKeyInput.value })
    apiKeyInput.value = ''
    success.value = 'تم الحفظ — المفتاح فعّال الآن فوراً.'
    await load()
  } catch (e) {
    error.value = e.response?.data?.message || 'حدث خطأ أثناء الحفظ.'
  } finally {
    saving.value = false
  }
}

async function saveWeights() {
  weightsSaving.value = true
  weightsSuccess.value = ''
  try {
    await api.put('/settings', { priority_weights: weights.value })
    weightsSuccess.value = 'تم حفظ أوزان الأولوية.'
  } finally {
    weightsSaving.value = false
  }
}

function toggleRequirement(type, key) {
  const list = requirements.value[type] || []
  const idx = list.indexOf(key)
  if (idx === -1) list.push(key)
  else list.splice(idx, 1)
}

async function saveRequirements() {
  requirementsSaving.value = true
  requirementsSuccess.value = ''
  try {
    await api.put('/settings', { project_type_requirements: requirements.value })
    requirementsSuccess.value = 'تم حفظ قوائم المتطلبات.'
  } finally {
    requirementsSaving.value = false
  }
}

onMounted(load)
</script>

<template>
  <div class="p-6 md:p-8 max-w-4xl mx-auto space-y-6">
    <h1 class="text-2xl font-bold text-slate-900">الإعدادات</h1>

    <div v-if="loading" class="text-slate-500">...جاري التحميل</div>

    <template v-else>
      <div class="bg-white rounded-xl border border-slate-200 p-6">
        <h2 class="font-semibold text-slate-900 mb-1">مفتاح Google PageSpeed Insights API</h2>
        <p class="text-sm text-slate-500 mb-4">
          يُستخدم لتشغيل فحص سرعة تلقائي (Lighthouse) لموبايل وديسكتوب لكل مشروع، مع تفاصيل المشاكل المكتشفة.
          يتفعّل فوراً بمجرد الحفظ، بدون الحاجة لإعادة نشر النظام.
        </p>

        <div class="mb-4">
          <span
            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium"
            :class="keySet ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'"
          >
            {{ keySet ? `مفعّل (${keyPreview})` : 'غير مفعّل بعد' }}
          </span>
        </div>

        <form class="space-y-3" @submit.prevent="save">
          <label class="block text-sm font-medium text-slate-700">
            {{ keySet ? 'استبدال المفتاح' : 'المفتاح' }}
          </label>
          <input
            v-model="apiKeyInput"
            type="password"
            placeholder="AIza..."
            class="w-full rounded-lg border border-slate-300 px-3 py-2"
          />

          <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
          <p v-if="success" class="text-sm text-emerald-600">{{ success }}</p>

          <button
            type="submit"
            :disabled="saving || !apiKeyInput"
            class="px-4 py-2 text-sm rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-medium disabled:opacity-60"
          >
            {{ saving ? '...جاري الحفظ' : 'حفظ وتفعيل' }}
          </button>
        </form>

        <div class="mt-6 pt-4 border-t border-slate-100 text-xs text-slate-500 space-y-1">
          <p class="font-medium text-slate-600">كيف تحصل على المفتاح؟</p>
          <p>1. افتح console.cloud.google.com وأنشئ مشروعاً (أو استخدم مشروعاً موجوداً)</p>
          <p>2. فعّل "PageSpeed Insights API" من مكتبة الـ APIs</p>
          <p>3. من APIs & Services → Credentials → Create Credentials → API Key</p>
        </div>
      </div>

      <div class="bg-white rounded-xl border border-slate-200 p-6">
        <h2 class="font-semibold text-slate-900 mb-1">أوزان درجة الأولوية</h2>
        <p class="text-sm text-slate-500 mb-4">
          عدد النقاط التي يضيفها كل عامل عند حساب أولوية المشروع اليومية تلقائياً.
        </p>

        <div class="grid sm:grid-cols-2 gap-3">
          <div v-for="(label, key) in weightLabels" :key="key" class="flex items-center justify-between gap-3 border border-slate-100 rounded-lg px-3 py-2">
            <span class="text-sm text-slate-700">{{ label }}</span>
            <input v-model.number="weights[key]" type="number" min="0" max="200" class="w-20 rounded-lg border border-slate-300 px-2 py-1 text-sm text-center" />
          </div>
        </div>

        <p v-if="weightsSuccess" class="text-sm text-emerald-600 mt-3">{{ weightsSuccess }}</p>

        <button
          class="mt-4 px-4 py-2 text-sm rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-medium disabled:opacity-60"
          :disabled="weightsSaving"
          @click="saveWeights"
        >
          {{ weightsSaving ? '...جاري الحفظ' : 'حفظ الأوزان' }}
        </button>
      </div>

      <div class="bg-white rounded-xl border border-slate-200 p-6">
        <h2 class="font-semibold text-slate-900 mb-1">المتطلبات المطلوبة لكل نوع مشروع</h2>
        <p class="text-sm text-slate-500 mb-4">
          تحدد هذه القائمة ما يفحصه النظام تلقائياً لحساب "جاهزية المشروع" — حسب نوعه.
        </p>

        <div class="space-y-4">
          <div v-for="(label, type) in projectTypeLabels" :key="type" class="border border-slate-100 rounded-lg p-3">
            <div class="text-sm font-medium text-slate-800 mb-2">{{ label }}</div>
            <div class="flex flex-wrap gap-2">
              <label
                v-for="(reqLabel, reqKey) in requirementLabels"
                :key="reqKey"
                class="flex items-center gap-1.5 text-xs px-2 py-1 rounded-full border cursor-pointer"
                :class="(requirements[type] || []).includes(reqKey) ? 'bg-indigo-50 border-indigo-200 text-indigo-700' : 'border-slate-200 text-slate-500'"
              >
                <input
                  type="checkbox"
                  class="hidden"
                  :checked="(requirements[type] || []).includes(reqKey)"
                  @change="toggleRequirement(type, reqKey)"
                />
                {{ reqLabel }}
              </label>
            </div>
          </div>
        </div>

        <p v-if="requirementsSuccess" class="text-sm text-emerald-600 mt-3">{{ requirementsSuccess }}</p>

        <button
          class="mt-4 px-4 py-2 text-sm rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-medium disabled:opacity-60"
          :disabled="requirementsSaving"
          @click="saveRequirements"
        >
          {{ requirementsSaving ? '...جاري الحفظ' : 'حفظ قوائم المتطلبات' }}
        </button>
      </div>
    </template>
  </div>
</template>
