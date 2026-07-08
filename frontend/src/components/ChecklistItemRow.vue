<script setup>
import { ref } from 'vue'
import api from '../lib/api'

const props = defineProps({ item: Object })
const emit = defineEmits(['updated'])

const notes = ref(props.item.notes || '')
const savingNotes = ref(false)
const addingLink = ref(false)
const storageBaseUrl = (import.meta.env.VITE_API_URL || 'http://localhost:8000/api').replace('/api', '')
const linkUrl = ref('')
const uploading = ref(false)

async function toggle() {
  const nextStatus = props.item.status === 'done' ? 'pending' : 'done'
  const { data } = await api.patch(
    `/projects/${props.item.project_id}/checklist/${props.item.id}`,
    { status: nextStatus, notes: notes.value },
  )
  emit('updated', data)
}

async function markNa() {
  const nextStatus = props.item.status === 'na' ? 'pending' : 'na'
  const { data } = await api.patch(
    `/projects/${props.item.project_id}/checklist/${props.item.id}`,
    { status: nextStatus, notes: notes.value },
  )
  emit('updated', data)
}

async function saveNotes() {
  savingNotes.value = true
  try {
    const { data } = await api.patch(
      `/projects/${props.item.project_id}/checklist/${props.item.id}`,
      { status: props.item.status, notes: notes.value },
    )
    emit('updated', data)
  } finally {
    savingNotes.value = false
  }
}

async function submitLink() {
  if (!linkUrl.value) return
  await api.post(`/projects/${props.item.project_id}/checklist/${props.item.id}/evidence`, {
    type: 'link',
    url: linkUrl.value,
  })
  linkUrl.value = ''
  addingLink.value = false
  const { data } = await api.get(`/projects/${props.item.project_id}/checklist`)
  const flat = data.flatMap((c) => c.items)
  const updated = flat.find((i) => i.id === props.item.id)
  if (updated) emit('updated', updated)
}

async function onFileSelected(e) {
  const file = e.target.files[0]
  if (!file) return
  uploading.value = true
  const form = new FormData()
  form.append('type', 'file')
  form.append('file', file)
  try {
    await api.post(`/projects/${props.item.project_id}/checklist/${props.item.id}/evidence`, form, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    const { data } = await api.get(`/projects/${props.item.project_id}/checklist`)
    const flat = data.flatMap((c) => c.items)
    const updated = flat.find((i) => i.id === props.item.id)
    if (updated) emit('updated', updated)
  } finally {
    uploading.value = false
    e.target.value = ''
  }
}

async function removeEvidence(evidenceId) {
  await api.delete(`/evidence/${evidenceId}`)
  const { data } = await api.get(`/projects/${props.item.project_id}/checklist`)
  const flat = data.flatMap((c) => c.items)
  const updated = flat.find((i) => i.id === props.item.id)
  if (updated) emit('updated', updated)
}
</script>

<template>
  <div class="border border-slate-100 rounded-lg p-3" :class="{ 'bg-slate-50': item.status === 'na' }">
    <div class="flex items-start gap-3">
      <button
        class="mt-0.5 shrink-0 w-5 h-5 rounded border flex items-center justify-center transition"
        :class="item.status === 'done' ? 'bg-emerald-500 border-emerald-500 text-white' : 'border-slate-300 hover:border-indigo-400'"
        @click="toggle"
      >
        <svg v-if="item.status === 'done'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3.5 h-3.5">
          <path fill-rule="evenodd" d="M16.7 5.3a1 1 0 010 1.4l-8 8a1 1 0 01-1.4 0l-4-4a1 1 0 111.4-1.4L8 12.6l7.3-7.3a1 1 0 011.4 0z" clip-rule="evenodd" />
        </svg>
      </button>

      <div class="flex-1 min-w-0">
        <p class="text-sm text-slate-800 leading-relaxed" :class="{ 'line-through text-slate-400': item.status === 'na' }">
          {{ item.checklist_item.text_ar }}
          <span v-if="item.checklist_item.is_critical" class="text-red-500 text-xs font-bold ms-1">⚠ حرج</span>
        </p>

        <div v-if="item.status !== 'pending'" class="text-xs text-slate-400 mt-1">
          {{ item.status === 'na' ? 'غير منطبق' : 'تم بواسطة' }}
          <span v-if="item.checker"> {{ item.checker.name }}</span>
          <span v-if="item.checked_at"> — {{ new Date(item.checked_at).toLocaleDateString('ar') }}</span>
        </div>

        <textarea
          v-model="notes"
          placeholder="ملاحظات..."
          rows="1"
          class="mt-2 w-full text-xs rounded border border-slate-200 px-2 py-1 focus:outline-none focus:ring-1 focus:ring-indigo-400"
          @blur="saveNotes"
        ></textarea>

        <div v-if="item.evidence?.length" class="mt-2 flex flex-wrap gap-2">
          <div
            v-for="ev in item.evidence"
            :key="ev.id"
            class="text-xs bg-slate-100 rounded px-2 py-1 flex items-center gap-1.5"
          >
            <a
              v-if="ev.type === 'link'"
              :href="ev.url"
              target="_blank"
              class="text-indigo-600 hover:underline max-w-[160px] truncate"
            >
              🔗 {{ ev.url }}
            </a>
            <a
              v-else
              :href="`${storageBaseUrl}/storage/${ev.path}`"
              target="_blank"
              class="text-indigo-600 hover:underline max-w-[160px] truncate"
            >
              📎 {{ ev.original_name }}
            </a>
            <button class="text-slate-400 hover:text-red-500" @click="removeEvidence(ev.id)">✕</button>
          </div>
        </div>

        <div class="mt-2 flex items-center gap-2 text-xs">
          <label class="cursor-pointer text-indigo-600 hover:underline">
            رفع ملف
            <input type="file" class="hidden" @change="onFileSelected" />
          </label>
          <span class="text-slate-300">|</span>
          <button class="text-indigo-600 hover:underline" @click="addingLink = !addingLink">إضافة رابط</button>
          <button
            class="text-slate-400 hover:underline"
            :class="{ 'text-amber-600 font-medium': item.status === 'na' }"
            @click="markNa"
          >
            {{ item.status === 'na' ? 'إلغاء "غير منطبق"' : 'غير منطبق' }}
          </button>
          <span v-if="uploading" class="text-slate-400">...جاري الرفع</span>
        </div>

        <div v-if="addingLink" class="mt-2 flex gap-2">
          <input
            v-model="linkUrl"
            type="url"
            placeholder="https://..."
            class="flex-1 text-xs rounded border border-slate-200 px-2 py-1"
          />
          <button class="text-xs bg-indigo-600 text-white rounded px-2 py-1" @click="submitLink">إضافة</button>
        </div>
      </div>
    </div>
  </div>
</template>
