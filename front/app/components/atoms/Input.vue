<style lang="less" scoped src="~/assets/less/components/atoms/Input.less"></style>
<template>
  <div class="input-group">
    <label v-if="label">{{ label }}</label>
    <div class="input-wrapper" :class="{ 'focused': isFocused }">
      <input
          v-model="model"
          :type="showPassword ? 'text' : type"
          :placeholder="placeholder"
          @focus="isFocused = true"
          @blur="isFocused = false"
      />

      <button
          v-if="type === 'password'"
          type="button"
          class="eye-btn"
          @click="showPassword = !showPassword"
      >
        <Eye v-if="!showPassword" :size="20" />
        <EyeOff v-else :size="20" />
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Eye, EyeOff } from 'lucide-vue-next'

defineProps<{
  label?: string
  type?: string // 'text', 'password', 'email'
  placeholder?: string
}>()

const model = defineModel<string>()
const showPassword = ref(false)
const isFocused = ref(false)
</script>
