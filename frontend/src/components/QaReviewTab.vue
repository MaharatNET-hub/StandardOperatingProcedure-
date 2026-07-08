<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '../lib/api'
import { useAuthStore } from '../stores/auth'

const props = defineProps({ project: Object })
const emit = defineEmits(['reload'])
const auth = useAuthStore()

const review = ref(null)
const loading = ref(true)
const starting = ref(false)
const submitting = ref(false)
const signingOff = ref(false)
const overallNotes = ref('')

const signoffRoles = [
  { key: 'prepared', label: 'إعداد' },
  { key: 'reviewed', label: 'مراجعة' },
  { key: 'final_approval', label: 'اعتماد نهائي' },
]

function signoffFor(role) {
  return props.project.signoffs?.find((s) => s.role === role)
}

const groupedItems = computed(() => {
  if (!review.value) return []
  const byCategory = {}
  for (const item of review.value.items) {
    const cat = item.project_checklist_item.checklist_item.category
    byCategory[cat.code] = byCategory[cat.code] || { name_ar: cat.name_ar, items: [] }
    byCategory[cat.code].items.push(item)
  }
  return Object.values(byCategory)
})

const failCount = computed(() => review.value?.items?.filter((i) => i.verdict === 'fail').length || 0)
const pendingCount = computed(() => review.value?.items?.filter((i) => !i.verdict).length || 0)

async function loadReview() {
  loading.value = true
  const { data } = await api.get(`/projects/${props.project.id}/qa-reviews/latest`)
  review.value = data && data.id ? data : null
  loading.value = false
}

async function startReview() {
  starting.value = true
  try {
    await api.post(`/projects/${props.project.id}/qa-reviews`)
    await loadReview()
    emit('reload')
  } finally {
    starting.value = false
  }
}

async function setVerdict(item, verdict) {
  const { data } = await api.patch(`/qa-reviews/${review.value.id}/items/${item.id}`, { verdict })
  const idx = review.value.items.findIndex((i) => i.id === item.id)
  review.value.items[idx] = { ...review.value.items[idx], ...data }
}

async function saveComment(item) {
  await api.patch(`/qa-reviews/${review.value.id}/items/${item.id}`, {
    verdict: item.verdict || 'fail',
    comment: item.comment,
  })
}

async function submitDecision(status) {
  submitting.value = true
  try {
    await api.post(`/qa-reviews/${review.value.id}/submit`, { status, overall_notes: overallNotes.value })
    await loadReview()
    emit('reload')
  } finally {
    submitting.value = false
  }
}

async function finalSignoff() {
  signingOff.value = true
  try {
    await api.post(`/projects/${props.project.id}/signoffs`)
    emit('reload')
  } finally {
    signingOff.value = false
  }
}

onMounted(loadReview)
</script>

