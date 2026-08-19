<template>
  <section class="settings">
    <button type="button" class="toggle" @click="open = !open">
      {{ open ? 'Ocultar WhatsApp' : 'Configurar WhatsApp' }}
    </button>

    <div v-if="open" class="panel">
      <p>
        Este monitor solo envía plantillas. El webhook del mismo WABA está en POS:
        <code>https://api-level.minegociolisto.com/api/webhooks/whatsapp</code>.
        Token y Phone Number ID van en <code>api/.env</code>.
        Cada sitio tiene su propio código de país y número; ahí llegan las alertas.
      </p>
      <form @submit.prevent="save">
        <label>
          Fallos seguidos para alertar
          <input v-model.number="failThreshold" type="number" min="1" max="10" />
        </label>
        <label class="check">
          <input v-model="digestEnabled" type="checkbox" />
          Resumen cada 6 horas: un WhatsApp por cada sitio (00:00, 06:00, 12:00 y 18:00)
        </label>
        <div class="actions">
          <button type="submit" :disabled="saving">Guardar</button>
          <button type="button" class="ghost" :disabled="digesting" @click="sendDigestNow">
            {{ digesting ? 'Enviando…' : 'Enviar resumen ahora' }}
          </button>
        </div>
      </form>
      <p v-if="notice" class="notice">{{ notice }}</p>
    </div>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { apiFetch } from '../api.js';

const open = ref(false);
const failThreshold = ref(3);
const digestEnabled = ref(true);
const saving = ref(false);
const digesting = ref(false);
const notice = ref('');

onMounted(load);

async function load() {
  const response = await apiFetch('/api/settings');
  const data = await response.json();
  failThreshold.value = data.fail_threshold || 3;
  digestEnabled.value = data.digest_enabled !== false;
}

async function save() {
  saving.value = true;
  notice.value = '';
  try {
    const response = await apiFetch('/api/settings', {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        fail_threshold: String(failThreshold.value),
        digest_enabled: digestEnabled.value,
      }),
    });
    const data = await response.json();
    if (!response.ok) throw new Error(data.error || 'No se pudo guardar');
    notice.value = data.whatsapp_configured
      ? 'Ajustes guardados. Las alertas salen al WhatsApp de cada sitio.'
      : 'Ajustes guardados. Activa WHATSAPP_ENABLED y el token en api/.env.';
  } catch (err) {
    notice.value = err.message;
  } finally {
    saving.value = false;
  }
}

async function sendDigestNow() {
  digesting.value = true;
  notice.value = '';
  try {
    const response = await apiFetch('/api/whatsapp/digest', { method: 'POST' });
    const data = await response.json().catch(() => ({}));
    if (!response.ok) throw new Error(data.detail || data.error || 'No se pudo enviar el resumen');
    const errors = (data.errors || []).join(' · ');
    notice.value = `Resumen: ${data.sent || 0} enviados, ${data.failed || 0} fallidos, ${data.skipped || 0} sin número.`
      + (errors ? ` ${errors}` : '');
  } catch (err) {
    notice.value = err.message;
  } finally {
    digesting.value = false;
  }
}
</script>

<style scoped>
.settings {
  margin-top: 8px;
}

.toggle,
.ghost {
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
}

p {
  color: var(--muted);
  margin-top: 0;
}

code {
  font-family: "IBM Plex Mono", monospace;
  color: var(--text);
  word-break: break-all;
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
  border-radius: 10px;
  padding: 10px 12px;
}

.check {
  display: flex;
  align-items: center;
  gap: 8px;
}

.check input {
  width: 16px;
  height: 16px;
  padding: 0;
}

.actions {
  display: flex;
  gap: 8px;
}

button[type='submit'] {
  background: var(--up);
  color: #062016;
  border: 0;
  border-radius: 10px;
  padding: 10px 14px;
  font-weight: 700;
}

.ghost {
  background: transparent;
  color: var(--text);
  border: 1px solid var(--line);
  border-radius: 10px;
  padding: 10px 14px;
}

.notice {
  margin: 12px 0 0;
  color: var(--text);
}
</style>
