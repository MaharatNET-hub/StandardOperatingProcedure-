<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import api from '../lib/api'

const router = useRouter()

const quotations = ref([])
const loading = ref(true)
const deleting = ref(null)
const filters = ref({ search: '', status: '' })
let searchDebounce = null

const statusLabels = {
  draft: 'مسودة',
  sent: 'مُرسل للعميل',
  accepted: 'مقبول',
  rejected: 'مرفوض',
}
const statusColors = {
  draft: 'bg-slate-100 text-slate-700',
  sent: 'bg-amber-100 text-amber-700',
  accepted: 'bg-emerald-100 text-emerald-700',
  rejected: 'bg-red-100 text-red-700',
}

async function load() {
  loading.value = true
  const params = {}
  if (filters.value.search) params.search = filters.value.search
  if (filters.value.status) params.status = filters.value.status
  const { data } = await api.get('/quotations', { params })
  quotations.value = data.data
  loading.value = false
}

watch(
  () => filters.value.search,
  () => {
    clearTimeout(searchDebounce)
    searchDebounce = setTimeout(load, 350)
  },
)
watch(() => filters.value.status, load)

async function remove(quotation) {
  if (!confirm(`حذف عرض السعر الخاص بـ "${quotation.url}"؟`)) return
  deleting.value = quotation.id
  try {
    await api.delete(`/quotations/${quotation.id}`)
    await load()
  } finally {
    deleting.value = null
  }
}

onMounted(load)
</script>

<template>
  <div class="p-6 md:p-8 max-w-6xl mx-auto">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">التحليل الفني وعروض الأسعار</h1>
        <p class="text-sm text-slate-500 mt-1">افحص أي رابط تقنياً، وأصدر عرض سعر رسمي جاهز للعميل بأسلوب Maharat Net.</p>
      </div>
      <button
        class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg px-4 py-2"
        @click="router.push({ name: 'quotation-new' })"
      >
        + تحليل موقع جديد
      </button>
    </div>

    <div class="flex flex-wrap gap-3 mb-5">
      <input
        v-model="filters.search"
        type="text"
        placeholder="ابحث بالرابط أو اسم العميل..."
        class="rounded-lg border border-slate-300 px-3 py-2 text-sm w-64"
      />
      <select v-model="filters.status" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
        <option value="">كل الحالات</option>
        <option v-for="(label, key) in statusLabels" :key="key" :value="key">{{ label }}</option>
      </select>
    </div>

    <div v-if="loading" class="text-slate-500">...جاري التحميل</div>

    <div v-else-if="!quotations.length" class="text-center text-slate-400 py-12 bg-white rounded-xl border border-slate-200">
      لا توجد عروض أسعار بعد — ابدأ بتحليل رابط موقع.
    </div>

    <div v-else class="bg-white rounded-xl border border-slate-200 overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-slate-50 text-xs text-slate-500">
          <tr>
            <th class="text-right p-3">الرابط</th>
            <th class="text-right p-3">العميل</th>
            <th class="text-right p-3">التوصية الفنية</th>
            <th class="text-right p-3">الحالة</th>
            <th class="text-right p-3">تاريخ الإنشاء</th>
            <th class="p-3"></th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="q in quotations"
            :key="q.id"
            class="border-t border-slate-100 hover:bg-slate-50 cursor-pointer"
            @click="router.push({ name: 'quotation-edit', params: { id: q.id } })"
          >
            <td class="p-3 text-slate-800 break-all">{{ q.url }}</td>
            <td class="p-3 text-slate-600">{{ q.client_name || '—' }}</td>
            <td class="p-3 text-slate-600">{{ q.recommended_platform || '—' }}</td>
            <td class="p-3">
              <span class="px-2.5 py-1 rounded-full text-xs font-medium" :class="statusColors[q.status]">
                {{ statusLabels[q.status] || q.status }}
              </span>
            </td>
            <td class="p-3 text-xs text-slate-400">{{ new Date(q.created_at).toLocaleDateString('ar') }}</td>
            <td class="p-3 text-left" @click.stop>
              <button
                class="text-xs text-red-600 hover:underline disabled:opacity-40"
                :disabled="deleting === q.id"
                @click="remove(q)"
              >
                حذف
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
