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

async function load() {
  loading.value = true
  const { data } = await api.get('/settings')
  keySet.value = data.pagespeed_api_key_set
  keyPreview.value = data.pagespeed_api_key_preview
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

onMounted(load)
</script>

<template>
  <div class="p-6 md:p-8 max-w-2xl mx-auto">
    <h1 class="text-2xl font-bold text-slate-900 mb-6">الإعدادات</h1>

    <div v-if="loading" class="text-slate-500">...جاري التحميل</div>

    <div v-else class="bg-white rounded-xl border border-slate-200 p-6">
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
  </div>
</template>
