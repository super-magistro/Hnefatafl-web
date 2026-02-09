<template>
  <div style="display: flex; justify-content: center; align-items: center; height: 100vh;">
    <p>Redirection en cours...</p>
  </div>
</template>

<script setup lang="ts">
// On récupère le token depuis notre composable
const { token } = useAuth()

// On redirige automatiquement dès que la page est chargée
onMounted(() => {
  if (token.value) {
    // Si on a le badge, on va jouer
    navigateTo('/games')
  } else {
    // Sinon, on va se connecter
    navigateTo('/login')
  }
})
</script>