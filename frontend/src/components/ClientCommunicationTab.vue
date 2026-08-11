<script setup>
import { ref, computed } from 'vue'
import api from '../lib/api'

const props = defineProps({ project: Object })
const emit = defineEmits(['reload'])

const feedbackLabels = { none: 'لا يوجد', new: 'جديدة', in_progress: 'قيد التنفيذ', completed: 'مكتملة' }
const blockerLabels = {
  none: 'لا يوجد',
  waiting_client: 'بانتظار العميل',
  waiting_developer: 'بانتظار المبرمج',
  waiting_payment_gateway: 'بانتظار بوابة الدفع',
  waiting_domain: 'بانتظار الدومين',
  waiting_hosting: 'بانتظار الاستضافة',
  waiting_content: 'بانتظار المحتوى',
  waiting_logo: 'بانتظار الشعار',
  waiting_product_images: 'بانتظار صور المنتجات',
  other: 'أخرى',
}

function toLocalInput(value) {
  if (!value) return ''
  const d = new Date(value)
  const pad = (n) => String(n).padStart(2, '0')
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`
}

const form = ref({
  client_feedback_status: props.project.client_feedback_status || 'none',
  client_feedback_notes: props.project.client_feedback_notes || '',
  client_feedback_at: props.project.client_feedback_at ? props.project.client_feedback_at.slice(0, 10) : '',
  next_meeting_at: toLocalInput(props.project.next_meeting_at),
  blocker: props.project.blocker || 'none',
  client_notes: props.project.client_notes || '',
  internal_notes: props.project.internal_notes || '',
})

const intervalDays = ref(props.project.client_update_interval_days || 3)
const sending = ref(false)
const saving = ref(false)
const error = ref('')
const success = ref('')

const daysSinceUpdate = computed(() => {
  if (!props.project.last_client_update_at) return null
  const diff = Date.now() - new Date(props.project.last_client_update_at).getTime()
  return Math.floor(diff / 86400000)
})

async function sendUpdate() {
  sending.value = true
  error.value = ''
  try {
    await api.post(`/projects/${props.project.id}/send-client-update`, { interval_days: intervalDays.value })
    emit('reload')
  } catch (e) {
    error.value = e.response?.data?.message || 'تعذر إرسال التحديث.'
  } finally {
    sending.value = false
  }
}

async function save() {
  saving.value = true
  error.value = ''
  success.value = ''
  try {
    await api.patch(`/projects/${props.project.id}`, {
      ...form.value,
      next_meeting_at: form.value.next_meeting_at || null,
      client_feedback_at: form.value.client_feedback_at || null,
    })
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
  <div class="space-y-6">
    <div class="bg-white rounded-xl border border-slate-200 p-5">
      <h2 class="font-semibold text-slate-900 mb-4">متابعة التحديثات مع العميل</h2>
      <div class="grid sm:grid-cols-3 gap-4 mb-4 text-sm">
        <div>
          <div class="text-xs text-slate-500 mb-1">آخر تحديث</div>
          <div class="font-medium text-slate-800">
            {{ project.last_client_update_at ? new Date(project.last_client_update_at).toLocaleDateString('ar') : '—' }}
            <span v-if="daysSinceUpdate !== null" class="text-xs text-slate-400">(منذ {{ daysSinceUpdate }} يوم)</span>
          </div>
        </div>
        <div>
          <div class="text-xs text-slate-500 mb-1">التحديث القادم</div>
          <div class="font-medium text-slate-800">
            {{ project.next_client_update_at ? new Date(project.next_client_update_at).toLocaleDateString('ar') : '—' }}
          </div>
        </div>
        <div>
          <label class="text-xs text-slate-500 mb-1 block">كل كم يوم (عند الإرسال)</label>
          <input v-model.number="intervalDays" type="number" min="1" max="60" class="w-full rounded-lg border border-slate-300 px-3 py-1.5" />
        </div>
      </div>
      <button
        class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg px-4 py-2 disabled:opacity-50"
        :disabled="sending"
        @click="sendUpdate"
      >
        {{ sending ? '...جاري الإرسال' : 'إرسال تحديث للعميل الآن' }}
      </button>
      <p class="text-xs text-slate-400 mt-2">
        يسجّل تاريخ اليوم كآخر تحديث، ويحسب موعد التحديث القادم تلقائياً (اليوم + عدد الأيام أعلاه).
      </p>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 p-5 space-y-4">
      <h2 class="font-semibold text-slate-900">ملاحظات العميل والموعد القادم</h2>

      <div class="grid sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-medium text-slate-600 mb-1">حالة الملاحظات</label>
          <select v-model="form.client_feedback_status" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <option v-for="(label, key) in feedbackLabels" :key="key" :value="key">{{ label }}</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-600 mb-1">تاريخ وصول الملاحظات</label>
          <input v-model="form.client_feedback_at" type="date" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
        </div>
      </div>

      <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">تفاصيل الملاحظات</label>
        <textarea v-model="form.client_feedback_notes" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="مثال: العميل طلب تعديل صفحة من نحن وإضافة قسم جديد للمنتجات."></textarea>
      </div>

      <div class="grid sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-medium text-slate-600 mb-1">موعد الاجتماع القادم</label>
          <input v-model="form.next_meeting_at" type="datetime-local" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-600 mb-1">المشروع متوقف بانتظار</label>
          <select v-model="form.blocker" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <option v-for="(label, key) in blockerLabels" :key="key" :value="key">{{ label }}</option>
          </select>
        </div>
      </div>

      <div class="grid sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-medium text-slate-600 mb-1">ملاحظات العميل</label>
          <textarea v-model="form.client_notes" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea>
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-600 mb-1">ملاحظات داخلية</label>
          <textarea v-model="form.internal_notes" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea>
        </div>
      </div>

      <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
      <p v-if="success" class="text-sm text-emerald-600">{{ success }}</p>

      <button
        class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg px-4 py-2 disabled:opacity-50"
        :disabled="saving"
        @click="save"
      >
        {{ saving ? '...جاري الحفظ' : 'حفظ' }}
      </button>
    </div>
  </div>
</template>
