<script setup>
const props = defineProps({ project: Object })

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

const priorityLabels = { critical: 'حرجة', high: 'عالية', medium: 'متوسطة', low: 'منخفضة' }
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
      <div class="flex items-center justify-between mb-4">
        <h2 class="font-semibold text-slate-900">المتطلبات</h2>
        <span class="text-sm font-bold" :class="project.readiness?.percent >= 80 ? 'text-emerald-600' : project.readiness?.percent >= 40 ? 'text-amber-600' : 'text-red-600'">
          {{ project.readiness?.percent ?? 0 }}% جاهز
        </span>
      </div>

      <div class="space-y-2">
        <div
          v-for="item in project.readiness?.items || []"
          :key="item.key"
          class="flex items-center justify-between border border-slate-100 rounded-lg px-3 py-2.5"
        >
          <div class="flex items-center gap-2">
            <span class="w-2 h-2 rounded-full shrink-0" :class="statusDot[item.status]"></span>
            <span class="text-sm font-medium text-slate-800">{{ item.label }}</span>
          </div>
          <div class="flex items-center gap-2">
            <span v-if="item.message" class="text-xs text-slate-400">{{ item.message }}</span>
            <span class="px-2 py-0.5 rounded-full text-xs font-medium" :class="statusColors[item.status]">
              {{ statusLabels[item.status] }}
            </span>
          </div>
        </div>
        <p v-if="!project.readiness?.items?.length" class="text-sm text-slate-400 text-center py-4">
          لا توجد متطلبات محددة لهذا النوع من المشاريع.
        </p>
      </div>
    </div>
  </div>
</template>
