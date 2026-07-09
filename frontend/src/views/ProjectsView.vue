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
const showForm = ref(false)
const editingProject = ref(null)
const saving = ref(false)
const error = ref('')
const deleting = ref(null)

const emptyForm = () => ({
  name: '',
  client_name: '',
  envato_preview_url: '',
  site_url: '',
  content_deadline: '',
  developer_ids: [],
})

const form = ref(emptyForm())

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

function openCreate() {
  editingProject.value = null
  form.value = emptyForm()
  error.value = ''
  showForm.value = true
  loadUsers()
}

function openEdit(project) {
  editingProject.value = project
  form.value = {
    name: project.name,
    client_name: project.client_name,
    envato_preview_url: project.envato_preview_url || '',
    site_url: project.site_url || '',
    content_deadline: project.content_deadline || '',
    developer_ids: (project.developers || []).map((d) => d.id),
  }
  error.value = ''
  showForm.value = true
  loadUsers()
}

async function submitForm() {
  error.value = ''
  saving.value = true
  try {
    if (editingProject.value) {
      await api.patch(`/projects/${editingProject.value.id}`, form.value)
      showForm.value = false
      await loadProjects()
    } else {
      const { data } = await api.post('/projects', form.value)
      showForm.value = false
      router.push({ name: 'project-detail', params: { id: data.id } })
    }
  } catch (e) {
    error.value = e.response?.data?.message || 'حدث خطأ أثناء الحفظ.'
  } finally {
    saving.value = false
  }
}

async function confirmDelete(project) {
  deleting.value = project
}

async function deleteProject() {
  const project = deleting.value
  try {
    await api.delete(`/projects/${project.id}`)
    deleting.value = null
    await loadProjects()
  } catch (e) {
    deleting.value = null
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
        @click="openCreate"
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
            <th v-if="auth.isAdmin" class="text-right px-4 py-3 font-medium">إجراءات</th>
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
            <td v-if="auth.isAdmin" class="px-4 py-3" @click.stop>
              <div class="flex gap-3 text-xs">
                <button class="text-indigo-600 hover:underline" @click="openEdit(p)">تعديل</button>
                <button class="text-red-500 hover:underline" @click="confirmDelete(p)">حذف</button>
              </div>
            </td>
          </tr>
          <tr v-if="!projects.length">
            <td colspan="6" class="px-4 py-8 text-center text-slate-400">لا توجد مشاريع بعد.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Create/Edit modal -->
    <div v-if="showForm" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg p-6">
        <h2 class="text-lg font-bold text-slate-900 mb-4">
          {{ editingProject ? 'تعديل المشروع' : 'مشروع جديد' }}
        </h2>
        <form class="space-y-4" @submit.prevent="submitForm">
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
            <label class="block text-sm font-medium text-slate-700 mb-1">
              رابط الموقع المباشر
              <span class="text-slate-400 font-normal">(لفحص PageSpeed)</span>
            </label>
            <input v-model="form.site_url" type="url" placeholder="https://example.com" class="w-full rounded-lg border border-slate-300 px-3 py-2" />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">مهلة تسليم المحتوى</label>
            <input v-model="form.content_deadline" type="date" class="w-full rounded-lg border border-slate-300 px-3 py-2" />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">المبرمجون المكلّفون</label>
            <div class="w-full rounded-lg border border-slate-300 divide-y divide-slate-100 max-h-40 overflow-y-auto">
              <label
                v-for="u in users"
                :key="u.id"
                class="flex items-center gap-2 px-3 py-2 text-sm cursor-pointer hover:bg-slate-50"
              >
                <input type="checkbox" :value="u.id" v-model="form.developer_ids" class="rounded border-slate-300" />
                {{ u.name }}
                <span class="text-slate-400 text-xs">({{ u.email }})</span>
              </label>
              <p v-if="!users.length" class="px-3 py-3 text-sm text-slate-400">
                لا يوجد مبرمجون بعد — أضفهم من صفحة "فريق العمل" أولاً.
              </p>
            </div>
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
              {{ saving ? '...جاري الحفظ' : editingProject ? 'حفظ التعديلات' : 'إنشاء المشروع' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Delete confirm -->
    <div v-if="deleting" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6">
        <h2 class="text-lg font-bold text-slate-900 mb-2">حذف المشروع</h2>
        <p class="text-sm text-slate-600 mb-5">
          سيتم حذف مشروع "<strong>{{ deleting.name }}</strong>" وكل بياناته (قائمة التحقق، الطلبات، التراخيص، التقارير) نهائياً. هل أنت متأكد؟
        </p>
        <div class="flex gap-3 justify-end">
          <button class="px-4 py-2 text-sm rounded-lg text-slate-600 hover:bg-slate-100" @click="deleting = null">إلغاء</button>
          <button class="px-4 py-2 text-sm rounded-lg bg-red-600 hover:bg-red-700 text-white font-medium" @click="deleteProject">
            حذف نهائياً
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
