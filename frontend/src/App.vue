<script setup lang="ts">
import { useThemeStore } from '@/stores/theme'
import ToastContainer from '@/components/ui/ToastContainer.vue'
import LoadingOverlay from '@/components/ui/LoadingOverlay.vue'

// Inicializa o tema (aplica data-theme no boot).
useThemeStore()
</script>

<template>
  <RouterView />
  <ToastContainer />
  <LoadingOverlay />
</template>
