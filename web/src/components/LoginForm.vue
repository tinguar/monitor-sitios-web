<template>
  <main class="login">
    <section class="card">
      <p class="kicker">Monitor de sitios</p>
      <h1>Iniciar sesión</h1>
      <p class="lead">Solo personal autorizado. La sesión caduca a las 8 horas.</p>
      <form @submit.prevent="submit">
        <label>
          Correo
          <input v-model.trim="email" type="email" autocomplete="username" required />
        </label>
        <label>
          Contraseña
          <input v-model="password" type="password" autocomplete="current-password" required />
        </label>
        <button type="submit" :disabled="saving">
          {{ saving ? 'Entrando…' : 'Entrar' }}
        </button>
        <p v-if="error" class="error">{{ error }}</p>
      </form>
    </section>
  </main>
</template>

<script setup>
import { ref } from 'vue';
import { apiFetch, setToken } from '../api.js';

const emit = defineEmits(['logged-in']);
const email = ref('');
const password = ref('');
const saving = ref(false);
const error = ref('');

async function submit() {
  saving.value = true;
  error.value = '';
  try {
    const response = await apiFetch('/api/login', {
      method: 'POST',
      body: JSON.stringify({ email: email.value, password: password.value }),
    });
    const data = await response.json().catch(() => ({}));
    if (!response.ok) {
      throw new Error(data.error || 'No se pudo iniciar sesión');
    }
    setToken(data.token);
    password.value = '';
    emit('logged-in', data.user);
  } catch (err) {
    error.value = err.message;
  } finally {
    saving.value = false;
  }
}
</script>

<style scoped>
.login {
  min-height: 100vh;
  display: grid;
  place-items: center;
  padding: 24px;
}

.card {
  width: min(420px, 100%);
  background: var(--bg-elev);
  border: 1px solid var(--line);
  border-radius: 20px;
  padding: 28px;
  box-shadow: var(--shadow);
}

.kicker {
  margin: 0 0 8px;
  color: var(--up);
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  font-size: 12px;
}

h1 {
  margin: 0;
}

.lead {
  color: var(--muted);
}

form {
  display: grid;
  gap: 12px;
}

label {
  display: grid;
  gap: 6px;
  font-size: 13px;
  color: var(--muted);
}

input {
  background: var(--bg);
  color: var(--text);
  border: 1px solid var(--line);
  border-radius: 12px;
  padding: 12px 14px;
}

button {
  background: var(--up);
  color: #062016;
  border: 0;
  border-radius: 12px;
  padding: 12px 14px;
  font-weight: 700;
}

button:disabled {
  opacity: 0.6;
}

.error {
  color: var(--down);
  margin: 0;
}
</style>
