<template>
  <span class="badge" :class="status">
    <i></i>
    {{ text }}
  </span>
</template>

<script setup>
defineProps({
  status: { type: String, required: true },
  text: { type: String, required: true },
});
</script>

<style scoped>
.badge {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 6px 10px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 600;
  letter-spacing: 0.01em;
  background: var(--bg-soft);
}

.badge i {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: currentColor;
  box-shadow: 0 0 0 4px color-mix(in srgb, currentColor 20%, transparent);
}

.up { color: var(--up); }
.down { color: var(--down); }
.slow { color: var(--slow); }
.retrying { color: var(--retry); }
.unknown { color: var(--muted); }
</style>
