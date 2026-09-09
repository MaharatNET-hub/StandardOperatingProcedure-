<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import api from '../lib/api'
import { useAuthStore } from '../stores/auth'
import {
  projectTypeLabels,
  blockerLabels,
  priorityLabels,
  priorityColors,
  pipelineStageLabels,
  noteTypeLabels,
  formatDate,
} from '../lib/labels'

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

const filters = ref({ search: '', status: '', developer_id: '', blocker: '', missing_requirements: false, waiting_for_client: false })
let searchDebounce = null

const emptyForm = () => ({
  name: '',
  client_name: '',
  project_type: '',
  envato_preview_url: '',
  site_url: '',
  content_deadline: '',
  primary_developer_id: '',
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

// أيام حتى التسليم: موجب = متبقٍّ، سالب = تأخّر
function daysToDeadline(project) {
  if (!project.content_deadline) return null
  const due = new Date(project.content_deadline)
  const today = new Date()
  due.setHours(0, 0, 0, 0)
  today.setHours(0, 0, 0, 0)
  return Math.round((due - today) / 86400000)
}

function deadlineText(project) {
  const days = daysToDeadline(project)
  if (days === null) return 'بلا موعد تسليم'
  if (days < 0) return `متأخر ${Math.abs(days)} يوم`
  if (days === 0) return 'التسليم اليوم'
  if (days === 1) return 'يوم واحد للتسليم'
  return `${days} يوم للتسليم`
}

function deadlineTone(project) {
  const days = daysToDeadline(project)
  if (days === null) return 'bg-slate-100 text-slate-500'
  if (days < 0) return 'bg-red-100 text-red-700'
  if (days <= 3) return 'bg-amber-100 text-amber-700'
  if (days <= 7) return 'bg-sky-100 text-sky-700'
  return 'bg-emerald-100 text-emerald-700'
}

function checklistPercent(project) {
  if (!project.checklist_total) return 0
  return Math.round((project.checklist_done / project.checklist_total) * 100)
}

function barTone(percent) {
  if (percent >= 80) return 'bg-emerald-500'
  if (percent >= 40) return 'bg-amber-500'
  return 'bg-red-500'
}

async function loadProjects() {
  loading.value = true
  const params = {}
  if (filters.value.search) params.search = filters.value.search
  if (filters.value.status) params.status = filters.value.status
  if (filters.value.developer_id) params.developer_id = filters.value.developer_id
  if (filters.value.blocker) params.blocker = filters.value.blocker
  if (filters.value.missing_requirements) params.missing_requirements = 1
  if (filters.value.waiting_for_client) params.waiting_for_client = 1
  const { data } = await api.get('/projects', { params })
  projects.value = data.data
  loading.value = false
}

watch(
  () => filters.value.search,
  () => {
    clearTimeout(searchDebounce)
    searchDebounce = setTimeout(loadProjects, 350)
  },
)
watch(
  [() => filters.value.status, () => filters.value.developer_id, () => filters.value.blocker, () => filters.value.missing_requirements, () => filters.value.waiting_for_client],
  loadProjects,
)

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
    project_type: project.project_type || '',
    envato_preview_url: project.envato_preview_url || '',
    site_url: project.site_url || '',
    content_deadline: project.content_deadline ? project.content_deadline.slice(0, 10) : '',
    primary_developer_id: project.primary_developer_id || '',
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
  if (auth.canManageProjects) loadUsers()
})
</script>

