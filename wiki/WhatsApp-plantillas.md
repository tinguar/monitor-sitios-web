# Plantillas de WhatsApp

Categoría **Utility**, idioma **`es`**, sin encabezado ni botones. El cuerpo **no puede empezar ni terminar** en una variable: cierra siempre con una frase fija.

El monitor **no recibe** webhooks. Solo hace `POST` a Graph (`/PHONE_NUMBER_ID/messages`).

Usa el mismo token de System User y Phone Number ID que el resto de tu WABA.

## `monitor_sitio_caido`

Cuando el sitio falla **3 veces seguidas**. 5 variables.

```
No se detectó actividad, sitio desconectado.

Sitio: {{1}}
URL: {{2}}
Qué pasó: {{3}}
Error técnico: {{4}}
Tiempo: {{5}}.

Revisa el sitio cuando puedas.
```

| Var | Ejemplo |
|-----|---------|
| {{1}} | Tinguar |
| {{2}} | https://tinguar.com |
| {{3}} | El sitio tiene un error interno y no puede mostrar la página. |
| {{4}} | HTTP 500 |
| {{5}} | 1.8 segundos |

## `monitor_sitio_activo`

Cuando el sitio vuelve a responder. 4 variables.

```
El sitio volvió a estar activo.

Sitio: {{1}}
URL: {{2}}
Qué pasó: {{3}}
Tiempo: {{4}}.

Todo en orden por ahora.
```

| Var | Ejemplo |
|-----|---------|
| {{1}} | Tinguar |
| {{2}} | https://tinguar.com |
| {{3}} | El sitio volvió a responder bien. |
| {{4}} | 0.4 segundos |

## `monitor_resumen`

Cada 6 horas (**00, 06, 12, 18** hora Ecuador). **Un mensaje por sitio**, al número de ese sitio. 3 variables.

```
Resumen de tu sitio.

Tu sitio {{1}} {{2}}.
Dirección: {{3}}.

Este aviso sale cada 6 horas.
```

| Var | Ejemplo |
|-----|---------|
| {{1}} | Tinguar |
| {{2}} | está activo, sin novedades, funcional al 100% |
| {{3}} | https://tinguar.com |

Otros textos de {{2}}:

- lento: `está activo, pero responde más lento de lo normal`
- caído: `está desconectado y necesita revisión`

Deben estar **Approved** en Meta antes de las pruebas del panel.
