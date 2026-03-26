<template>
  <UFormField :label="label" class="mb-4">
    <UInput
        v-model="model"
        :type="showPassword ? 'text' : type"
        :placeholder="placeholder"
        size="lg"
        class="w-full"
    >
      <template v-if="type === 'password'" #trailing>
        <UButton
            color="neutral"
            variant="ghost"
            :icon="showPassword ? 'i-lucide-eye-off' : 'i-lucide-eye'"
            aria-label="Afficher/Masquer le mot de passe"
            @click="showPassword = !showPassword"
        />
      </template>
    </UInput>
  </UFormField>
</template>

<script setup lang="ts">
const props = withDefaults(defineProps<{
  label?: string
  type?: 'text' | 'password' | 'email' | 'number'
  placeholder?: string
}>(), {
  type: 'text',
  placeholder: ''
})

const model = defineModel<string | number>()
const showPassword = ref(false)
</script>