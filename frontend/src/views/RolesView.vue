<script setup>
import { ref, onMounted } from 'vue'
import api from '../lib/api'

const roles = ref([])
const allPermissions = ref([])
const loading = ref(true)
const showForm = ref(false)
const editingRole = ref(null)
const saving = ref(false)
const error = ref('')
const deleting = ref(null)
const deleteError = ref('')

const emptyForm = () => ({ key: '', label_ar: '', permissions: [] })
const form = ref(emptyForm())

async function load() {
  loading.value = true
  const [rolesRes, permsRes] = await Promise.all([api.get('/roles'), api.get('/permissions')])
  roles.value = rolesRes.data
  allPermissions.value = permsRes.data
  loading.value = false
}

function openCreate() {
  editingRole.value = null
  form.value = emptyForm()
  error.value = ''
  showForm.value = true
}

function openEdit(role) {
  editingRole.value = role
  form.value = { key: role.key, label_ar: role.label_ar, permissions: [...role.permissions] }
  error.value = ''
  showForm.value = true
}

async function submitForm() {
  error.value = ''
  saving.value = true
  try {
    if (editingRole.value) {
      await api.patch(`/roles/${editingRole.value.id}`, {
        label_ar: form.value.label_ar,
        permissions: form.value.permissions,
      })
    } else {
      await api.post('/roles', form.value)
    }
    showForm.value = false
    await load()
  } catch (e) {
    error.value = e.response?.data?.message || 'حدث خطأ أثناء الحفظ.'
  } finally {
    saving.value = false
  }
}

function confirmDelete(role) {
  deleteError.value = ''
  deleting.value = role
}

async function deleteRole() {
  try {
    await api.delete(`/roles/${deleting.value.id}`)
    deleting.value = null
    await load()
  } catch (e) {
    deleteError.value = e.response?.data?.message || 'تعذر حذف الدور.'
  }
}

onMounted(load)
</script>

<template>
  <div class="p-6 md:p-8 max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-2">
      <h1 class="text-2xl font-bold text-slate-900">الأدوار والصلاحيات</h1>
      <button
        class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg px-4 py-2 transition"
        @click="openCreate"
      >
        + دور جديد
      </button>
    </div>
    <p class="text-sm text-slate-500 mb-6">
      أنشئ أدواراً مخصصة (مثل "المدير التنفيذي") وحدد الصلاحيات التي يمنحها كل دور. الأدوار الأساسية الأربعة لا يمكن حذفها للحفاظ على استقرار النظام، لكن يمكنك تعديل صلاحياتها.
    </p>

    <div v-if="loading" class="text-slate-500">...جاري التحميل</div>

    <div v-else class="space-y-3">
      <div v-for="r in roles" :key="r.key" class="bg-white rounded-xl border border-slate-200 p-4">
        <div class="flex items-start justify-between gap-4">
          <div>
            <div class="flex items-center gap-2">
              <span class="font-semibold text-slate-900">{{ r.label_ar }}</span>
              <span v-if="r.is_system" class="text-xs bg-slate-100 text-slate-500 rounded-full px-2 py-0.5">أساسي</span>
              <span class="text-xs text-slate-400">{{ r.users_count }} عضو</span>
            </div>
            <div class="flex flex-wrap gap-1.5 mt-2">
              <span
                v-for="p in r.permissions"
                :key="p"
                class="text-xs bg-indigo-50 text-indigo-700 rounded-full px-2 py-0.5"
              >
                {{ allPermissions.find((ap) => ap.key === p)?.label_ar || p }}
              </span>
              <span v-if="!r.permissions.length" class="text-xs text-slate-400">لا توجد صلاحيات خاصة</span>
            </div>
          </div>
          <div class="flex gap-3 text-xs shrink-0">
            <button class="text-indigo-600 hover:underline" @click="openEdit(r)">تعديل</button>
            <button v-if="!r.is_system" class="text-red-500 hover:underline" @click="confirmDelete(r)">حذف</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Create/Edit modal -->
    <div v-if="showForm" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
        <h2 class="text-lg font-bold text-slate-900 mb-4">{{ editingRole ? 'تعديل الدور' : 'دور جديد' }}</h2>
        <form class="space-y-4" @submit.prevent="submitForm">
          <div v-if="!editingRole">
            <label class="block text-sm font-medium text-slate-700 mb-1">المفتاح (بالإنجليزية، بدون مسافات)</label>
            <input
              v-model="form.key"
              required
              pattern="[a-zA-Z0-9_\-]+"
              placeholder="ceo"
              class="w-full rounded-lg border border-slate-300 px-3 py-2"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">اسم الدور</label>
            <input v-model="form.label_ar" required placeholder="المدير التنفيذي" class="w-full rounded-lg border border-slate-300 px-3 py-2" />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-2">الصلاحيات</label>
            <div class="space-y-2 max-h-64 overflow-y-auto">
              <label
                v-for="p in allPermissions"
                :key="p.key"
                class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg border border-slate-200 cursor-pointer hover:bg-slate-50"
              >
                <input type="checkbox" :value="p.key" v-model="form.permissions" class="rounded border-slate-300" />
                {{ p.label_ar }}
              </label>
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
              {{ saving ? '...جاري الحفظ' : 'حفظ' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Delete confirm -->
    <div v-if="deleting" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6">
        <h2 class="text-lg font-bold text-slate-900 mb-2">حذف الدور</h2>
        <p class="text-sm text-slate-600 mb-3">
          سيتم حذف دور "<strong>{{ deleting.label_ar }}</strong>" نهائياً. هذا الإجراء متاح فقط إذا لم يوجد أي عضو مرتبط بهذا الدور حالياً.
        </p>
        <p v-if="deleteError" class="text-sm text-red-600 mb-3">{{ deleteError }}</p>
        <div class="flex gap-3 justify-end">
          <button class="px-4 py-2 text-sm rounded-lg text-slate-600 hover:bg-slate-100" @click="deleting = null">إلغاء</button>
          <button class="px-4 py-2 text-sm rounded-lg bg-red-600 hover:bg-red-700 text-white font-medium" @click="deleteRole">
            حذف نهائياً
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
