<template>
  <LoginForm v-if="!ready" @logged-in="onLoggedIn" />
  <SiteList v-else :user="user" @logout="logout" />
</template>

<script setup>
import { onMounted, onUnmounted, ref } from 'vue';
import { apiFetch, clearToken, getToken } from '../api.js';
import LoginForm from './LoginForm.vue';
import SiteList from './SiteList.vue';

const ready = ref(false);
const user = ref(null);

onMounted(() => {
  window.addEventListener('monitor:unauthorized', onUnauthorized);
  restore();
});

onUnmounted(() => {
  window.removeEventListener('monitor:unauthorized', onUnauthorized);
});

async function restore() {
  if (!getToken()) {
    ready.value = false;
    return;
  }
  const response = await apiFetch('/api/me');
  if (response.status === 401) {
    clearToken();
    ready.value = false;
    return;
  }
  if (!response.ok) {
    ready.value = true;
    return;
  }
  user.value = await response.json();
  ready.value = true;
}

function onLoggedIn(profile) {
  user.value = profile;
  ready.value = true;
}

function onUnauthorized() {
  user.value = null;
  ready.value = false;
}

async function logout() {
  try {
    await apiFetch('/api/logout', { method: 'POST' });
  } catch {
    // ignore
  }
  clearToken();
  user.value = null;
  ready.value = false;
}
</script>
