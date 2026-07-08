<script setup>
import { ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const email = ref('')
const password = ref('')
const error = ref('')
const loading = ref(false)

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()

async function submit() {
  error.value = ''
  loading.value = true
  try {
    await auth.login(email.value, password.value)
    router.push(route.query.redirect || { name: 'dashboard' })
  } catch (e) {
    error.value = e.response?.data?.message || 'تعذر تسجيل الدخول.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-indigo-950 via-slate-900 to-indigo-950 px-4">
    <div class="w-full max-w-sm bg-white rounded-2xl shadow-xl p-8">
      <div class="text-center mb-8">
        <img src="/logo-icon.png" alt="Maharat Net" class="mx-auto w-16 h-16 object-contain mb-3" />
        <h1 class="text-xl font-bold text-slate-900">نظام إدارة الجودة والمشاريع</h1>
        <p class="text-sm text-slate-500 mt-1">Maharat Net — دليل إجراءات العمل الموحّد (SOP)</p>
      </div>

      <form class="space-y-4" @submit.prevent="submit">
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">البريد الإلكتروني</label>
          <input
            v-model="email"
            type="email"
            required
            class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">كلمة المرور</label>
          <input
            v-model="password"
            type="password"
            required
            class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
          />
        </div>

        <p v-if="error" class="text-sm text-red-600">{{ error }}</p>

        <button
          type="submit"
          :disabled="loading"
          class="w-full bg-indigo-600 hover:bg-indigo-700 disabled:opacity-60 text-white font-medium rounded-lg py-2.5 transition"
        >
          {{ loading ? '...جاري الدخول' : 'تسجيل الدخول' }}
        </button>
      </form>
    </div>
  </div>
</template>
