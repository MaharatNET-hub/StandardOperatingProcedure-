<script setup>
import { ref, computed } from 'vue'
import api from '../lib/api'
import { priorityLabels, formatDate } from '../lib/labels'

const props = defineProps({ project: Object })
const emit = defineEmits(['reload'])

const statusLabels = { ready: 'جاهز', in_progress: 'قيد التنفيذ', missing: 'ناقص' }
const statusColors = {
  ready: 'bg-emerald-100 text-emerald-700',
  in_progress: 'bg-amber-100 text-amber-700',
  missing: 'bg-red-100 text-red-700',
}
const statusDot = {
  ready: 'bg-emerald-500',
  in_progress: 'bg-amber-500',
  missing: 'bg-red-500',
}

// المتطلّب المفتوح للتعديل حالياً، ونموذج التعديل الخاص به
const editing = ref(null)
const saving = ref(false)
const error = ref('')
const form = ref({ status: 'ready', effective_date: '', note: '' })

// نسخة محلية من الجاهزية تُحدَّث فور الحفظ قبل إعادة تحميل المشروع
const readiness = ref(null)
const current = computed(() => readiness.value || props.project.readiness)

const today = new Date().toISOString().slice(0, 10)

function open(item) {
  editing.value = item.key
  error.value = ''
  form.value = {
    status: item.status === 'ready' ? 'missing' : 'ready',
    effective_date: item.since || today,
    note: '',
  }
}

function close() {
  editing.value = null
  error.value = ''
}

async function save(item) {
  saving.value = true
  error.value = ''
  try {
    const { data } = await api.patch(`/projects/${props.project.id}/requirements/${item.key}`, {
      status: form.value.status,
      effective_date: form.value.effective_date || null,
      note: form.value.note || null,
    })
    readiness.value = data.readiness
    editing.value = null
    // يُعيد حساب الأولوية وشريط الجاهزية في أعلى الصفحة
    emit('reload')
  } catch (e) {
    error.value = e.response?.data?.message || 'تعذر حفظ التغيير.'
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div>
    <div v-if="!project.project_type" class="bg-amber-50 text-amber-900 text-sm rounded-xl p-4 mb-5">
      حدد "نوع المشروع" من تبويب "تفاصيل إضافية" ليتم فحص المتطلبات المناسبة له بدقة.
    </div>

    <div v-if="project.priority?.reasons?.length" class="bg-white rounded-xl border border-slate-200 p-5 mb-6">
      <h2 class="font-semibold text-slate-900 mb-3">
        لماذا أولوية هذا المشروع {{ priorityLabels[project.priority.level] }}؟
      </h2>
      <ul class="space-y-1.5">
        <li v-for="(r, i) in project.priority.reasons" :key="i" class="flex justify-between text-sm">
          <span class="text-slate-600">{{ r.label }}</span>
          <span class="font-medium text-slate-900">+{{ r.points }}</span>
        </li>
      </ul>
      <div class="flex justify-between text-sm font-bold text-slate-900 mt-3 pt-3 border-t border-slate-100">
        <span>الإجمالي</span>
        <span>{{ project.priority.score }} نقطة</span>
      </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 p-5">
      <div class="flex items-center justify-between mb-1">
        <h2 class="font-semibold text-slate-900">المتطلبات</h2>
        <span
          class="text-sm font-bold"
          :class="current?.percent >= 80 ? 'text-emerald-600' : current?.percent >= 40 ? 'text-amber-600' : 'text-red-600'"
        >
          {{ current?.percent ?? 0 }}% جاهز
        </span>
      </div>
      <p class="text-xs text-slate-500 mb-4">
        اضغط "تغيير الحالة" لتسجيل أن المتطلّب صار جاهزاً — مع تاريخ الجاهزية الفعلي ولو كان قبل اليوم.
      </p>

      <div class="space-y-2">
        <div
          v-for="item in current?.items || []"
          :key="item.key"
          class="border border-slate-100 rounded-lg px-3 py-2.5"
        >
          <div class="flex items-center justify-between gap-3 flex-wrap">
            <div class="flex items-center gap-2 min-w-0">
              <span class="w-2 h-2 rounded-full shrink-0" :class="statusDot[item.status]"></span>
              <span class="text-sm font-medium text-slate-800">{{ item.label }}</span>
              <span v-if="item.since" class="text-xs text-emerald-600">جاهز منذ {{ formatDate(item.since) }}</span>
            </div>
            <div class="flex items-center gap-2">
              <span v-if="item.message" class="text-xs text-slate-400">{{ item.message }}</span>
              <span class="px-2 py-0.5 rounded-full text-xs font-medium" :class="statusColors[item.status]">
                {{ statusLabels[item.status] }}
              </span>
              <button
                v-if="editing !== item.key"
                class="text-xs text-indigo-600 hover:underline"
                @click="open(item)"
              >
                تغيير الحالة
              </button>
            </div>
          </div>

          <div v-if="item.note && editing !== item.key" class="text-xs text-slate-400 mt-1">
            {{ item.note }}<span v-if="item.updated_by"> — {{ item.updated_by }}</span>
          </div>

          <!-- نموذج تغيير الحالة -->
          <div v-if="editing === item.key" class="mt-3 pt-3 border-t border-slate-100">
            <div class="grid sm:grid-cols-3 gap-3">
              <div>
                <label class="block text-xs text-slate-500 mb-1">الحالة الجديدة</label>
                <select v-model="form.status" class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-sm">
                  <option value="ready">جاهز</option>
                  <option value="in_progress">قيد التنفيذ</option>
                  <option value="missing">ناقص</option>
                </select>
              </div>
              <div>
                <label class="block text-xs text-slate-500 mb-1">تاريخ السريان</label>
                <input
                  v-model="form.effective_date"
                  type="date"
                  :max="today"
                  class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-sm"
                />
              </div>
              <div>
                <label class="block text-xs text-slate-500 mb-1">ملاحظة (اختياري)</label>
                <input
                  v-model="form.note"
                  type="text"
                  placeholder="استلمنا الشعار من العميل"
                  class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-sm"
                />
              </div>
            </div>

            <p v-if="error" class="text-xs text-red-600 mt-2">{{ error }}</p>

            <div class="flex gap-2 justify-end mt-3">
              <button class="px-3 py-1.5 text-xs rounded-lg text-slate-600 hover:bg-slate-100" @click="close">
                إلغاء
              </button>
              <button
                :disabled="saving"
                class="px-3 py-1.5 text-xs rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-medium disabled:opacity-60"
                @click="save(item)"
              >
                {{ saving ? '...جاري الحفظ' : 'حفظ' }}
              </button>
            </div>
          </div>
        </div>

        <p v-if="!current?.items?.length" class="text-sm text-slate-400 text-center py-4">
          لا توجد متطلبات محددة لهذا النوع من المشاريع.
        </p>
      </div>
    </div>
  </div>
</template>
