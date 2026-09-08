<script setup>
import { ref } from 'vue'
import api from '../lib/api'
import { pipelineStageLabels, projectTypeLabels } from '../lib/labels'

const props = defineProps({ project: Object })
const emit = defineEmits(['reload'])

function dateInput(value) {
  return value ? value.slice(0, 10) : ''
}

const form = ref({
  project_type: props.project.project_type || '',
  project_description: props.project.project_description || '',
  current_task: props.project.current_task || '',
  pipeline_stage: props.project.pipeline_stage || 'new_project',
  start_date: dateInput(props.project.start_date),

  has_domain: !!props.project.has_domain,
  domain_name: props.project.domain_name || '',
  domain_login_info: props.project.domain_login_info || '',
  domain_purchaser: props.project.domain_purchaser || '',
  has_hosting: !!props.project.has_hosting,
  hosting_provider: props.project.hosting_provider || '',
  hosting_login_info: props.project.hosting_login_info || '',
  hosting_purchaser: props.project.hosting_purchaser || '',

  has_logo: !!props.project.has_logo,
  needs_logo_design: !!props.project.needs_logo_design,
  design_style: props.project.design_style || '',
  website_languages_count: props.project.website_languages_count || 1,
  primary_language: props.project.primary_language || '',
  secondary_language: props.project.secondary_language || '',

  needs_payment_gateway: !!props.project.needs_payment_gateway,
  payment_gateway_type: props.project.payment_gateway_type || '',
  payment_gateway_status: props.project.payment_gateway_status || 'not_started',
  has_shipping_company: !!props.project.has_shipping_company,
  shipping_company_name: props.project.shipping_company_name || '',

  content_ready: !!props.project.content_ready,
  product_images_ready: !!props.project.product_images_ready,
  seo_required: !!props.project.seo_required,
  seo_status: props.project.seo_status || 'not_started',
  google_analytics_connected: !!props.project.google_analytics_connected,
  search_console_connected: !!props.project.search_console_connected,

  phone: props.project.phone || '',
  whatsapp: props.project.whatsapp || '',
  social_media_available: !!props.project.social_media_available,
  social_media_login_info: props.project.social_media_login_info || '',
  website_uploaded: !!props.project.website_uploaded,
})

const saving = ref(false)
const error = ref('')
const success = ref('')

