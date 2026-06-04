<script setup lang="ts">
</script>
<template>
  <div class="flex h-screen overflow-hidden bg-neutral-100">
    <OrganismsAppSidebar />
    <main class="flex-1 overflow-y-auto">
      <slot />
    </main>
  </div>
</template>
