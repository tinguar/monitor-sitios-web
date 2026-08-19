# Monitor de sitios

Panel y API de código abierto para saber si tus sitios web están **activos**, **lentos** o **desconectados**, y avisar por **WhatsApp** (plantillas).

**Repositorio:** [tinguar/monitor-sitios-web](https://github.com/tinguar/monitor-sitios-web)  
**Licencia:** [MIT](https://github.com/tinguar/monitor-sitios-web/blob/main/LICENSE) — uso libre.

## Qué incluye

| Carpeta | Qué es |
|---------|--------|
| `web/` | Panel (Astro + Vue) |
| `api/` | API Laravel, chequeos HTTP y scheduler |

El monitor **solo envía** plantillas de WhatsApp. **No tiene webhook**. Si usas el mismo WABA que un POS, el webhook se queda en el POS.

## Cómo funciona

1. Cada sitio se registra con nombre, URL, país y número de WhatsApp.
2. Laravel hace GET a la URL. HTTP 200–399 = activo. Latencia alta = lento (no es caída).
3. **Caída:** 3 fallos seguidos → plantilla `monitor_sitio_caido` a **ese** número.
4. **Recuperación:** vuelve a responder → `monitor_sitio_activo`.
5. **Resumen:** 00:00, 06:00, 12:00 y 18:00 (America/Guayaquil) → un WhatsApp **por cada sitio** (`monitor_resumen`).
6. El intervalo “Automático cada X min” del panel **solo chequea HTTP**. No manda WhatsApp.

## Páginas

- [Instalación local](Instalacion-local.md)
- [Producción](Produccion.md)
- [Plantillas WhatsApp](WhatsApp-plantillas.md)
- [API](API.md)
- [Licencia](Licencia.md)
