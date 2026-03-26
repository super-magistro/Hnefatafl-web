<script setup lang="ts">
// pages/index.vue
definePageMeta({
  middleware: [
    function (to, from) {
      const { token } = useAuth()

      // Redirection instantanée avant même le rendu de la page
      if (token.value) {
        return navigateTo('/main')
      }
      return navigateTo('/login')
    }
  ]
})
</script>

<template>
  <div></div>
</template>