<script setup>
import { useAuthStore } from './stores/auth'

const auth = useAuthStore()
auth.hydrate()
</script>

<template>
  <router-view />
</template>
