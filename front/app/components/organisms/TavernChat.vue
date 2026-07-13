<template>
  <UCard variant="subtle" class="shadow-sm h-[300px] shrink-0 flex flex-col overflow-hidden" :ui="{ body: 'flex-1 flex flex-col min-h-0 p-4 overflow-hidden' }">
    <template #header>
      <h4 class="text-xs font-bold text-pine-cone-600 uppercase tracking-wider flex items-center gap-1.5 font-['Cinzel',serif]">
        <UIcon name="i-lucide-message-square" class="text-warning-500 w-4 h-4" />
        Discussion de Taverne
      </h4>
    </template>

    <!-- Liste des messages -->
    <div ref="chatContainer" class="flex-1 overflow-y-auto p-2 space-y-2 text-xs bg-neutral-50 rounded-lg mb-2.5 min-h-0">
      <div v-if="!messages || messages.length === 0" class="text-xs text-pine-cone-400 text-center py-8">
        Aucun message dans le chat.
      </div>
      <div 
        v-else 
        v-for="(msg, idx) in messages" 
        :key="idx" 
        class="p-2 rounded-lg max-w-[85%] break-words shadow-sm flex flex-col"
        :class="msg.sender === currentSenderName ? 'bg-primary-50 border border-primary-100 self-end text-oil-950' : 'bg-white border border-neutral-200 self-start text-neutral-800'"
      >
        <div class="flex items-center justify-between gap-4 mb-1">
          <span class="font-black text-[10px] uppercase tracking-wider" :class="msg.sender === currentSenderName ? 'text-primary-800' : 'text-pine-cone-600'">
            {{ msg.sender }}
          </span>
          <span class="text-[9px] text-pine-cone-400 font-bold shrink-0">
            {{ formatTime(msg.timestamp) }}
          </span>
        </div>
        <p class="leading-relaxed font-medium">{{ msg.text }}</p>
      </div>
    </div>

    <!-- Formulaire d'envoi -->
    <form @submit.prevent="handleSend" class="flex gap-2 shrink-0">
      <UInput 
        v-model="chatInput" 
        placeholder="Écrivez un message..." 
        class="flex-1"
        size="md"
        color="neutral"
        :disabled="isSending"
      />
      <UButton 
        type="submit" 
        icon="i-lucide-send" 
        color="primary"
        variant="solid"
        :loading="isSending"
      />
    </form>
  </UCard>
</template>

<script setup lang="ts">
import { ref, watch, nextTick } from 'vue'

const props = defineProps<{
  messages: { sender: string; text: string; timestamp: string }[]
  currentSenderName: string
  isSending: boolean
}>()

const emit = defineEmits<{
  (e: 'send', text: string): void
}>()

const chatInput = ref('')
const chatContainer = ref<HTMLElement | null>(null)

const handleSend = () => {
  if (!chatInput.value.trim()) return
  emit('send', chatInput.value.trim())
  chatInput.value = ''
}


// Auto-scroll en bas de la taverne à chaque nouveau message
watch(() => props.messages?.length, () => {
  nextTick(() => {
    if (chatContainer.value) {
      chatContainer.value.scrollTop = chatContainer.value.scrollHeight
    }
  })
}, { immediate: true })
</script>
