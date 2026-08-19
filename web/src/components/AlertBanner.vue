<template>
  <div v-if="message" class="banner" :class="tone">
    {{ message }}
  </div>
</template>

<script setup>
defineProps({
  message: { type: String, default: '' },
  tone: { type: String, default: 'down' },
});
</script>

<style scoped>
.banner {
  border: 1px solid var(--line);
  border-radius: 14px;
  padding: 12px 14px;
  font-weight: 600;
}

.down {
  background: rgba(255, 77, 109, 0.12);
  color: #ffd0d8;
}

.up {
  background: rgba(61, 255, 176, 0.1);
  color: #c8ffe8;
}

.info {
  background: rgba(122, 162, 255, 0.12);
  color: #d7e3ff;
}
</style>
