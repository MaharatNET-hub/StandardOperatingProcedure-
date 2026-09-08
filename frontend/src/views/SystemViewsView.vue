<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import api from '../lib/api'
import {
  pipelineStageLabels,
  blockerLabels,
  priorityLabels,
  priorityColors,
  updateStatusLabels,
  updateStatusColors,
  feedbackLabels,
  formatDate,
  formatDateTime,
} from '../lib/labels'

// لوحات النظام كما وردت في وثيقة SRS (القسم 18) — كل لوحة تُحمّل من نقطة
// النهاية الخاصة بها عند فتحها فقط.
const boards = [
  { key: 'pm-dashboard', label: 'PM Dashboard', title: 'لوحة الإدارة', hint: 'كل المشاريع مجمّعة حسب المبرمج المسؤول ومرتّبة حسب درجة الأولوية.' },
  { key: 'today-by-developer', label: 'Today — By Developer', title: 'أعمال اليوم لكل مبرمج (القابلة للتنفيذ)', hint: 'المشاريع التي يمكن للمبرمج العمل عليها فعلياً اليوم — تُستبعد المشاريع المنتظرة للعميل أو لجهة خارجية.' },
  { key: 'updates-due', label: 'Updates Due', title: 'التحديثات المستحقة (تشمل المتوقفة)', hint: 'كل المشاريع التي حان أو تأخّر موعد تحديث العميل فيها، بما فيها المشاريع المتوقفة حتى لا يُنسى التواصل.' },
  { key: 'paused-monitor', label: 'Paused Monitor', title: 'مراقبة المشاريع المتوقفة', hint: 'سبب الإيقاف، تاريخه، ومدة بقاء المشروع متوقفاً.' },
  { key: 'critical-watchlist', label: 'Critical Watchlist', title: 'قائمة المتابعة الحرجة', hint: 'المشاريع التي بلغت مستوى الأولوية "حرجة".' },
  { key: 'pipeline', label: 'Pipeline', title: 'مسار المشاريع', hint: 'لوحة تعرض المشاريع حسب مرحلة سير العمل من الإنشاء وحتى الإنجاز.' },
]

const active = ref('today-by-developer')
const loading = ref(true)
const payload = ref(null)

const activeBoard = computed(() => boards.find((b) => b.key === active.value))
const topPerDeveloper = computed(() => payload.value?.top_per_developer ?? 3)

// أعمدة المسار غير الفارغة فقط، حتى لا تمتلئ الشاشة بمراحل بلا مشاريع.
const pipelineColumns = computed(() => (payload.value?.data || []).filter((c) => c.projects.length))

async function load() {
  loading.value = true
  payload.value = null
  try {
    const { data } = await api.get(`/views/${active.value}`)
    payload.value = data
  } finally {
    loading.value = false
  }
}

function deadlineTone(row) {
  if (!row.content_deadline) return 'text-slate-400'
  const days = Math.ceil((new Date(row.content_deadline) - new Date()) / 86400000)
  if (days < 0) return 'text-red-600 font-medium'
  if (days <= 3) return 'text-amber-600 font-medium'
  return 'text-slate-500'
}

watch(active, load)
onMounted(load)
</script>

