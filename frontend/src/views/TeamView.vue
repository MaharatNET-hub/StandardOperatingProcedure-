<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '../lib/api'
import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()
const users = ref([])
const roles = ref([])
const loading = ref(true)
const showForm = ref(false)
const editingUser = ref(null)
const saving = ref(false)
const error = ref('')
const deleting = ref(null)
const deleteError = ref('')

const emptyForm = () => ({ name: '', email: '', password: '', role: 'developer' })
const form = ref(emptyForm())

const roleLabels = computed(() => Object.fromEntries(roles.value.map((r) => [r.key, r.label_ar])))

async function load() {
  loading.value = true
  const [usersRes, rolesRes] = await Promise.all([api.get('/users'), api.get('/roles')])
  users.value = usersRes.data
  roles.value = rolesRes.data
  loading.value = false
}

function openCreate() {
  editingUser.value = null
  form.value = emptyForm()
  error.value = ''
  showForm.value = true
}

function openEdit(user) {
  editingUser.value = user
  form.value = { name: user.name, email: user.email, password: '', role: user.role }
  error.value = ''
  showForm.value = true
}

async function submitForm() {
  error.value = ''
  saving.value = true
  try {
    if (editingUser.value) {
      const payload = { ...form.value }
      if (!payload.password) delete payload.password
      await api.patch(`/users/${editingUser.value.id}`, payload)
    } else {
      await api.post('/users', form.value)
    }
    showForm.value = false
    await load()
  } catch (e) {
    error.value = e.response?.data?.message || 'حدث خطأ.'
  } finally {
    saving.value = false
  }
}

function confirmDelete(user) {
  deleteError.value = ''
  deleting.value = user
}

async function deleteUser() {
  try {
    await api.delete(`/users/${deleting.value.id}`)
    deleting.value = null
    await load()
  } catch (e) {
    deleteError.value = e.response?.data?.message || 'تعذر حذف العضو.'
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
        @click="openCreate"
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
            <th class="text-right px-4 py-3 font-medium">إجراءات</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="u in users" :key="u.id" class="border-t border-slate-100">
            <td class="px-4 py-3 font-medium text-slate-900">{{ u.name }}</td>
            <td class="px-4 py-3 text-slate-600">{{ u.email }}</td>
            <td class="px-4 py-3 text-slate-600">{{ roleLabels[u.role] || u.role }}</td>
            <td class="px-4 py-3">
              <div class="flex gap-3 text-xs">
                <button class="text-indigo-600 hover:underline" @click="openEdit(u)">تعديل</button>
                <button
                  v-if="u.id !== auth.user?.id"
                  class="text-red-500 hover:underline"
                  @click="confirmDelete(u)"
                >
                  حذف
                </button>
                <span v-else class="text-slate-300">(حسابك)</span>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="showForm" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
        <h2 class="text-lg font-bold text-slate-900 mb-4">{{ editingUser ? 'تعديل العضو' : 'عضو جديد' }}</h2>
        <form class="space-y-4" @submit.prevent="submitForm">
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">الاسم</label>
            <input v-model="form.name" required class="w-full rounded-lg border border-slate-300 px-3 py-2" />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">البريد الإلكتروني</label>
            <input v-model="form.email" type="email" required class="w-full rounded-lg border border-slate-300 px-3 py-2" />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">
              كلمة المرور
              <span v-if="editingUser" class="text-slate-400 font-normal">(اتركها فارغة لعدم التغيير)</span>
            </label>
            <input
              v-model="form.password"
              type="password"
              :required="!editingUser"
              minlength="8"
              class="w-full rounded-lg border border-slate-300 px-3 py-2"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">الدور</label>
            <select v-model="form.role" class="w-full rounded-lg border border-slate-300 px-3 py-2">
              <option v-for="r in roles" :key="r.key" :value="r.key">{{ r.label_ar }}</option>
            </select>
            <router-link :to="{ name: 'roles' }" v-if="auth.canManageRoles" class="text-xs text-indigo-600 hover:underline mt-1 inline-block">
              إدارة الأدوار والصلاحيات
            </router-link>
          </div>

          <p v-if="error" class="text-sm text-red-600">{{ error }}</p>

          <div class="flex gap-3 justify-end pt-2">
            <button type="button" class="px-4 py-2 text-sm rounded-lg text-slate-600 hover:bg-slate-100" @click="showForm = false">
              إلغاء
            </button>
            <button
              type="submit"
              :disabled="saving"
              class="px-4 py-2 text-sm rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-medium disabled:opacity-60"
            >
              {{ saving ? '...جاري الحفظ' : 'حفظ' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <div v-if="deleting" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6">
        <h2 class="text-lg font-bold text-slate-900 mb-2">حذف العضو</h2>
        <p class="text-sm text-slate-600 mb-3">
          سيتم حذف حساب "<strong>{{ deleting.name }}</strong>" نهائياً. مشاريعه وسجلاته السابقة تبقى محفوظة.
        </p>
        <p v-if="deleteError" class="text-sm text-red-600 mb-3">{{ deleteError }}</p>
        <div class="flex gap-3 justify-end">
          <button class="px-4 py-2 text-sm rounded-lg text-slate-600 hover:bg-slate-100" @click="deleting = null">إلغاء</button>
          <button class="px-4 py-2 text-sm rounded-lg bg-red-600 hover:bg-red-700 text-white font-medium" @click="deleteUser">
            حذف نهائياً
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