<template>
  <div v-if="loading" class="text-slate-500">...جاري التحميل</div>

  <div v-else class="space-y-6">
    <!-- Signature table matching the SOP's final sign-off section -->
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-slate-50 text-slate-500 text-xs">
          <tr>
            <th class="text-right px-4 py-2 font-medium">الدور</th>
            <th class="text-right px-4 py-2 font-medium">الاسم</th>
            <th class="text-right px-4 py-2 font-medium">التوقيع / التاريخ</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="r in signoffRoles" :key="r.key" class="border-t border-slate-100">
            <td class="px-4 py-2.5 text-slate-600">{{ r.label }}</td>
            <td class="px-4 py-2.5 font-medium text-slate-900">{{ signoffFor(r.key)?.signature_name || '—' }}</td>
            <td class="px-4 py-2.5 text-slate-400 text-xs">
              {{ signoffFor(r.key)?.signed_at ? new Date(signoffFor(r.key).signed_at).toLocaleString('ar') : '—' }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="project.status === 'approved' && auth.isAdmin" class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 flex items-center justify-between">
      <p class="text-sm text-emerald-800">مراجعة الجودة معتمدة. يمكن الآن الاعتماد النهائي وتسليم المشروع للعميل.</p>
      <button
        :disabled="signingOff"
        class="bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg px-4 py-2 disabled:opacity-60 shrink-0"
        @click="finalSignoff"
      >
        {{ signingOff ? '...جاري الاعتماد' : 'الاعتماد النهائي والتسليم' }}
      </button>
    </div>

    <div v-if="!review && auth.canReview" class="bg-white rounded-xl border border-slate-200 p-6 text-center">
      <p class="text-slate-600 mb-4">لا توجد مراجعة جودة حالية. ابدأ مراجعة شاملة لكل بنود قائمة التحقق.</p>
      <button
        :disabled="starting"
        class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg px-5 py-2.5 disabled:opacity-60"
        @click="startReview"
      >
        {{ starting ? '...جاري البدء' : 'بدء مراجعة الجودة' }}
      </button>
    </div>
    <div v-else-if="!review" class="bg-white rounded-xl border border-slate-200 p-6 text-center text-slate-400">
      لم تبدأ مراجعة الجودة بعد لهذا المشروع.
    </div>

    <template v-else>
      <div class="bg-white rounded-xl border border-slate-200 p-4 flex flex-wrap items-center justify-between gap-3">
        <div class="text-sm">
          <span class="font-medium text-slate-900">المراجع:</span> {{ review.reviewer?.name }}
          <span class="mx-2 text-slate-300">|</span>
          <span class="font-medium text-slate-900">الحالة:</span> {{ review.status }}
          <span class="mx-2 text-slate-300">|</span>
          <span :class="failCount ? 'text-red-600 font-medium' : 'text-slate-500'">فشل: {{ failCount }}</span>
          <span class="mx-2 text-slate-300">|</span>
          <span class="text-slate-500">لم يُراجع: {{ pendingCount }}</span>
        </div>
      </div>

      <div v-for="cat in groupedItems" :key="cat.name_ar" class="bg-white rounded-xl border border-slate-200 p-4">
        <h3 class="font-semibold text-slate-900 mb-3">{{ cat.name_ar }}</h3>
        <div class="space-y-2">
          <div v-for="item in cat.items" :key="item.id" class="border border-slate-100 rounded-lg p-3">
            <p class="text-sm text-slate-800">{{ item.project_checklist_item.checklist_item.text_ar }}</p>
            <div class="text-xs text-slate-400 mt-1">
              حالة المبرمج: {{ item.project_checklist_item.status === 'done' ? '✔ منجز' : item.project_checklist_item.status === 'na' ? 'غير منطبق' : 'لم يُنجز' }}
            </div>

            <div v-if="review.status === 'in_progress' && auth.canReview" class="mt-2 flex items-center gap-2">
              <button
                class="text-xs px-2 py-1 rounded"
                :class="item.verdict === 'pass' ? 'bg-emerald-600 text-white' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100'"
                @click="setVerdict(item, 'pass')"
              >
                ناجح
              </button>
              <button
                class="text-xs px-2 py-1 rounded"
                :class="item.verdict === 'fail' ? 'bg-red-600 text-white' : 'bg-red-50 text-red-700 hover:bg-red-100'"
                @click="setVerdict(item, 'fail')"
              >
                فشل
              </button>
              <input
                v-model="item.comment"
                placeholder="ملاحظة..."
                class="flex-1 text-xs rounded border border-slate-200 px-2 py-1"
                @blur="saveComment(item)"
              />
            </div>
            <div v-else-if="item.verdict" class="mt-2 text-xs">
              <span :class="item.verdict === 'pass' ? 'text-emerald-600' : 'text-red-600'">
                {{ item.verdict === 'pass' ? 'ناجح' : 'فشل' }}
              </span>
              <span v-if="item.comment" class="text-slate-500"> — {{ item.comment }}</span>
            </div>
          </div>
        </div>
      </div>

      <div v-if="review.status === 'in_progress' && auth.canReview" class="bg-white rounded-xl border border-slate-200 p-5">
        <h3 class="font-semibold text-slate-900 mb-3">القرار النهائي للمراجعة</h3>
        <textarea
          v-model="overallNotes"
          placeholder="ملاحظات عامة..."
          rows="3"
          class="w-full rounded-lg border border-slate-300 px-3 py-2 mb-3"
        ></textarea>
        <div class="flex flex-wrap gap-2">
          <button
            :disabled="submitting"
            class="bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg px-4 py-2 disabled:opacity-60"
            @click="submitDecision('approved')"
          >
            اعتماد المشروع
          </button>
          <button
            :disabled="submitting"
            class="bg-amber-500 hover:bg-amber-600 text-white text-sm font-medium rounded-lg px-4 py-2 disabled:opacity-60"
            @click="submitDecision('changes_requested')"
          >
            طلب تعديلات
          </button>
          <button
            :disabled="submitting"
            class="bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg px-4 py-2 disabled:opacity-60"
            @click="submitDecision('rejected')"
          >
            رفض
          </button>
        </div>
      </div>
    </template>
  </div>
</template>