async function save() {
  saving.value = true
  error.value = ''
  success.value = ''
  try {
    await api.patch(`/projects/${props.project.id}`, form.value)
    success.value = 'تم الحفظ.'
    emit('reload')
  } catch (e) {
    error.value = e.response?.data?.message || 'تعذر الحفظ.'
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div class="space-y-5">
    <div class="bg-white rounded-xl border border-slate-200 p-5 space-y-4">
      <h2 class="font-semibold text-slate-900">نظرة عامة</h2>
      <div class="grid sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-medium text-slate-600 mb-1">نوع المشروع</label>
          <select v-model="form.project_type" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <option value="">— اختر —</option>
            <option v-for="(label, key) in projectTypeLabels" :key="key" :value="key">{{ label }}</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-600 mb-1">مرحلة خط السير</label>
          <select v-model="form.pipeline_stage" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <option v-for="(label, key) in pipelineStageLabels" :key="key" :value="key">{{ label }}</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-600 mb-1">المهمة الحالية</label>
          <input v-model="form.current_task" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="مثال: برمجة الصفحة الرئيسية" />
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-600 mb-1">تاريخ البدء</label>
          <input v-model="form.start_date" type="date" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
        </div>
      </div>
      <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">وصف المشروع</label>
        <textarea v-model="form.project_description" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="ملخص مختصر لطبيعة المشروع..."></textarea>
      </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 p-5 space-y-4">
      <h2 class="font-semibold text-slate-900">الدومين والاستضافة</h2>
      <div class="grid sm:grid-cols-2 gap-4">
        <div class="space-y-2">
          <label class="flex items-center gap-2 text-sm text-slate-700">
            <input type="checkbox" v-model="form.has_domain" class="rounded border-slate-300" /> يوجد دومين
          </label>
          <input v-model="form.domain_name" type="text" placeholder="اسم الدومين" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
          <select v-model="form.domain_purchaser" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <option value="">من سيشتري الدومين؟</option>
            <option value="client">العميل</option>
            <option value="maharat">مهارات نت</option>
          </select>
          <textarea v-model="form.domain_login_info" rows="2" placeholder="بيانات دخول الدومين" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea>
        </div>
        <div class="space-y-2">
          <label class="flex items-center gap-2 text-sm text-slate-700">
            <input type="checkbox" v-model="form.has_hosting" class="rounded border-slate-300" /> توجد استضافة
          </label>
          <input v-model="form.hosting_provider" type="text" placeholder="مزوّد الاستضافة" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
          <select v-model="form.hosting_purchaser" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <option value="">من سيشتري الاستضافة؟</option>
            <option value="client">العميل</option>
            <option value="maharat">مهارات نت</option>
          </select>
          <textarea v-model="form.hosting_login_info" rows="2" placeholder="بيانات دخول الاستضافة" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 p-5 space-y-4">
      <h2 class="font-semibold text-slate-900">الهوية والتصميم</h2>
      <div class="grid sm:grid-cols-2 gap-4">
        <label class="flex items-center gap-2 text-sm text-slate-700">
          <input type="checkbox" v-model="form.has_logo" class="rounded border-slate-300" /> يوجد شعار من العميل
        </label>
        <label class="flex items-center gap-2 text-sm text-slate-700">
          <input type="checkbox" v-model="form.needs_logo_design" class="rounded border-slate-300" /> يحتاج تصميم شعار من الفريق
        </label>
        <input v-model="form.design_style" type="text" placeholder="نمط التصميم / الألوان" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
        <input v-model.number="form.website_languages_count" type="number" min="1" max="10" placeholder="عدد لغات الموقع" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
        <input v-model="form.primary_language" type="text" placeholder="اللغة الأساسية" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
        <input v-model="form.secondary_language" type="text" placeholder="اللغة الثانوية" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
      </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 p-5 space-y-4">
      <h2 class="font-semibold text-slate-900">التجارة الإلكترونية</h2>
      <div class="grid sm:grid-cols-2 gap-4">
        <label class="flex items-center gap-2 text-sm text-slate-700">
          <input type="checkbox" v-model="form.needs_payment_gateway" class="rounded border-slate-300" /> يحتاج بوابة دفع
        </label>
        <select v-model="form.payment_gateway_status" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
          <option value="not_started">لم يبدأ</option>
          <option value="in_progress">قيد التنفيذ</option>
          <option value="done">مكتمل</option>
        </select>
        <input v-model="form.payment_gateway_type" type="text" placeholder="نوع بوابة الدفع" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
        <label class="flex items-center gap-2 text-sm text-slate-700">
          <input type="checkbox" v-model="form.has_shipping_company" class="rounded border-slate-300" /> توجد شركة شحن
        </label>
        <input v-model="form.shipping_company_name" type="text" placeholder="اسم شركة الشحن" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
      </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 p-5 space-y-4">
      <h2 class="font-semibold text-slate-900">المحتوى والتسويق</h2>
      <div class="grid sm:grid-cols-2 gap-3">
        <label class="flex items-center gap-2 text-sm text-slate-700">
          <input type="checkbox" v-model="form.content_ready" class="rounded border-slate-300" /> المحتوى النصي جاهز
        </label>
        <label class="flex items-center gap-2 text-sm text-slate-700">
          <input type="checkbox" v-model="form.product_images_ready" class="rounded border-slate-300" /> صور المنتجات جاهزة
        </label>
        <label class="flex items-center gap-2 text-sm text-slate-700">
          <input type="checkbox" v-model="form.seo_required" class="rounded border-slate-300" /> يحتاج إعداد SEO
        </label>
        <select v-model="form.seo_status" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
          <option value="not_started">لم يبدأ</option>
          <option value="in_progress">قيد التنفيذ</option>
          <option value="done">مكتمل</option>
        </select>
        <label class="flex items-center gap-2 text-sm text-slate-700">
          <input type="checkbox" v-model="form.google_analytics_connected" class="rounded border-slate-300" /> Google Analytics مربوط
        </label>
        <label class="flex items-center gap-2 text-sm text-slate-700">
          <input type="checkbox" v-model="form.search_console_connected" class="rounded border-slate-300" /> Search Console مربوط
        </label>
        <label class="flex items-center gap-2 text-sm text-slate-700">
          <input type="checkbox" v-model="form.website_uploaded" class="rounded border-slate-300" /> تم رفع الموقع
        </label>
      </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 p-5 space-y-4">
      <h2 class="font-semibold text-slate-900">التواصل والسوشيال ميديا</h2>
      <div class="grid sm:grid-cols-2 gap-4">
        <input v-model="form.phone" type="text" placeholder="رقم الهاتف" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
        <input v-model="form.whatsapp" type="text" placeholder="واتساب" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
        <label class="flex items-center gap-2 text-sm text-slate-700">
          <input type="checkbox" v-model="form.social_media_available" class="rounded border-slate-300" /> حسابات السوشيال ميديا متوفرة
        </label>
        <textarea v-model="form.social_media_login_info" rows="2" placeholder="بيانات دخول حسابات التواصل" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea>
      </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 p-5">
      <p v-if="error" class="text-sm text-red-600 mb-3">{{ error }}</p>
      <p v-if="success" class="text-sm text-emerald-600 mb-3">{{ success }}</p>
      <button
        class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg px-4 py-2 disabled:opacity-50"
        :disabled="saving"
        @click="save"
      >
        {{ saving ? '...جاري الحفظ' : 'حفظ التفاصيل' }}
      </button>
    </div>
  </div>
</template>
