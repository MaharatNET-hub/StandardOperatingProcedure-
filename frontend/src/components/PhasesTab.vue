<script setup>
import { computed } from 'vue'
import api from '../lib/api'
import { useAuthStore } from '../stores/auth'

const props = defineProps({ project: Object })
const emit = defineEmits(['reload'])

const auth = useAuthStore()

const sortedPhases = computed(() =>
  [...props.project.project_phases].sort((a, b) => a.phase.order - b.phase.order),
)

const statusLabels = { pending: 'لم تبدأ', in_progress: 'قيد التنفيذ', completed: 'مكتملة' }
const statusColors = {
  pending: 'bg-slate-100 text-slate-500',
  in_progress: 'bg-amber-100 text-amber-700',
  completed: 'bg-emerald-100 text-emerald-700',
}

const canManage = computed(() => auth.canManageProjects || auth.isDeveloper)

async function setStatus(phase, status) {
  await api.patch(`/projects/${props.project.id}/phases/${phase.id}`, { status })
  emit('reload')
}
</script>

<template>
  <div class="space-y-4">
    <div
      v-for="p in sortedPhases"
      :key="p.id"
      class="bg-white rounded-xl border border-slate-200 p-5 flex items-start justify-between gap-4"
    >
      <div>
        <div class="flex items-center gap-2">
          <span class="text-xs font-mono text-slate-400">المرحلة {{ p.phase.number }}</span>
          <h3 class="font-semibold text-slate-900">{{ p.phase.name_ar }}</h3>
        </div>
        <p class="text-sm text-slate-500 mt-1 max-w-2xl">{{ p.phase.description }}</p>
        <p class="text-xs text-slate-400 mt-2">المدة التقديرية: {{ p.phase.estimated_duration }}</p>
      </div>

      <div class="text-left shrink-0 flex flex-col items-end gap-2">
        <span class="px-2.5 py-1 rounded-full text-xs font-medium" :class="statusColors[p.status]">
          {{ statusLabels[p.status] }}
        </span>
        <div v-if="canManage" class="flex gap-1">
          <button
            v-if="p.status === 'pending'"
            class="text-xs px-2 py-1 rounded bg-indigo-50 text-indigo-700 hover:bg-indigo-100"
            @click="setStatus(p, 'in_progress')"
          >
            بدء
          </button>
          <button
            v-if="p.status === 'in_progress'"
            class="text-xs px-2 py-1 rounded bg-emerald-50 text-emerald-700 hover:bg-emerald-100"
            @click="setStatus(p, 'completed')"
          >
            إنهاء
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
