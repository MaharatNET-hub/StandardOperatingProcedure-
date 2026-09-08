<script setup>
import { ref, onMounted } from 'vue'
import api from '../lib/api'
import { feedbackLabels, blockerLabels, updateStatusLabels, updateStatusColors } from '../lib/labels'

const items = ref([])
const loading = ref(true)

async function load() {
  loading.value = true
  const { data } = await api.get('/client-follow-ups')
  items.value = data.data
  loading.value = false
}

onMounted(load)
</script>

<template>
  <div class="p-6 md:p-8 max-w-5xl mx-auto">
    <h1 class="text-2xl font-bold text-slate-900 mb-2">متابعة العملاء</h1>
    <p class="text-sm text-slate-500 mb-6">المشاريع التي تحتاج تواصل مع العميل اليوم — تحديث متأخر أو مستحق، ملاحظات جديدة، أو مشروع متوقف بانتظاره.</p>

    <div v-if="loading" class="text-slate-500">...جاري التحميل</div>

    <div v-else-if="!items.length" class="text-center text-slate-400 py-12 bg-white rounded-xl border border-slate-200">
      لا توجد مشاريع بحاجة متابعة اليوم 🎉
    </div>

    <div v-else class="space-y-3">
      <router-link
        v-for="item in items"
        :key="item.id"
        :to="{ name: 'project-detail', params: { id: item.id } }"
        class="block bg-white hover:bg-slate-50 rounded-xl border border-slate-200 p-4 transition"
      >
        <div class="flex items-center justify-between gap-3 flex-wrap mb-2">
          <div>
            <span class="font-semibold text-slate-900">{{ item.name }}</span>
            <span class="text-sm text-slate-500 ms-2">{{ item.client_name }}</span>
          </div>
          <span class="text-xs text-slate-400">{{ item.primary_developer || '—' }}</span>
        </div>

        <div class="flex flex-wrap gap-2 mb-2">
          <span class="px-2 py-1 rounded-full text-xs font-medium" :class="updateStatusColors[item.update_status]">
            التحديث: {{ updateStatusLabels[item.update_status] }}
          </span>
          <span v-for="(reason, i) in item.reasons" :key="i" class="px-2 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-700">
            {{ reason }}
          </span>
        </div>

        <div class="grid sm:grid-cols-3 gap-2 text-xs text-slate-500">
          <div>آخر تحديث: {{ item.days_since_update !== null ? `منذ ${item.days_since_update} يوم` : '—' }}</div>
          <div>حالة الملاحظات: {{ feedbackLabels[item.client_feedback_status] }}</div>
          <div>التوقف: {{ blockerLabels[item.blocker] }}</div>
        </div>

        <div v-if="item.missing_from_client.length" class="mt-2 text-xs text-slate-500">
          ناقص من العميل: {{ item.missing_from_client.join('، ') }}
        </div>
      </router-link>
    </div>
  </div>
</template>
