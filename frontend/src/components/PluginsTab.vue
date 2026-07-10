<script setup>
import { ref, onMounted } from 'vue'
import api from '../lib/api'
import { useAuthStore } from '../stores/auth'

const props = defineProps({ project: Object })
const auth = useAuthStore()

const requests = ref([])
const loading = ref(true)
const showCreate = ref(false)
const creating = ref(false)
const form = ref({ plugin_name: '', purpose: '', environment: 'staging' })

const statusLabels = { pending: 'قيد الانتظار', approved: 'موافَق عليها', rejected: 'مرفوضة' }
const statusColors = {
  pending: 'bg-amber-100 text-amber-700',
  approved: 'bg-emerald-100 text-emerald-700',
  rejected: 'bg-red-100 text-red-700',
}

async function load() {
  loading.value = true
  const { data } = await api.get(`/projects/${props.project.id}/plugin-requests`)
  requests.value = data
  loading.value = false
}

async function createRequest() {
  creating.value = true
  try {
    await api.post(`/projects/${props.project.id}/plugin-requests`, form.value)
    showCreate.value = false
    form.value = { plugin_name: '', purpose: '', environment: 'staging' }
    await load()
  } finally {
    creating.value = false
  }
}

async function decide(request, status) {
  await api.patch(`/plugin-requests/${request.id}/decide`, { status })
  await load()
}

async function removeRequest(request) {
  await api.delete(`/plugin-requests/${request.id}`)
  await load()
}

onMounted(load)
</script>

<template>
  <div>
    <div class="bg-indigo-50 text-indigo-900 text-sm rounded-xl p-4 mb-5">
      يُمنع تثبيت أو تفعيل أي إضافة جديدة قبل الرجوع إلى أخصائي تقنية المعلومات والحصول على موافقته — تنطبق القاعدة على الـ Staging والموقع الحي.
    </div>

    <div class="flex justify-end mb-4">
      <button class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg px-4 py-2" @click="showCreate = true">
        + طلب إضافة جديدة
      </button>
    </div>

    <div v-if="loading" class="text-slate-500">...جاري التحميل</div>

    <div v-else class="space-y-3">
      <div v-for="r in requests" :key="r.id" class="bg-white rounded-xl border border-slate-200 p-4">
        <div class="flex items-start justify-between gap-4">
          <div>
            <div class="font-semibold text-slate-900">{{ r.plugin_name }}</div>
            <div class="text-sm text-slate-500 mt-1">{{ r.purpose }}</div>
            <div class="text-xs text-slate-400 mt-1">
              بيئة: {{ r.environment === 'live' ? 'الموقع الحي' : 'Staging' }}
              — طلبها: {{ r.requester?.name }}
            </div>
            <div v-if="r.decision_notes" class="text-xs text-slate-500 mt-1">ملاحظات: {{ r.decision_notes }}</div>
          </div>
          <div class="text-left shrink-0 flex flex-col items-end gap-2">
            <span class="px-2.5 py-1 rounded-full text-xs font-medium" :class="statusColors[r.status]">
              {{ statusLabels[r.status] }}
            </span>
            <div v-if="r.status === 'pending' && auth.canDecidePlugins" class="flex gap-1">
              <button class="text-xs px-2 py-1 rounded bg-emerald-50 text-emerald-700 hover:bg-emerald-100" @click="decide(r, 'approved')">
                موافقة
              </button>
              <button class="text-xs px-2 py-1 rounded bg-red-50 text-red-700 hover:bg-red-100" @click="decide(r, 'rejected')">
                رفض
              </button>
            </div>
            <button v-if="auth.canManageProjects" class="text-xs text-slate-400 hover:text-red-500 hover:underline" @click="removeRequest(r)">
              حذف
            </button>
          </div>
        </div>
      </div>
      <div v-if="!requests.length" class="text-center text-slate-400 py-8">لا توجد طلبات إضافات.</div>
    </div>

    <div v-if="showCreate" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
        <h2 class="text-lg font-bold text-slate-900 mb-4">طلب إضافة جديدة</h2>
        <form class="space-y-4" @submit.prevent="createRequest">
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">اسم الإضافة</label>
            <input v-model="form.plugin_name" required class="w-full rounded-lg border border-slate-300 px-3 py-2" />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">الغرض من الإضافة</label>
            <textarea v-model="form.purpose" required rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2"></textarea>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">البيئة</label>
            <select v-model="form.environment" class="w-full rounded-lg border border-slate-300 px-3 py-2">
              <option value="staging">Staging</option>
              <option value="live">الموقع الحي</option>
            </select>
          </div>
          <div class="flex gap-3 justify-end pt-2">
            <button type="button" class="px-4 py-2 text-sm rounded-lg text-slate-600 hover:bg-slate-100" @click="showCreate = false">
              إلغاء
            </button>
            <button type="submit" :disabled="creating" class="px-4 py-2 text-sm rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-medium disabled:opacity-60">
              {{ creating ? '...جاري الإرسال' : 'إرسال الطلب' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
