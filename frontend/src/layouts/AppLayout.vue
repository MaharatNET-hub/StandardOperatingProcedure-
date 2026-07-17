<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import api from '../lib/api'

const auth = useAuthStore()
const router = useRouter()

const roleLabels = ref({})

async function loadRoleLabels() {
  try {
    const { data } = await api.get('/roles')
    roleLabels.value = Object.fromEntries(data.map((r) => [r.key, r.label_ar]))
  } catch {
    // sidebar still works with the raw role key as a fallback
  }
}

async function logout() {
  await auth.logout()
  router.push({ name: 'login' })
}

onMounted(loadRoleLabels)
</script>

<template>
  <div class="min-h-screen flex bg-slate-50">
    <aside class="w-64 shrink-0 bg-indigo-950 text-indigo-100 flex flex-col">
      <div class="p-5 border-b border-indigo-900 flex items-center gap-3">
        <div class="bg-white rounded-lg p-1.5 shrink-0">
          <img src="/logo-icon.png" alt="Maharat Net" class="w-8 h-8 object-contain" />
        </div>
        <div>
          <div class="font-bold text-white text-sm leading-tight">Maharat Net</div>
          <div class="text-xs text-indigo-300 mt-0.5">نظام إدارة الجودة والمشاريع</div>
        </div>
      </div>

      <nav class="flex-1 p-3 space-y-1">
        <router-link
          :to="{ name: 'dashboard' }"
          class="block rounded-lg px-3 py-2 text-sm font-medium hover:bg-indigo-900 transition"
          active-class="bg-indigo-800 text-white"
        >
          لوحة التحكم
        </router-link>
        <router-link
          :to="{ name: 'projects' }"
          class="block rounded-lg px-3 py-2 text-sm font-medium hover:bg-indigo-900 transition"
          active-class="bg-indigo-800 text-white"
        >
          المشاريع
        </router-link>
        <router-link
          :to="{ name: 'activity-log' }"
          class="block rounded-lg px-3 py-2 text-sm font-medium hover:bg-indigo-900 transition"
          active-class="bg-indigo-800 text-white"
        >
          سجل النشاطات
        </router-link>
        <router-link
          :to="{ name: 'quotations' }"
          class="block rounded-lg px-3 py-2 text-sm font-medium hover:bg-indigo-900 transition"
          active-class="bg-indigo-800 text-white"
        >
          التحليل الفني وعروض الأسعار
        </router-link>
        <router-link
          v-if="auth.canManageUsers"
          :to="{ name: 'team' }"
          class="block rounded-lg px-3 py-2 text-sm font-medium hover:bg-indigo-900 transition"
          active-class="bg-indigo-800 text-white"
        >
          فريق العمل
        </router-link>
        <router-link
          v-if="auth.canManageRoles"
          :to="{ name: 'roles' }"
          class="block rounded-lg px-3 py-2 text-sm font-medium hover:bg-indigo-900 transition"
          active-class="bg-indigo-800 text-white"
        >
          الأدوار والصلاحيات
        </router-link>
        <router-link
          v-if="auth.canManageChecklistTemplate"
          :to="{ name: 'checklist-template' }"
          class="block rounded-lg px-3 py-2 text-sm font-medium hover:bg-indigo-900 transition"
          active-class="bg-indigo-800 text-white"
        >
          إدارة قائمة التحقق
        </router-link>
        <router-link
          v-if="auth.canManageSettings"
          :to="{ name: 'settings' }"
          class="block rounded-lg px-3 py-2 text-sm font-medium hover:bg-indigo-900 transition"
          active-class="bg-indigo-800 text-white"
        >
          الإعدادات
        </router-link>
      </nav>

      <div class="p-4 border-t border-indigo-900">
        <div class="text-sm font-medium text-white">{{ auth.user?.name }}</div>
        <div class="text-xs text-indigo-300">{{ roleLabels[auth.role] || auth.role }}</div>
        <button
          class="mt-3 w-full text-sm bg-indigo-900 hover:bg-indigo-800 text-white rounded-lg py-2 transition"
          @click="logout"
        >
          تسجيل الخروج
        </button>
      </div>
    </aside>

    <main class="flex-1 min-w-0">
      <router-view />
    </main>
  </div>
</template>
