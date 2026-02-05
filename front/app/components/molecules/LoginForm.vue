<style lang="less" scoped src="@/assets/less/components/molecules/LoginForm.less"></style>
<script setup lang="ts">
import { Shield } from 'lucide-vue-next'

const isLoginMode = ref(true)
const email = ref('')
const password = ref('')
const confirmPassword = ref('')
const isLoading = ref(false)

const errors = ref<Record<string, string>>({})

const { login } = useAuth()
const router = useRouter()

const handleSubmit = async () => {
  errors.value = {}

  // Validation rapide (grâce aux utils auto-importés)
  if (!isValidEmail(email.value)) {
    errors.value.email = "Email invalide"
    return
  }
  if (!isStrongPassword(password.value)) {
    errors.value.password = "Mot de passe trop court (min 6)"
    return
  }
  if (!isLoginMode.value && password.value !== confirmPassword.value) {
    errors.value.confirm = "Les mots de passe ne correspondent pas"
    return
  }

  isLoading.value = true

  if (isLoginMode.value) {
    const success = await login(email.value, password.value)
    if (success) {
      router.push('/games')
    } else {
      errors.value.general = 'Identifiants incorrects'
      errors.value.general = 'Identifiants incorrects'
    }
  } else {
    errors.value.general = "Inscription bientôt dispo !"
  }

  isLoading.value = false
}
</script>

<template>
  <div class="auth-card">
    <div class="header">
      <div class="shield-icon">
        <Shield :size="32" color="#DAA520" fill="#2E4A35" />
      </div>
      <h1>Hnefatafl Online</h1>
      <p>La Stratégie des Rois</p>
    </div>

    <div class="tabs">
      <button :class="{ active: isLoginMode }" @click="isLoginMode = true">Connexion</button>
      <button :class="{ active: !isLoginMode }" @click="isLoginMode = false">Inscription</button>
    </div>

    <form @submit.prevent="handleSubmit">

      <AtomsInput v-model="email" label="Email" type="email" placeholder="viking@valhalla.com" />
      <p v-if="errors.email" class="field-error">{{ errors.email }}</p>

      <AtomsInput v-model="password" label="Mot de passe" type="password" />
      <p v-if="errors.password" class="field-error">{{ errors.password }}</p>

      <div v-if="!isLoginMode">
        <AtomsInput v-model="confirmPassword" label="Confirmer" type="password" />
        <p v-if="errors.confirm" class="field-error">{{ errors.confirm }}</p>
      </div>

      <button type="submit" class="cta-btn" :disabled="isLoading">
        {{ isLoading ? '...' : (isLoginMode ? 'Se connecter' : 'Rejoindre') }}
      </button>

      <p v-if="errors.general" class="error-msg">{{ errors.general }}</p>
    </form>

    <div class="footer-links">
      <a href="#">Mot de passe oublié ?</a>
    </div>
  </div>
</template>

