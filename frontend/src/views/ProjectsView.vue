<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '../lib/api'
import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()
const router = useRouter()

const projects = ref([])
const users = ref([])
const loading = ref(true)
const showCreate = ref(false)
const creating = ref(false)
const error = ref('')

const form = ref({
  name: '',
  client_name: '',
  envato_preview_url: '',
  content_deadline: '',
  developer_ids: [],
})

const statusLabels = {
  in_progress: 'قيد التنفيذ',
  in_review: 'قيد مراجعة الجودة',
  changes_requested: 'طلب تعديلات',
  approved: 'معتمد',
  delivered: 'تم التسليم',
}

const statusColors = {
  in_progress: 'bg-slate-100 text-slate-700',
  in_review: 'bg-amber-100 text-amber-700',
  changes_requested: 'bg-red-100 text-red-700',
  approved: 'bg-emerald-100 text-emerald-700',
  delivered: 'bg-indigo-100 text-indigo-700',
}

async function loadProjects() {
  loading.value = true
  const { data } = await api.get('/projects')
  projects.value = data.data
  loading.value = false
}

async function loadUsers() {
  const { data } = await api.get('/users')
  users.value = data.filter((u) => u.role === 'developer')
}

async function createProject() {
  error.value = ''
  creating.value = true
  try {
    const { data } = await api.post('/projects', form.value)
    showCreate.value = false
    router.push({ name: 'project-detail', params: { id: data.id } })
  } catch (e) {
    error.value = e.response?.data?.message || 'حدث خطأ أثناء إنشاء المشروع.'
  } finally {
    creating.value = false
  }
}

onMounted(() => {
  loadProjects()
  if (auth.isAdmin) loadUsers()
})
</script>

<template>
  <div class="p-6 md:p-8 max-w-7xl mx-auto">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-slate-900">المشاريع</h1>
      <button
        v-if="auth.isAdmin"
        class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg px-4 py-2 transition"
        @click="showCreate = true"
      >
        + مشروع جديد
      </button>
    </div>

    <div v-if="loading" class="text-slate-500">...جاري التحميل</div>

    <div v-else class="bg-white rounded-xl border border-slate-200 overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-slate-50 text-slate-500 text-xs">
          <tr>
            <th class="text-right px-4 py-3 font-medium">المشروع</th>
            <th class="text-right px-4 py-3 font-medium">العميل</th>
            <th class="text-right px-4 py-3 font-medium">المرحلة الحالية</th>
            <th class="text-right px-4 py-3 font-medium">التحقق</th>
            <th class="text-right px-4 py-3 font-medium">الحالة</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="p in projects"
            :key="p.id"
            class="border-t border-slate-100 hover:bg-slate-50 cursor-pointer"
            @click="router.push({ name: 'project-detail', params: { id: p.id } })"
          >
            <td class="px-4 py-3 font-medium text-slate-900">{{ p.name }}</td>
            <td class="px-4 py-3 text-slate-600">{{ p.client_name }}</td>
            <td class="px-4 py-3 text-slate-600">{{ p.current_phase?.name_ar || '—' }}</td>
            <td class="px-4 py-3 text-slate-600">
              {{ p.checklist_done }} / {{ p.checklist_total }}
            </td>
            <td class="px-4 py-3">
              <span class="px-2 py-1 rounded-full text-xs font-medium" :class="statusColors[p.status]">
                {{ statusLabels[p.status] || p.status }}
              </span>
            </td>
          </tr>
          <tr v-if="!projects.length">
            <td colspan="5" class="px-4 py-8 text-center text-slate-400">لا توجد مشاريع بعد.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Create modal -->
    <div v-if="showCreate" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg p-6">
        <h2 class="text-lg font-bold text-slate-900 mb-4">مشروع جديد</h2>
        <form class="space-y-4" @submit.prevent="createProject">
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">اسم المشروع</label>
            <input v-model="form.name" required class="w-full rounded-lg border border-slate-300 px-3 py-2" />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">اسم العميل</label>
            <input v-model="form.client_name" required class="w-full rounded-lg border border-slate-300 px-3 py-2" />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">رابط Live Preview (Envato)</label>
            <input v-model="form.envato_preview_url" type="url" class="w-full rounded-lg border border-slate-300 px-3 py-2" />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">مهلة تسليم المحتوى</label>
            <input v-model="form.content_deadline" type="date" class="w-full rounded-lg border border-slate-300 px-3 py-2" />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">المبرمجون المكلّفون</label>
            <select v-model="form.developer_ids" multiple class="w-full rounded-lg border border-slate-300 px-3 py-2 h-28">
              <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
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
              {{ creating ? '...جاري الإنشاء' : 'إنشاء المشروع' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
