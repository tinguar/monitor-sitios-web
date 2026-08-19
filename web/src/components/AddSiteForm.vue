<template>
  <form class="form" @submit.prevent="submit">
    <input v-model.trim="name" type="text" placeholder="Nombre del sitio" required />
    <input v-model.trim="url" type="text" placeholder="https://tusitio.com" required />
    <select v-model="countryCode" required>
      <option v-for="item in countries" :key="item.code" :value="item.code">
        +{{ item.code }} {{ item.name }}
      </option>
    </select>
    <input
      v-model.trim="phone"
      type="tel"
      inputmode="numeric"
      placeholder="0991234567"
      required
    />
    <button type="submit" :disabled="saving">
      {{ saving ? 'Agregando…' : 'Agregar sitio' }}
    </button>
  </form>
  <p v-if="error" class="error">{{ error }}</p>
</template>

<script setup>
import { ref } from 'vue';
import { apiFetch } from '../api.js';

const emit = defineEmits(['created']);
const name = ref('');
const url = ref('');
const countryCode = ref('593');
const phone = ref('');
const saving = ref(false);
const error = ref('');

const countries = [
  { code: '593', name: 'Ecuador' },
  { code: '57', name: 'Colombia' },
  { code: '51', name: 'Perú' },
  { code: '52', name: 'México' },
  { code: '54', name: 'Argentina' },
  { code: '56', name: 'Chile' },
  { code: '58', name: 'Venezuela' },
  { code: '34', name: 'España' },
  { code: '1', name: 'USA / Canadá' },
];

async function submit() {
  saving.value = true;
  error.value = '';
  try {
    const response = await apiFetch('/api/sites', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        name: name.value,
        url: url.value,
        country_code: countryCode.value,
        phone: phone.value,
      }),
    });
    const data = await response.json();
    if (!response.ok) {
      throw new Error(data.error || 'No se pudo agregar el sitio');
    }
    name.value = '';
    url.value = '';
    phone.value = '';
    emit('created', data);
  } catch (err) {
    error.value = err.message;
  } finally {
    saving.value = false;
  }
}
</script>

<style scoped>
.form {
  display: grid;
  grid-template-columns: 1fr 1.3fr 160px 1fr auto;
  gap: 10px;
}

input,
select,
button {
  border-radius: 12px;
  border: 1px solid var(--line);
  padding: 12px 14px;
}

input,
select {
  background: var(--bg-soft);
  color: var(--text);
}

button {
  background: var(--up);
  color: #062016;
  font-weight: 700;
  border: 0;
}

button:disabled {
  opacity: 0.6;
}

.error {
  color: var(--down);
  margin: 8px 0 0;
  font-size: 14px;
}

@media (max-width: 900px) {
  .form {
    grid-template-columns: 1fr;
  }
}
</style>
