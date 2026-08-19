<template>
  <article class="card">
    <header>
      <div>
        <h3>{{ site.name }}</h3>
        <a :href="site.url" target="_blank" rel="noreferrer">{{ site.url }}</a>
      </div>
      <StatusBadge :status="site.status" :text="badgeText" />
    </header>

    <p class="label">{{ site.label }}</p>

    <dl>
      <div>
        <dt>HTTP</dt>
        <dd>{{ site.last_http_code ?? '—' }}</dd>
      </div>
      <div>
        <dt>Tiempo</dt>
        <dd>{{ site.last_response_ms != null ? site.last_response_ms + ' ms' : '—' }}</dd>
      </div>
      <div>
        <dt>SSL</dt>
        <dd :class="{ warn: site.ssl_expiring }">
          {{ site.ssl_days_left != null ? site.ssl_days_left + ' días' : '—' }}
        </dd>
      </div>
      <div>
        <dt>Fallos</dt>
        <dd>{{ site.consecutive_failures }}</dd>
      </div>
      <div>
        <dt>WhatsApp</dt>
        <dd>{{ site.whatsapp_e164 ? '+' + site.whatsapp_e164 : '—' }}</dd>
      </div>
    </dl>

    <footer>
      <time>{{ checkedLabel }}</time>
      <button type="button" class="danger" @click="$emit('remove', site.id)">Quitar</button>
    </footer>
  </article>
</template>

<script setup>
import { computed } from 'vue';
import StatusBadge from './StatusBadge.vue';

const props = defineProps({
  site: { type: Object, required: true },
});

defineEmits(['remove']);

const badgeText = computed(() => {
  const map = {
    up: 'Activo',
    down: 'Desconectado',
    slow: 'Lento',
    retrying: 'Reintentando',
    unknown: 'Sin datos',
  };
  return map[props.site.status] || 'Sin datos';
});

const checkedLabel = computed(() => {
  if (!props.site.last_checked_at) return 'Aún no se ha chequeado';
  const date = new Date(props.site.last_checked_at);
  return 'Último chequeo: ' + date.toLocaleString('es');
});
</script>

<style scoped>
.card {
  background: var(--bg-elev);
  border: 1px solid var(--line);
  border-radius: 18px;
  padding: 18px;
  box-shadow: var(--shadow);
  display: grid;
  gap: 12px;
}

header {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  align-items: flex-start;
}

h3 {
  margin: 0 0 4px;
  font-size: 18px;
}

a {
  color: var(--muted);
  text-decoration: none;
  font-family: "IBM Plex Mono", monospace;
  font-size: 12px;
  word-break: break-all;
}

a:hover {
  color: var(--text);
}

.label {
  margin: 0;
  color: var(--muted);
  font-size: 14px;
}

dl {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 8px;
  margin: 0;
}

dt {
  color: var(--muted);
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: 0.08em;
}

dd {
  margin: 4px 0 0;
  font-family: "IBM Plex Mono", monospace;
  font-size: 13px;
}

.warn {
  color: var(--slow);
}

footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 8px;
}

time {
  color: var(--muted);
  font-size: 12px;
}

.danger {
  background: transparent;
  color: var(--down);
  border: 1px solid color-mix(in srgb, var(--down) 40%, transparent);
  border-radius: 10px;
  padding: 6px 10px;
}

@media (max-width: 720px) {
  dl {
    grid-template-columns: repeat(2, 1fr);
  }
}
</style>
