<script setup>
import { ref, onMounted } from 'vue'
import api from '../lib/api'

const users = ref([])
const loading = ref(true)
const showCreate = ref(false)
const creating = ref(false)
const error = ref('')

const form = ref({ name: '', email: '', password: '', role: 'developer' })

const roleLabels = {
  admin: 'مدير / CTO',
  developer: 'مبرمج',
  qa_reviewer: 'مراجع جودة (QA)',
  it_specialist: 'أخصائي تقنية معلومات',
}

async function load() {
  loading.value = true
  const { data } = await api.get('/users')
  users.value = data
  loading.value = false
}

async function createUser() {
  error.value = ''
  creating.value = true
  try {
    await api.post('/users', form.value)
    showCreate.value = false
    form.value = { name: '', email: '', password: '', role: 'developer' }
    await load()
  } catch (e) {
    error.value = e.response?.data?.message || 'حدث خطأ.'
  } finally {
    creating.value = false
  }
}

onMounted(load)
</script>

<template>
  <div class="p-6 md:p-8 max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-slate-900">فريق العمل</h1>
      <button
        class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg px-4 py-2 transition"
        @click="showCreate = true"
      >
        + إضافة عضو
      </button>
    </div>

    <div v-if="loading" class="text-slate-500">...جاري التحميل</div>

    <div v-else class="bg-white rounded-xl border border-slate-200 overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-slate-50 text-slate-500 text-xs">
          <tr>
            <th class="text-right px-4 py-3 font-medium">الاسم</th>
            <th class="text-right px-4 py-3 font-medium">البريد الإلكتروني</th>
            <th class="text-right px-4 py-3 font-medium">الدور</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="u in users" :key="u.id" class="border-t border-slate-100">
            <td class="px-4 py-3 font-medium text-slate-900">{{ u.name }}</td>
            <td class="px-4 py-3 text-slate-600">{{ u.email }}</td>
            <td class="px-4 py-3 text-slate-600">{{ roleLabels[u.role] || u.role }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="showCreate" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
        <h2 class="text-lg font-bold text-slate-900 mb-4">عضو جديد</h2>
        <form class="space-y-4" @submit.prevent="createUser">
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">الاسم</label>
            <input v-model="form.name" required class="w-full rounded-lg border border-slate-300 px-3 py-2" />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">البريد الإلكتروني</label>
            <input v-model="form.email" type="email" required class="w-full rounded-lg border border-slate-300 px-3 py-2" />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">كلمة المرور</label>
            <input v-model="form.password" type="password" required minlength="8" class="w-full rounded-lg border border-slate-300 px-3 py-2" />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">الدور</label>
            <select v-model="form.role" class="w-full rounded-lg border border-slate-300 px-3 py-2">
              <option value="developer">مبرمج</option>
              <option value="qa_reviewer">مراجع جودة (QA)</option>
              <option value="it_specialist">أخصائي تقنية معلومات</option>
              <option value="admin">مدير / CTO</option>
            </select>
          </div>

          <p v-if="error" class="text-sm text-red-600">{{ error }}</p>

          <div class="flex gap-3 justify-end pt-2">
            <button type="button" class="px-4 py-2 text-sm rounded-lg text-slate-600 hover:bg-slate-100" @click="showCreate = false">
              إلغاء
            </button>
            <button
              type="submit"
              :disabled="creating"
              class="px-4 py-2 text-sm rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-medium disabled:opacity-60"
            >
              {{ creating ? '...جاري الحفظ' : 'حفظ' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
