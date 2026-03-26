<template>
  <UCard class="w-full max-w-md mx-auto">

    <div class="flex flex-col items-center mb-8 text-center">
      <AtomsIconTafl class="w-15 h-15 mb-4" />
      <h1 class="text-3xl font-bold font-['Cinzel',serif] text-oil-950">Hnefatafl Online</h1>
      <p class="mt-2 text-sm text-pine-cone-600">La Stratégie des Rois</p>
    </div>

    <div class="flex p-1 mb-8 space-x-1 rounded-lg bg-neutral-100">
      <UButton
          class="flex-1 justify-center py-2.5 text-sm"
          :variant="isLoginMode ? 'tabActive' : 'tabInactive'"
          @click="isLoginMode = true"
      >
        Connexion
      </UButton>
      <UButton
          class="flex-1 justify-center py-2.5 text-sm"
          :variant="!isLoginMode ? 'tabActive' : 'tabInactive'"
          @click="isLoginMode = false"
      >
        Inscription
      </UButton>
    </div>

    <form @submit.prevent="handleSubmit" class="space-y-5">

      <div>
        <AtomsInput v-model="email" label="Email" type="email" placeholder="viking@valhalla.com" />
        <p v-if="errors.email" class="mt-1 text-sm text-error-500">{{ errors.email }}</p>
      </div>

      <div>
        <AtomsInput v-model="password" label="Mot de passe" type="password" />
        <p v-if="errors.password" class="mt-1 text-sm text-error-500">{{ errors.password }}</p>
      </div>

      <div v-if="!isLoginMode">
        <AtomsInput v-model="confirmPassword" label="Confirmer le mot de passe" type="password" />
        <p v-if="errors.confirm" class="mt-1 text-sm text-error-500">{{ errors.confirm }}</p>
      </div>

      <UAlert
          v-if="errors.general"
          color="error"
          variant="subtle"
          icon="i-lucide-alert-circle"
          :title="errors.general"
      />

      <UButton
          type="submit"
          variant="cta"
          size="xl"
          block
          :loading="isLoading"
          class="mt-6"
      >
        {{ isLoginMode ? 'Se connecter' : 'Rejoindre' }}
      </UButton>

    </form>

    <div class="mt-6 text-center" v-if="isLoginMode">
      <UButton variant="Link" class="text-sm">
        Mot de passe oublié ?
      </UButton>
    </div>

  </UCard>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import validator from 'validator';

const isLoginMode = ref(true)
const email = ref('')
const password = ref('')
const confirmPassword = ref('')
const isLoading = ref(false)
const errors = ref<Record<string, string>>({})

const { login, register } = useAuth()
const router = useRouter()

const handleSubmit = async () => {
  errors.value = {}

  if (!validator.isEmail(email.value)) {
    errors.value.email = "Format d'email invalide"
    return
  }
  if (!validator.isStrongPassword(password.value)) {
    errors.value.password = "Le mot de passe doit contenir au moins 8 caractères, une majuscule, un chiffre et un symbole."
    return
  }
  if (!isLoginMode.value && password.value !== confirmPassword.value) {
    errors.value.confirm = "Les mots de passe ne correspondent pas"
    return
  }

  isLoading.value = true
  let success = false

  try {
    if (isLoginMode.value) {
      success = await login(email.value, password.value)
      if (!success) errors.value.general = 'Email ou mot de passe incorrect'
    } else {
      success = await register(email.value, password.value)
      if (!success) errors.value.general = "Impossible de s'inscrire (cet email est peut-être déjà pris)"
    }

    if (success) {
      router.push('/games')
    }
  } catch (error) {
    errors.value.general = "Une erreur serveur est survenue."
  } finally {
    isLoading.value = false
  }
}
</script>