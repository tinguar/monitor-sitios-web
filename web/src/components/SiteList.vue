<template>
  <main class="wrap">
    <header class="hero">
      <div>
        <p class="kicker">Monitor de sitios</p>
        <h1>¿Están activos tus sitios?</h1>
        <p class="lead">
          Laravel chequea cada {{ intervalMinutes }} min. WhatsApp alerta al caer o volver, y cada 6 horas un resumen por sitio.
          <template v-if="user?.email"> Sesión: {{ user.email }}.</template>
        </p>
      </div>
      <div class="stats">
        <div>
          <strong>{{ counts.up }}</strong>
          <span>Activos</span>
        </div>
        <div>
          <strong>{{ counts.down }}</strong>
          <span>Desconectados</span>
        </div>
        <div>
          <strong>{{ counts.slow }}</strong>
          <span>Lentos</span>
        </div>
      </div>
    </header>

    <AlertBanner v-if="downMessage" :message="downMessage" tone="down" />

    <section class="toolbar">
      <AddSiteForm @created="onCreated" />
      <div class="row">
        <button type="button" class="ghost" :disabled="checking" @click="runChecks">
          {{ checking ? 'Chequeando…' : 'Chequear ahora' }}
        </button>
        <label class="interval">
          Automático cada
          <select v-model.number="intervalMinutes" @change="saveInterval">
            <option v-for="mins in intervalOptions" :key="mins" :value="mins">
              {{ mins }} min
            </option>
          </select>
        </label>
        <WhatsAppSettings />
        <button type="button" class="ghost" @click="emit('logout')">Cerrar sesión</button>
      </div>
      <WhatsAppTests />
    </section>

    <p v-if="error" class="error">{{ error }}</p>

    <section v-if="sites.length" class="grid">
      <SiteCard
        v-for="site in sites"
        :key="site.id"
        :site="site"
        @remove="removeSite"
      />
    </section>
    <section v-else class="empty">
      Agrega tu primer sitio para empezar el monitoreo.
    </section>
  </main>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { apiFetch } from '../api.js';
import AddSiteForm from './AddSiteForm.vue';
import AlertBanner from './AlertBanner.vue';
import SiteCard from './SiteCard.vue';
import WhatsAppSettings from './WhatsAppSettings.vue';
import WhatsAppTests from './WhatsAppTests.vue';

defineProps({
  user: { type: Object, default: null },
});
const emit = defineEmits(['logout']);

const sites = ref([]);
const error = ref('');
const checking = ref(false);
const intervalMinutes = ref(1);
const intervalOptions = [1, 2, 5, 10, 15, 30, 60];
let timer;

const counts = computed(() => ({
  up: sites.value.filter((site) => site.status === 'up').length,
  down: sites.value.filter((site) => site.status === 'down').length,
  slow: sites.value.filter((site) => site.status === 'slow').length,
}));

const downMessage = computed(() => {
  const down = sites.value.filter((site) => site.status === 'down');
  if (!down.length) return '';
  if (down.length === 1) {
    return `No se detectó actividad, sitio desconectado: ${down[0].name}`;
  }
  return `No se detectó actividad. ${down.length} sitios desconectados.`;
});

onMounted(async () => {
  await load();
  await runChecks();
  startAutoCheck();
});

onUnmounted(() => {
  clearInterval(timer);
});

function startAutoCheck() {
  clearInterval(timer);
  const ms = Math.max(1, intervalMinutes.value) * 60 * 1000;
  timer = setInterval(() => {
    if (!checking.value) {
      runChecks();
    }
  }, ms);
}

async function load() {
  try {
    const response = await apiFetch('/api/sites');
    const data = await response.json();
    if (!response.ok) throw new Error(data.detail || data.error || 'No se pudo cargar');
    sites.value = data.sites || [];
    if (data.check_interval_minutes) {
      intervalMinutes.value = data.check_interval_minutes;
      if (!intervalOptions.includes(data.check_interval_minutes)) {
        intervalOptions.push(data.check_interval_minutes);
        intervalOptions.sort((a, b) => a - b);
      }
    }
    error.value = '';
  } catch (err) {
    error.value = 'No se pudo conectar con la API Laravel.';
  }
}

async function saveInterval() {
  const minutes = Math.min(60, Math.max(1, Number(intervalMinutes.value) || 1));
  intervalMinutes.value = minutes;
  try {
    await apiFetch('/api/settings', {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ check_interval_minutes: String(minutes) }),
    });
    startAutoCheck();
  } catch (err) {
    error.value = err.message;
  }
}

function onCreated(site) {
  const rest = sites.value.filter((item) => item.id !== site.id);
  sites.value = [site, ...rest];
}

async function runChecks() {
  checking.value = true;
  try {
    const response = await apiFetch('/api/checks/run', { method: 'POST' });
    const data = await response.json();
    if (!response.ok) throw new Error(data.detail || data.error || 'Falló el chequeo');
    sites.value = data.sites || [];
  } catch (err) {
    error.value = err.message;
  } finally {
    checking.value = false;
  }
}

async function removeSite(id) {
  if (!confirm('¿Quitar este sitio del monitor?')) return;
  const response = await apiFetch(`/api/sites/${id}`, { method: 'DELETE' });
  if (response.ok) {
    sites.value = sites.value.filter((site) => site.id !== id);
  }
}
</script>

<style scoped>
.wrap {
  width: min(1100px, calc(100% - 32px));
  margin: 0 auto;
  padding: 40px 0 80px;
  display: grid;
  gap: 20px;
}

.hero {
  display: flex;
  justify-content: space-between;
  gap: 24px;
  align-items: end;
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
  font-size: clamp(28px, 5vw, 44px);
}

.lead {
  color: var(--muted);
  max-width: 520px;
}

.stats {
  display: flex;
  gap: 12px;
}

.stats div {
  background: var(--bg-elev);
  border: 1px solid var(--line);
  border-radius: 16px;
  min-width: 110px;
  padding: 14px 16px;
}

.stats strong {
  display: block;
  font-size: 28px;
}

.stats span {
  color: var(--muted);
  font-size: 13px;
}

.toolbar,
.row {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  align-items: center;
}

.toolbar {
  display: grid;
}

.interval {
  display: flex;
  align-items: center;
  gap: 8px;
  color: var(--muted);
  font-size: 14px;
}

.interval select {
  background: var(--bg-soft);
  color: var(--text);
  border: 1px solid var(--line);
  border-radius: 12px;
  padding: 12px 14px;
}

.ghost {
  background: transparent;
  color: var(--text);
  border: 1px solid var(--line);
  border-radius: 12px;
  padding: 12px 14px;
  width: fit-content;
}

.grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 14px;
}

.empty,
.error {
  padding: 18px;
  border-radius: 16px;
  border: 1px dashed var(--line);
  color: var(--muted);
}

.error {
  border-style: solid;
  color: var(--down);
}

@media (max-width: 800px) {
  .hero {
    display: grid;
  }
}
</style>