<template>
  <div class="p-6 md:p-8 max-w-7xl mx-auto">
    <h1 class="text-2xl font-bold text-slate-900 mb-1">لوحات إدارة الأولويات</h1>
    <p class="text-sm text-slate-500 mb-5">
      لوحات القرار اليومية: من يعمل على ماذا اليوم، ولماذا، وما الذي يمنع بقية المشاريع من التقدّم.
    </p>

    <div class="flex flex-wrap gap-2 mb-5">
      <button
        v-for="board in boards"
        :key="board.key"
        class="px-3 py-1.5 rounded-lg text-sm font-medium border transition"
        :class="active === board.key ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-slate-600 border-slate-300 hover:bg-slate-50'"
        @click="active = board.key"
      >
        {{ board.label }}
      </button>
    </div>

    <div class="mb-5">
      <h2 class="text-lg font-semibold text-slate-900">{{ activeBoard.title }}</h2>
      <p class="text-sm text-slate-500">{{ activeBoard.hint }}</p>
    </div>

    <div v-if="loading" class="text-slate-500">...جاري التحميل</div>

    <template v-else-if="payload">
      <!-- PM Dashboard + Today — By Developer: مجمّعة حسب المبرمج المسؤول -->
      <template v-if="active === 'pm-dashboard' || active === 'today-by-developer'">
        <div v-if="payload.totals" class="grid sm:grid-cols-4 gap-3 mb-5">
          <div class="bg-white rounded-xl border border-slate-200 p-4">
            <div class="text-xs text-slate-500">إجمالي المشاريع</div>
            <div class="text-2xl font-bold text-slate-900">{{ payload.totals.projects }}</div>
          </div>
          <div class="bg-white rounded-xl border border-slate-200 p-4">
            <div class="text-xs text-slate-500">أولوية حرجة</div>
            <div class="text-2xl font-bold text-red-600">{{ payload.totals.critical }}</div>
          </div>
          <div class="bg-white rounded-xl border border-slate-200 p-4">
            <div class="text-xs text-slate-500">قابلة للتنفيذ</div>
            <div class="text-2xl font-bold text-emerald-600">{{ payload.totals.workable }}</div>
          </div>
          <div class="bg-white rounded-xl border border-slate-200 p-4">
            <div class="text-xs text-slate-500">متوقفة / بانتظار طرف آخر</div>
            <div class="text-2xl font-bold text-amber-600">{{ payload.totals.blocked }}</div>
          </div>
        </div>

        <div v-if="!payload.data.length" class="text-center text-slate-400 py-12 bg-white rounded-xl border border-slate-200">
          لا توجد مشاريع في هذه اللوحة.
        </div>

        <div v-else class="space-y-5">
          <section v-for="group in payload.data" :key="group.developer.id || 'unassigned'" class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <header class="px-5 py-3 bg-slate-50 border-b border-slate-200 flex items-center justify-between gap-3 flex-wrap">
              <div class="font-semibold text-slate-900">{{ group.developer.name || 'غير مُسند' }}</div>
              <div class="text-xs text-slate-500">{{ group.projects.length }} مشروع · مجموع النقاط {{ group.total_score }}</div>
            </header>

            <ul class="divide-y divide-slate-100">
              <li
                v-for="(row, index) in group.projects"
                :key="row.id"
                class="px-5 py-3"
                :class="active === 'today-by-developer' && index < topPerDeveloper ? 'bg-indigo-50/40' : ''"
              >
                <div class="flex items-start justify-between gap-3 flex-wrap">
                  <div class="min-w-0">
                    <router-link :to="{ name: 'project-detail', params: { id: row.id } }" class="font-medium text-slate-900 hover:text-indigo-600">
                      <span v-if="active === 'today-by-developer' && index < topPerDeveloper" class="text-indigo-600">{{ index + 1 }}.</span>
                      {{ row.name }}
                    </router-link>
                    <span class="text-xs text-slate-500 ms-2">{{ row.client_name }}</span>
                    <div v-if="row.current_task" class="text-xs text-slate-500 mt-0.5">المهمة الحالية: {{ row.current_task }}</div>
                  </div>
                  <div class="flex items-center gap-2 shrink-0">
                    <span class="px-2 py-0.5 rounded-full text-xs font-medium" :class="priorityColors[row.priority.level]">
                      {{ priorityLabels[row.priority.level] }} ({{ row.priority.score }})
                    </span>
                    <span v-if="!row.priority.workable" class="px-2 py-0.5 rounded-full text-xs font-medium bg-slate-200 text-slate-600">
                      غير قابل للتنفيذ
                    </span>
                  </div>
                </div>

                <div v-if="row.priority.summary" class="text-xs text-slate-500 mt-1.5">{{ row.priority.summary }}</div>

                <div class="flex flex-wrap gap-x-4 gap-y-1 text-xs mt-1.5">
                  <span :class="deadlineTone(row)">التسليم: {{ formatDate(row.content_deadline) }}</span>
                  <span class="text-slate-500">المرحلة: {{ pipelineStageLabels[row.pipeline_stage] || '—' }}</span>
                  <span v-if="row.blocker && row.blocker !== 'none'" class="text-amber-600">التوقف: {{ blockerLabels[row.blocker] }}</span>
                  <span v-if="row.readiness.missing_count" class="text-slate-500">ناقص: {{ row.readiness.missing_items }}</span>
                </div>
              </li>
            </ul>
          </section>
        </div>
      </template>

      <!-- Pipeline: أعمدة حسب مرحلة المشروع -->
      <template v-else-if="active === 'pipeline'">
        <div v-if="!pipelineColumns.length" class="text-center text-slate-400 py-12 bg-white rounded-xl border border-slate-200">
          لا توجد مشاريع.
        </div>
        <div v-else class="flex gap-4 overflow-x-auto pb-4">
          <section v-for="column in pipelineColumns" :key="column.stage" class="w-72 shrink-0 bg-slate-100 rounded-xl p-3">
            <header class="flex items-center justify-between mb-3">
              <span class="font-semibold text-sm text-slate-800">{{ pipelineStageLabels[column.stage] || column.stage }}</span>
              <span class="text-xs text-slate-500 bg-white rounded-full px-2 py-0.5">{{ column.projects.length }}</span>
            </header>
            <div class="space-y-2">
              <router-link
                v-for="row in column.projects"
                :key="row.id"
                :to="{ name: 'project-detail', params: { id: row.id } }"
                class="block bg-white rounded-lg border border-slate-200 p-3 hover:border-indigo-300 transition"
              >
                <div class="font-medium text-sm text-slate-900">{{ row.name }}</div>
                <div class="text-xs text-slate-500 mb-2">{{ row.client_name }}</div>
                <div class="flex items-center justify-between gap-2">
                  <span class="px-2 py-0.5 rounded-full text-xs font-medium" :class="priorityColors[row.priority.level]">
                    {{ row.priority.score }}
                  </span>
                  <span class="text-xs text-slate-400">{{ row.owner.name || 'غير مُسند' }}</span>
                </div>
              </router-link>
            </div>
          </section>
        </div>
      </template>

      <!-- القوائم المسطّحة: Updates Due / Paused Monitor / Critical Watchlist -->
      <template v-else>
        <div v-if="!payload.data.length" class="text-center text-slate-400 py-12 bg-white rounded-xl border border-slate-200">
          لا توجد مشاريع في هذه اللوحة 🎉
        </div>

        <div v-else class="space-y-3">
          <router-link
            v-for="row in payload.data"
            :key="row.id"
            :to="{ name: 'project-detail', params: { id: row.id } }"
            class="block bg-white hover:bg-slate-50 rounded-xl border border-slate-200 p-4 transition"
          >
            <div class="flex items-start justify-between gap-3 flex-wrap mb-2">
              <div>
                <span class="font-semibold text-slate-900">{{ row.name }}</span>
                <span class="text-sm text-slate-500 ms-2">{{ row.client_name }}</span>
              </div>
              <div class="flex items-center gap-2">
                <span
                  v-if="active === 'updates-due'"
                  class="px-2 py-0.5 rounded-full text-xs font-medium"
                  :class="updateStatusColors[row.priority.update_status]"
                >
                  {{ updateStatusLabels[row.priority.update_status] }}
                </span>
                <span class="px-2 py-0.5 rounded-full text-xs font-medium" :class="priorityColors[row.priority.level]">
                  {{ priorityLabels[row.priority.level] }} ({{ row.priority.score }})
                </span>
                <span class="text-xs text-slate-400">{{ row.owner.name || 'غير مُسند' }}</span>
              </div>
            </div>

            <div v-if="active === 'paused-monitor'" class="grid sm:grid-cols-4 gap-2 text-xs text-slate-500">
              <div>سبب الإيقاف: {{ blockerLabels[row.blocker] || '—' }}</div>
              <div>تاريخ الإيقاف: {{ formatDate(row.paused_since) }}</div>
              <div>مدة التوقف: {{ row.paused_days !== null ? `${row.paused_days} يوم` : '—' }}</div>
              <div>ملاحظات العميل: {{ feedbackLabels[row.client_feedback_status] }}</div>
            </div>

            <div v-else-if="active === 'updates-due'" class="grid sm:grid-cols-4 gap-2 text-xs text-slate-500">
              <div>آخر تحديث: {{ formatDateTime(row.last_client_update_at) }}</div>
              <div>التحديث القادم: {{ formatDateTime(row.next_client_update_at) }}</div>
              <div>كل {{ row.client_update_interval_days }} يوم</div>
              <div>المرحلة: {{ pipelineStageLabels[row.pipeline_stage] || '—' }}</div>
            </div>

            <div v-else class="text-xs text-slate-500">
              <div v-if="row.priority.summary">{{ row.priority.summary }}</div>
              <div class="mt-1">
                التسليم: {{ formatDate(row.content_deadline) }} · المرحلة: {{ pipelineStageLabels[row.pipeline_stage] || '—' }}
                <span v-if="row.readiness.missing_count"> · ناقص: {{ row.readiness.missing_items }}</span>
              </div>
            </div>
          </router-link>
        </div>
      </template>
    </template>
  </div>
</template>
