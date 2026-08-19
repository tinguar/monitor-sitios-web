<template>
  <section class="tests">
    <button type="button" class="toggle" @click="open = !open">
      {{ open ? 'Ocultar pruebas WP' : 'Pruebas WhatsApp' }}
    </button>

    <form v-if="open" class="panel" @submit.prevent="send">
      <p>
        Elige la plantilla y envíala al número de prueba. Por defecto Ecuador
        <strong>0992889078</strong> (+593).
      </p>

      <label>
        Plantilla
        <select v-model="template" required>
          <option value="down">monitor_sitio_caido — sitio caído</option>
          <option value="up">monitor_sitio_activo — sitio recuperado</option>
          <option value="digest">monitor_resumen — resumen de este sitio</option>
        </select>
      </label>

      <div class="row">
        <label>
          País
          <select v-model="countryCode">
            <option value="593">+593 Ecuador</option>
            <option value="57">+57 Colombia</option>
            <option value="51">+51 Perú</option>
            <option value="52">+52 México</option>
            <option value="1">+1 USA / Canadá</option>
          </select>
        </label>
        <label>
          Número
          <input v-model.trim="phone" type="tel" inputmode="numeric" placeholder="0992889078" required />
        </label>
      </div>

      <button type="submit" :disabled="busy">
        {{ busy ? 'Enviando…' : `Enviar ${names[template]}` }}
      </button>
      <p v-if="notice" class="notice" :class="{ bad: failed }">{{ notice }}</p>
    </form>
  </section>
</template>

<script setup>
import { ref } from 'vue';
import { apiFetch } from '../api.js';

const open = ref(true);
const template = ref('down');
const countryCode = ref('593');
const phone = ref('0992889078');
const busy = ref(false);
const notice = ref('');
const failed = ref(false);

const names = {
  down: 'monitor_sitio_caido',
  up: 'monitor_sitio_activo',
  digest: 'monitor_resumen',
};

async function send() {
  busy.value = true;
  notice.value = '';
  failed.value = false;
  try {
    const response = await apiFetch('/api/whatsapp/test', {
      method: 'POST',
      body: JSON.stringify({
        template: template.value,
        country_code: countryCode.value,
        phone: phone.value,
      }),
    });
    const data = await response.json().catch(() => ({}));
    if (!response.ok) {
      throw new Error(data.detail || data.error || 'Meta rechazó el envío');
    }
    notice.value = `Enviado ${names[template.value]} a +${data.to}.`;
  } catch (err) {
    failed.value = true;
    notice.value = err.message;
  } finally {
    busy.value = false;
  }
}
</script>

<style scoped>
.tests {
  width: 100%;
}

.toggle {
  background: transparent;
  color: var(--text);
  border: 1px solid var(--line);
  border-radius: 10px;
  padding: 8px 12px;
}

.panel {
  margin-top: 12px;
  padding: 16px;
  border: 1px solid var(--line);
  border-radius: 16px;
  background: var(--bg-soft);
  display: grid;
  gap: 12px;
  max-width: 640px;
}

p {
  color: var(--muted);
  margin: 0;
}

label {
  display: grid;
  gap: 6px;
  font-size: 13px;
  color: var(--muted);
}

.row {
  display: grid;
  grid-template-columns: 180px 1fr;
  gap: 10px;
}

input,
select,
button[type='submit'] {
  border-radius: 10px;
  border: 1px solid var(--line);
  padding: 10px 12px;
  font: inherit;
}

input,
select {
  background: var(--bg);
  color: var(--text);
  width: 100%;
}

button[type='submit'] {
  background: var(--up);
  color: #062016;
  font-weight: 700;
  border: 0;
  width: fit-content;
}

button[type='submit']:disabled {
  opacity: 0.6;
}

.notice {
  color: var(--up);
}

.notice.bad {
  color: var(--down);
}

@media (max-width: 700px) {
  .row {
    grid-template-columns: 1fr;
  }
}
</style>
