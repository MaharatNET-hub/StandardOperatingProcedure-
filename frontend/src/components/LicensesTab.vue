<script setup>
import { ref, onMounted } from 'vue'
import api from '../lib/api'
import { useAuthStore } from '../stores/auth'

const props = defineProps({ project: Object })
const auth = useAuthStore()

const licenses = ref([])
const users = ref([])
const loading = ref(true)
const showCreate = ref(false)
const creating = ref(false)
const form = ref({ type: 'elementor_pro', registered_email: '', expiry_date: '', renewal_responsible_id: '', notes: '' })

const typeLabels = { elementor_pro: 'Elementor Pro', astra_pro: 'Astra Pro', other: 'أخرى' }

async function load() {
  loading.value = true
  const [licRes, usersRes] = await Promise.all([
    api.get(`/projects/${props.project.id}/licenses`),
    auth.canManageProjects ? api.get('/users') : Promise.resolve({ data: [] }),
  ])
  licenses.value = licRes.data
  users.value = usersRes.data
  loading.value = false
}

async function createLicense() {
  creating.value = true
  try {
    await api.post(`/projects/${props.project.id}/licenses`, form.value)
    showCreate.value = false
    form.value = { type: 'elementor_pro', registered_email: '', expiry_date: '', renewal_responsible_id: '', notes: '' }
    await load()
  } finally {
    creating.value = false
  }
}

async function remove(license) {
  await api.delete(`/projects/${props.project.id}/licenses/${license.id}`)
  await load()
}

function isExpiringSoon(date) {
  if (!date) return false
  const days = (new Date(date) - new Date()) / (1000 * 60 * 60 * 24)
  return days < 30
}

onMounted(load)
</script>

<template>
  <div>
    <div class="bg-indigo-50 text-indigo-900 text-sm rounded-xl p-4 mb-5">
      تراخيص Elementor Pro وAstra Pro من نوع Agency/Unlimited ومسجّلة باسم الشركة وبريدها الرسمي — ليس باسم أي موظف. تحديد مسؤولية التجديد بعد التسليم إلزامي في العقد.
    </div>

    <div class="flex justify-end mb-4">
      <button class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg px-4 py-2" @click="showCreate = true">
        + ترخيص جديد
      </button>
    </div>

    <div v-if="loading" class="text-slate-500">...جاري التحميل</div>

    <div v-else class="space-y-3">
      <div v-for="l in licenses" :key="l.id" class="bg-white rounded-xl border border-slate-200 p-4 flex items-start justify-between gap-4">
        <div>
          <div class="font-semibold text-slate-900">{{ typeLabels[l.type] || l.type }}</div>
          <div class="text-sm text-slate-500 mt-1">{{ l.registered_email }}</div>
          <div class="text-xs mt-1" :class="isExpiringSoon(l.expiry_date) ? 'text-red-600 font-medium' : 'text-slate-400'">
            انتهاء: {{ l.expiry_date || '—' }}
            <span v-if="isExpiringSoon(l.expiry_date)">⚠ قريب من الانتهاء</span>
          </div>
          <div v-if="l.renewal_responsible" class="text-xs text-slate-400 mt-1">مسؤول التجديد: {{ l.renewal_responsible.name }}</div>
        </div>
        <button v-if="auth.canManageProjects" class="text-xs text-red-500 hover:underline shrink-0" @click="remove(l)">حذف</button>
      </div>
      <div v-if="!licenses.length" class="text-center text-slate-400 py-8">لا توجد تراخيص مسجّلة.</div>
    </div>

    <div v-if="showCreate" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
        <h2 class="text-lg font-bold text-slate-900 mb-4">ترخيص جديد</h2>
        <form class="space-y-4" @submit.prevent="createLicense">
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">نوع الترخيص</label>
            <select v-model="form.type" class="w-full rounded-lg border border-slate-300 px-3 py-2">
              <option value="elementor_pro">Elementor Pro</option>
              <option value="astra_pro">Astra Pro</option>
              <option value="other">أخرى</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">البريد المسجّل به</label>
            <input v-model="form.registered_email" type="email" required class="w-full rounded-lg border border-slate-300 px-3 py-2" />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">تاريخ الانتهاء</label>
            <input v-model="form.expiry_date" type="date" class="w-full rounded-lg border border-slate-300 px-3 py-2" />
          </div>
          <div v-if="auth.canManageProjects">
            <label class="block text-sm font-medium text-slate-700 mb-1">مسؤول التجديد</label>
            <select v-model="form.renewal_responsible_id" class="w-full rounded-lg border border-slate-300 px-3 py-2">
              <option value="">—</option>
              <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
            </select>
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
