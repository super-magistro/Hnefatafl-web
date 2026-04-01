<script setup lang="ts">
import { onMounted } from 'vue'

const { token } = useAuth()

onMounted(() => {
  if (token.value) {
    navigateTo('/main', { replace: true })
  }
  else {
    navigateTo('/login', { replace: true })
  }
})
</script>

<template>
  <MoleculesLoadingScreen />
</template>