<template>
  <div class="p-6 md:p-8 max-w-7xl mx-auto">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-slate-900">المشاريع</h1>
      <button
        v-if="auth.canManageProjects"
        class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg px-4 py-2 transition"
        @click="openCreate"
      >
        + مشروع جديد
      </button>
    </div>

    <div class="flex flex-wrap gap-3 mb-4">
      <input
        v-model="filters.search"
        type="text"
        placeholder="بحث باسم المشروع أو العميل..."
        class="flex-1 min-w-[200px] rounded-lg border border-slate-300 px-3 py-2 text-sm"
      />
      <select v-model="filters.status" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
        <option value="">كل الحالات</option>
        <option value="in_progress">قيد التنفيذ</option>
        <option value="in_review">قيد مراجعة الجودة</option>
        <option value="changes_requested">طلب تعديلات</option>
        <option value="approved">معتمد</option>
        <option value="delivered">تم التسليم</option>
      </select>
      <select v-if="auth.canManageProjects" v-model="filters.developer_id" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
        <option value="">كل المبرمجين</option>
        <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
      </select>
      <select v-model="filters.blocker" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
        <option value="">كل حالات التوقف</option>
        <option v-for="(label, key) in blockerLabels" :key="key" :value="key" :disabled="key === 'none'">{{ label }}</option>
      </select>
      <label class="flex items-center gap-1.5 text-sm text-slate-600 px-1">
        <input type="checkbox" v-model="filters.missing_requirements" class="rounded border-slate-300" />
        متطلبات ناقصة
      </label>
      <label class="flex items-center gap-1.5 text-sm text-slate-600 px-1">
        <input type="checkbox" v-model="filters.waiting_for_client" class="rounded border-slate-300" />
        بانتظار العميل
      </label>
    </div>

    <div v-if="loading" class="text-slate-500">...جاري التحميل</div>

    <div v-else-if="!projects.length" class="text-center text-slate-400 py-16 bg-white rounded-xl border border-slate-200">
      لا توجد مشاريع مطابقة.
    </div>

    <!-- بطاقات المشاريع: ما المتبقي، كم بقي للتسليم، وأين وصل التنفيذ -->
    <div v-else class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
      <article
        v-for="p in projects"
        :key="p.id"
        class="group bg-white rounded-2xl border border-slate-200 hover:border-indigo-300 hover:shadow-md transition cursor-pointer flex flex-col overflow-hidden"
        @click="router.push({ name: 'project-detail', params: { id: p.id } })"
      >
        <!-- شريط علوي بلون الأولوية -->
        <div class="h-1" :class="{
          'bg-red-500': p.priority?.level === 'critical',
          'bg-orange-400': p.priority?.level === 'high',
          'bg-amber-400': p.priority?.level === 'medium',
          'bg-slate-200': !p.priority || p.priority.level === 'low',
        }"></div>

        <div class="p-4 flex-1 flex flex-col gap-3">
          <!-- الاسم والعميل والأولوية -->
          <div class="flex items-start justify-between gap-2">
            <div class="min-w-0">
              <h3 class="font-bold text-slate-900 truncate group-hover:text-indigo-600">{{ p.name }}</h3>
              <p class="text-xs text-slate-500 truncate">
                {{ p.client_name }}
                <span v-if="p.project_type"> · {{ projectTypeLabels[p.project_type] }}</span>
              </p>
            </div>
            <span
              v-if="p.priority"
              class="shrink-0 px-2 py-0.5 rounded-full text-xs font-bold"
              :class="priorityColors[p.priority.level]"
              :title="p.priority.summary || 'لا توجد عوامل ترفع الأولوية حالياً'"
            >
              {{ priorityLabels[p.priority.level] }} · {{ p.priority.score }}
            </span>
          </div>

          <!-- شارات: التسليم، المرحلة، التوقف -->
          <div class="flex flex-wrap gap-1.5">
            <span class="px-2 py-0.5 rounded-full text-xs font-medium" :class="deadlineTone(p)">
              {{ deadlineText(p) }}
            </span>
            <span v-if="p.pipeline_stage" class="px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600">
              {{ pipelineStageLabels[p.pipeline_stage] }}
            </span>
            <span
              v-if="p.blocker && p.blocker !== 'none'"
              class="px-2 py-0.5 rounded-full text-xs font-medium bg-rose-50 text-rose-600"
            >
              ⏸ {{ blockerLabels[p.blocker] }}
            </span>
          </div>

          <!-- شريطا التقدّم: التنفيذ والجاهزية -->
          <div class="space-y-2">
            <div>
              <div class="flex justify-between text-xs mb-1">
                <span class="text-slate-500">نسبة التنفيذ</span>
                <span class="font-medium text-slate-700">
                  {{ checklistPercent(p) }}%
                  <span class="text-slate-400 font-normal">({{ p.checklist_done }}/{{ p.checklist_total }})</span>
                </span>
              </div>
              <div class="h-1.5 rounded-full bg-slate-100 overflow-hidden">
                <div class="h-full rounded-full transition-all" :class="barTone(checklistPercent(p))" :style="{ width: checklistPercent(p) + '%' }"></div>
              </div>
            </div>
            <div v-if="p.readiness">
              <div class="flex justify-between text-xs mb-1">
                <span class="text-slate-500">جاهزية المتطلبات</span>
                <span class="font-medium text-slate-700">{{ p.readiness.percent }}%</span>
              </div>
              <div class="h-1.5 rounded-full bg-slate-100 overflow-hidden">
                <div class="h-full rounded-full transition-all" :class="barTone(p.readiness.percent)" :style="{ width: p.readiness.percent + '%' }"></div>
              </div>
            </div>
          </div>

          <!-- المتبقي من المتطلبات -->
          <div v-if="p.readiness?.missing_count" class="bg-amber-50 rounded-lg px-3 py-2">
            <div class="text-xs font-medium text-amber-900 mb-1">
              متبقٍّ {{ p.readiness.missing_count }}:
            </div>
            <div class="flex flex-wrap gap-1">
              <span
                v-for="item in p.readiness.missing.slice(0, 4)"
                :key="item"
                class="px-1.5 py-0.5 rounded bg-white text-amber-800 text-[11px] border border-amber-200"
              >
                {{ item }}
              </span>
              <span v-if="p.readiness.missing.length > 4" class="text-[11px] text-amber-700 self-center">
                +{{ p.readiness.missing.length - 4 }}
              </span>
            </div>
          </div>
          <div v-else class="bg-emerald-50 text-emerald-700 text-xs rounded-lg px-3 py-2">
            ✓ كل المتطلبات مكتملة
          </div>

          <!-- آخر ملاحظة -->
          <div v-if="p.latest_note" class="border-s-2 border-slate-200 ps-2.5">
            <p class="text-xs text-slate-600 line-clamp-2">
              <span class="text-slate-400">{{ noteTypeLabels[p.latest_note.type] }}:</span>
              {{ p.latest_note.title }}
            </p>
            <p class="text-[11px] text-slate-400 mt-0.5">
              {{ p.latest_note.author?.name }} · {{ formatDate(p.latest_note.created_at) }}
            </p>
          </div>
        </div>

        <!-- تذييل: المبرمج والإجراءات -->
        <div class="px-4 py-2.5 bg-slate-50 border-t border-slate-100 flex items-center justify-between gap-2">
          <span class="text-xs text-slate-500 truncate">
            {{ p.primary_developer?.name || 'غير مُسند' }}
          </span>
          <div v-if="auth.canManageProjects" class="flex gap-3 text-xs shrink-0" @click.stop>
            <button class="text-indigo-600 hover:underline" @click="openEdit(p)">تعديل</button>
            <button class="text-red-500 hover:underline" @click="confirmDelete(p)">حذف</button>
          </div>
        </div>
      </article>
    </div>

    <!-- Create/Edit modal -->
    <div v-if="showForm" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-3xl max-h-[90vh] flex flex-col">
        <h2 class="text-lg font-bold text-slate-900 px-6 pt-6 pb-4 shrink-0">
          {{ editingProject ? 'تعديل المشروع' : 'مشروع جديد' }}
        </h2>
        <form class="flex flex-col min-h-0 flex-1" @submit.prevent="submitForm">
          <!-- جسم النموذج: عمودان على الشاشات المتوسطة فما فوق، ويمرّر
               داخلياً إذا لم تتّسع الشاشة بدل أن يمتدّ البوب أب خارجها -->
          <div class="grid sm:grid-cols-2 gap-4 px-6 pb-4 overflow-y-auto flex-1 min-h-0">
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">اسم المشروع</label>
              <input v-model="form.name" required class="w-full rounded-lg border border-slate-300 px-3 py-2" />
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">اسم العميل</label>
              <input v-model="form.client_name" required class="w-full rounded-lg border border-slate-300 px-3 py-2" />
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">نوع المشروع</label>
              <select v-model="form.project_type" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                <option value="">— اختر —</option>
                <option v-for="(label, key) in projectTypeLabels" :key="key" :value="key">{{ label }}</option>
              </select>
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
              <label class="block text-sm font-medium text-slate-700 mb-1">المبرمج الرئيسي (المسؤول عن المشروع)</label>
              <select v-model="form.primary_developer_id" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                <option value="">— بدون —</option>
                <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
              </select>
            </div>
            <div class="sm:col-span-2">
              <label class="block text-sm font-medium text-slate-700 mb-1">المبرمجون المساعدون</label>
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

            <p v-if="error" class="text-sm text-red-600 sm:col-span-2">{{ error }}</p>
          </div>

          <div class="flex gap-3 justify-end px-6 py-4 border-t border-slate-200 shrink-0">
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
