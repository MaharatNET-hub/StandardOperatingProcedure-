import { defineStore } from 'pinia'
import api from '../lib/api'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    token: null,
    user: null,
    ready: false,
  }),
  getters: {
    isAuthenticated: (state) => !!state.token,
    role: (state) => state.user?.role,
    isAdmin: (state) => state.user?.role === 'admin',
    isQaReviewer: (state) => state.user?.role === 'qa_reviewer',
    isItSpecialist: (state) => state.user?.role === 'it_specialist',
    isDeveloper: (state) => state.user?.role === 'developer',
    canReview: (state) => ['admin', 'qa_reviewer'].includes(state.user?.role),
    canDecidePlugins: (state) => ['admin', 'it_specialist'].includes(state.user?.role),
  },
  actions: {
    hydrate() {
      const token = localStorage.getItem('sop_token')
      const user = localStorage.getItem('sop_user')
      if (token && user) {
        this.token = token
        this.user = JSON.parse(user)
      }
      this.ready = true
    },
    async login(email, password) {
      const { data } = await api.post('/login', { email, password })
      this.token = data.token
      this.user = data.user
      localStorage.setItem('sop_token', data.token)
      localStorage.setItem('sop_user', JSON.stringify(data.user))
    },
    async logout() {
      try {
        await api.post('/logout')
      } catch {
        // ignore
      }
      this.token = null
      this.user = null
      localStorage.removeItem('sop_token')
      localStorage.removeItem('sop_user')
    },
  },
})
