<script setup>
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()
const router = useRouter()

const roleLabels = {
  admin: 'مدير / CTO',
  developer: 'مبرمج',
  qa_reviewer: 'مراجع جودة (QA)',
  it_specialist: 'أخصائي تقنية معلومات',
}

async function logout() {
  await auth.logout()
  router.push({ name: 'login' })
}
</script>

<template>
  <div class="min-h-screen flex bg-slate-50">
    <aside class="w-64 shrink-0 bg-indigo-950 text-indigo-100 flex flex-col">
      <div class="p-5 border-b border-indigo-900">
        <div class="font-bold text-white text-lg">نظام إدارة الجودة</div>
        <div class="text-xs text-indigo-300 mt-1">مشاريع WordPress / Astra</div>
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
          v-if="auth.isAdmin"
          :to="{ name: 'team' }"
          class="block rounded-lg px-3 py-2 text-sm font-medium hover:bg-indigo-900 transition"
          active-class="bg-indigo-800 text-white"
        >
          فريق العمل
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
