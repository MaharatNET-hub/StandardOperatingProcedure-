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
    permissions: (state) => state.user?.permissions || [],
    isAdmin: (state) => state.user?.role === 'admin',
    isQaReviewer: (state) => state.user?.role === 'qa_reviewer',
    isItSpecialist: (state) => state.user?.role === 'it_specialist',
    isDeveloper: (state) => state.user?.role === 'developer',
    hasPermission: (state) => (key) => (state.user?.permissions || []).includes(key),
    canManageUsers: (state) => (state.user?.permissions || []).includes('manage_users'),
    canManageRoles: (state) => (state.user?.permissions || []).includes('manage_roles'),
    canManageProjects: (state) => (state.user?.permissions || []).includes('manage_projects'),
    viewsAllProjects: (state) => (state.user?.permissions || []).includes('view_all_projects'),
    canManageChecklistTemplate: (state) => (state.user?.permissions || []).includes('manage_checklist_template'),
    canManageSettings: (state) => (state.user?.permissions || []).includes('manage_settings'),
    canReview: (state) => (state.user?.permissions || []).includes('qa_review'),
    canDecidePlugins: (state) => (state.user?.permissions || []).includes('decide_plugins'),
  },
  actions: {
    hydrate() {
      const token = localStorage.getItem('sop_token')
      const user = localStorage.getItem('sop_user')
      if (token && user) {
        this.token = token
        this.user = JSON.parse(user)
        this.refreshUser()
      }
      this.ready = true
    },
    async refreshUser() {
      try {
        const { data } = await api.get('/me')
        this.user = data
        localStorage.setItem('sop_user', JSON.stringify(data))
      } catch {
        // token likely expired; leave cached user in place until an authenticated
        // request fails and the interceptor logs the user out.
      }
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
