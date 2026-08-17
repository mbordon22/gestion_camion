# Auditoría del sistema + plan para el MVP

**Fecha:** 17/08/2026
**Alcance:** auditoría de código completa (modelos, migraciones, rutas, controladores y vistas). Sin cambios en el código.

---

## PASO 1 — Qué hay hoy

### Stack

Laravel 11 · Blade + Tailwind (por CDN) · DataTables (jQuery, por CDN) · MySQL · Breeze para autenticación.

Dos dependencias instaladas y **sin usar**: Livewire 4 (no existe `app/Livewire`) y Yajra DataTables server-side (las tablas se renderizan enteras en el HTML y DataTables solo pagina del lado del cliente).

### Entidades

| Tabla | Campos principales |
|---|---|
| `camiones` | patente (única), marca, modelo, año, activo, observaciones |
| `viajes` | camion_id, fecha (fecha+hora de la orden de carga), fecha_carga, nro_ingreso, tipo_ingreso, motivo, **bolsas**, **precio_bolsa**, total, facturado, kg_netos, destino, observaciones |
| `combustible` | camion_id, fecha, litros, precio_litro, total, km_odometro, lugar, medio_pago_id, fecha_vencimiento |
| `mantenimiento` | camion_id, fecha, tipo (aceite/filtros/neumáticos/frenos/repuesto/service/otro), monto, km_actuales, proximo_service, detalle, medio_pago_id, fecha_vencimiento |
| `medios_pago` | nombre, tipo (efectivo/débito/transferencia/crédito), día de cierre, día de vencimiento, activo |
| `prestamos` | descripción, categoría (camión/personal), monto_total, cantidad_cuotas, valor_cuota, día de vencimiento, fecha primera cuota, medio_pago_id |
| `cuotas` | prestamo_id, número, fecha_venc, monto, pagada, fecha_pago |
| `users` | Breeze estándar |

### Relaciones

```
Camion ──1:N──> Viaje
       ──1:N──> Combustible
       ──1:N──> Mantenimiento

MedioPago ──1:N──> Combustible / Mantenimiento / Prestamo

Prestamo ──1:N──> Cuota   (borrado en cascada)
```

Dos ausencias que importan:

- **No hay relación `User ─ Camion`.** Cualquier usuario autenticado ve todos los datos de todos los camiones.
- **No hay relación `Viaje ─ gasto`.** Ningún gasto (combustible o mantenimiento) se puede imputar a un viaje.

### Funcionalidades que están de punta a punta

Todo esto tiene pantalla, formulario y flujo real de uso:

- **Camiones** — alta, edición, listado. Borrado inteligente: si el camión tiene movimientos se desactiva en vez de borrarse.
- **Viajes** — ABM completo, filtro por camión y por período (hoy/semana/mes/rango), tarjetas de resumen, y un switch de "facturado" que se actualiza por AJAX sin recargar la página, recalculando los totales en vivo. Está bien hecho.
- **Combustible** — ABM completo, filtro por camión y período, totales de litros y gasto.
- **Mantenimiento** — ABM completo, filtro por camión, banner de "próximo service".
- **Medios de pago** — ABM completo.
- **Préstamos** — ABM con generación automática del plan de cuotas y toggle de cuota pagada.
- **Pagos** — calendario de egresos futuros agrupado por mes (cuotas de préstamos + gastos a crédito según cierre/vencimiento de cada tarjeta), más el total atrasado. Es la pantalla más sofisticada del sistema.
- **Reportes** — ingresos − gastos por período y por camión, con resultado neto.
- **Autenticación** — login, recuperación de contraseña, perfil. El registro público está deshabilitado a propósito; los usuarios se crean por comando de consola.

### Uso real hasta hoy

La base local tiene 3 viajes (todos de junio 2026), 4 cargas de combustible, 7 mantenimientos, 5 préstamos con 39 cuotas y 1 camión.

Dato relevante para el punto 2 del núcleo: **de las 4 cargas de combustible, ninguna tiene el km del odómetro cargado.** El campo existe, es opcional, y en la práctica nunca se completó.

### Deuda técnica detectada

- `resources/views/dashboard.blade.php` y `layouts/navigation.blade.php` quedaron huérfanas (restos de Breeze). El layout real es `layouts/app.blade.php`.
- **Tailwind, jQuery y DataTables se cargan por CDN.** Sin internet la aplicación se ve rota y las tablas no funcionan. Para un usuario que carga datos desde el celular en una balanza con señal mala, esto importa.
- Coexisten dos sistemas de assets: el CDN de Tailwind y Vite + Tailwind en `package.json`.
- Sin tests del dominio (solo los que trajo Breeze).
- `MantenimientoController::index` trae **todo el histórico** sin filtro de fecha, y `$proximoService` toma el último registro con ese campo **sin filtrar por camión** — con dos camiones muestra el dato del equivocado.
- El campo `total` es `readonly` en el formulario pero se valida como `required`. Si el JavaScript falla, el formulario no se puede enviar y el mensaje de error no explica por qué. Además el total viaja desde el navegador en vez de calcularse en el servidor.

---

## PASO 2 — Contraste contra el núcleo del MVP

### 1. Viajes: origen/destino, carga, cobro, fecha, rentabilidad por viaje

**PARCIAL — y con el bloqueante más grande del sistema.**

Está: fecha (dos, incluso), destino, cobro, facturado, observaciones.

Falta:

- **Origen** no existe como campo. Solo hay destino.
- **La carga está modelada exclusivamente como bolsas de azúcar.** `bolsas` es obligatorio con mínimo 1, y el total se calcula siempre como `bolsas × precio_bolsa`. Un transportista que lleve granos a granel, hacienda, o cobre un flete fijo por viaje **no puede cargar un viaje en el sistema**. Los campos `nro_ingreso`, `tipo_ingreso` y `motivo` son vocabulario de ingenio azucarero.
- **La rentabilidad por viaje no existe.** No hay forma de saber qué costó un viaje: ningún gasto se asocia a un viaje, y no se registran los km recorridos. La rentabilidad solo existe agregada por período.

Esto es lo primero a resolver: hoy el sistema sirve para *tu* camión en zafra, no para el público que describís.

### 2. Combustible: litros, costo, kilometraje → consumo por km

**PARCIAL — el dato se guarda pero nunca se usa.**

Está: litros, precio por litro, total, km del odómetro, lugar, medio de pago.

Falta:

- **El consumo no se calcula en ningún lado.** Verificado por búsqueda en todo el proyecto: no hay una sola división de litros sobre km. `km_odometro` se guarda y se muestra como una columna más en la tabla.
- **El km del odómetro es opcional y está vacío en el 100% de los registros reales.** Sin ese dato el consumo es incalculable. Hay que volverlo obligatorio o al menos muy insistente.
- No existe el concepto de "tanque lleno" ni validación de que el odómetro sea creciente. Sin eso, una carga parcial o un km mal tipeado produce un consumo absurdo, y el usuario deja de confiar en el número.

### 3. Mantenimiento: gasto, tipo, fecha/km

**COMPLETO.** Es el punto más sano de los cinco.

Están los tres datos pedidos: monto, tipo (con 7 opciones que cubren service, cubiertas —"neumáticos"— y reparaciones) y fecha + km. El ABM es completo y funciona.

Dos detalles menores: el listado no tiene filtro por fecha (trae siempre todo), y las columnas de "km actuales" y "próximo service" están comentadas en la tabla — el dato se carga pero no se ve.

### 4. Un número resumen: rentabilidad por camión, por mes

**PARCIAL.**

Está: `/reportes` calcula ingresos − combustible − mantenimiento, filtrable por camión y por período (semana/quincena/mes/rango libre), con el resultado neto destacado. La aclaración de devengado vs. caja está muy bien puesta y es más cuidada de lo que suele verse.

Falta:

- **No hay vista por mes.** Es un período por vez. No se pueden ver los meses en una tabla, ni comparar uno contra otro, ni ver cómo evoluciona la temporada. Para alguien que trabaja por zafra, ver la curva del mes a mes es justamente el número que importa.
- **No hay comparación entre camiones.** Con 2 camiones hay que cambiar el filtro y recordar el número anterior.
- Los préstamos quedan fuera del resultado a propósito, y contablemente está bien. Pero si el camión se está pagando en cuotas, el "resultado neto" que ve el dueño está inflado respecto de la plata que le queda. Falta al menos mostrar al lado cuánto pesan las cuotas del período.
- No hay costo por km ni margen porcentual, solo el número absoluto.

### 5. Alertas de vencimientos y próximo service por km

**PRÁCTICAMENTE INEXISTENTE.** Es el hueco más grande.

- **Seguro, RTO/VTV y licencia no existen.** Ni campos en la tabla `camiones`, ni tabla de vencimientos, ni pantalla, ni alerta. Cero.
- **Próximo service por km: existe el campo, pero no es una alerta.** Hay un banner naranja en `/mantenimiento` que dice "Próximo service programado a los X km". Pero:
  - **no compara contra el km actual del camión**, porque el km actual no existe en ningún lado del sistema (el odómetro vive en combustible y viene vacío). Nunca puede avisar que estás cerca o pasado: es un cartelito informativo.
  - toma el último registro con `proximo_service` **sin filtrar por camión**, así que con dos camiones muestra el dato del equivocado.
  - solo se ve si entrás a Mantenimiento.
- **No hay ningún lugar donde el dueño vea "qué se me viene".** El sistema abre directo en la lista de viajes.

### Resumen

| # | Punto del núcleo | Estado |
|---|---|---|
| 1 | Viajes con rentabilidad por viaje | 🟡 Parcial — carga atada a bolsas de azúcar, sin origen, sin costos por viaje |
| 2 | Combustible → consumo por km | 🟡 Parcial — se guarda el dato, nunca se calcula, y en la práctica viene vacío |
| 3 | Mantenimiento | 🟢 Completo |
| 4 | Rentabilidad por camión por mes | 🟡 Parcial — existe por período, falta la vista mes a mes y la comparación |
| 5 | Alertas de vencimientos y service | 🔴 Casi inexistente — documentación no existe; el service no alerta |

---

## PASO 3 — Plan priorizado

### P0 — Sin esto el MVP no sirve

**1. Destrabar el tipo de carga del viaje** · Esfuerzo: **grande**

Hoy un viaje es obligatoriamente "N bolsas × $precio". Hay que permitir tres formas de cobro: monto fijo por viaje (el caso más común), por unidad (bolsas, pallets, cabezas) y por peso (tonelada). El formulario debería preguntar primero "¿cómo cobrás este viaje?" y mostrar solo los campos que correspondan. Agregar **origen** junto a destino, y **km recorridos**. Los campos del ingenio (nro. de ingreso, tipo de ingreso, motivo) pasan a ser opcionales y agrupados aparte, no en el cuerpo principal del formulario.

Es grande porque toca la tabla, el modelo, el formulario, el listado y los reportes, y hay que migrar los 3 viajes existentes sin romperlos.

**2. Vencimientos de documentación con alertas** · Esfuerzo: **mediano**

Tabla nueva de vencimientos asociada al camión (seguro, RTO/VTV, patente, licencia del chofer, y que se puedan agregar otros), con fecha de vencimiento y un aviso configurable en días. Pantalla simple de carga y un estado visible: verde / amarillo (vence pronto) / rojo (vencido). Es el punto 5 del núcleo y hoy no existe nada.

**3. Pantalla de inicio: "¿cómo venimos?"** · Esfuerzo: **mediano**

Hoy el sistema abre en una tabla de viajes con filtros. La primera pregunta del dueño no es "¿qué viajes hice?" sino "¿cómo vengo este mes y qué se me viene encima". Una sola pantalla con: resultado del mes en curso (un número grande), viajes del mes, alertas de vencimientos y de service, y los dos botones que más se usan: "Cargar viaje" y "Cargar combustible". Es donde se juega la percepción de simplicidad de todo el producto.

**4. Consumo de combustible que se calcule de verdad** · Esfuerzo: **mediano**

Volver el km del odómetro obligatorio, agregar una marca de "cargué el tanque lleno", validar que el odómetro sea mayor al de la carga anterior del mismo camión, y calcular y mostrar litros cada 100 km (o km por litro) entre cargas consecutivas de tanque lleno. Mostrar el promedio del período en la pantalla de combustible y en el inicio. Sin esto el punto 2 del núcleo no existe.

**5. Rentabilidad mes a mes** · Esfuerzo: **chico a mediano**

En Reportes, agregar una tabla de los últimos 12 meses: mes, ingresos, combustible, mantenimiento, resultado. Con selector de camión y una fila de totales. Es el "número resumen simple" del punto 4 y es barato de hacer porque los datos ya están.

**6. Km actual del camión y alerta de service real** · Esfuerzo: **chico** (si ya están el 1 y el 4)

Derivar el km actual de cada camión del último odómetro registrado (combustible, mantenimiento o viaje) y comparar contra el `proximo_service`. Mostrar "faltan 2.400 km para el service" o "service vencido hace 800 km", en el inicio y en Mantenimiento. De paso, corregir el bug de que el banner no filtra por camión.

**7. Decidir el aislamiento de datos entre usuarios** · Esfuerzo: **mediano** ahora, grande después

Hoy cualquier usuario autenticado ve todos los datos de todos los camiones. El registro público está cerrado y los usuarios se crean por consola, así que no hay una filtración abierta. Pero si dos clientes van a usar la misma instalación, hace falta que cada uno vea solo lo suyo (relación usuario ↔ camiones y filtrado en todas las consultas). Lo pongo en P0 no porque haga falta para que funcione, sino porque **es barato ahora y caro después**: cada pantalla nueva que agregues sin esto es una pantalla más para corregir luego. Si el plan es una instalación separada por cliente, bajalo a P2 y listo — pero decidilo antes de seguir construyendo.

### P1 — Mejora mucho, se puede vivir sin eso al principio

**8. Rentabilidad por viaje** · Esfuerzo: **grande**

Poder imputar gastos a un viaje concreto (combustible asociable a un viaje, peajes, comidas, ayudante) y mostrar ganancia neta por viaje. Es lo que pide el punto 1 del núcleo en su forma completa. Va en P1 porque exige que el usuario cargue más datos por viaje, y para validar el MVP alcanza con el resultado por mes más el consumo promedio. Una versión intermedia y mucho más barata: prorratear el combustible del período por km recorridos y mostrar un costo estimado por viaje.

**9. Calcular los totales en el servidor** · Esfuerzo: **chico**

Hoy el total llega desde el navegador, con el campo `readonly` y a la vez obligatorio. Si el JavaScript no carga, el usuario no puede guardar y no entiende por qué. Calcularlo en el controlador y dejar el campo del formulario como referencia visual.

**10. Sacar la dependencia de los CDN** · Esfuerzo: **chico**

Compilar Tailwind con Vite (ya está instalado) y servir jQuery y DataTables localmente. Hoy sin internet la aplicación se ve rota.

**11. Filtro de fecha en Mantenimiento y mostrar las columnas de km** · Esfuerzo: **chico**

El listado trae todo el histórico siempre, y las columnas de km actuales y próximo service están comentadas en el HTML.

**12. Adjuntar foto del comprobante** · Esfuerzo: **mediano**

Una foto del ticket de combustible o de la factura del service, sacada con el celular en el momento. Para este público reemplaza la carpeta de papeles, y es de las cosas que más se piden una vez que el sistema se usa en serio.

**13. Limpieza de código muerto** · Esfuerzo: **chico**

Sacar Livewire y Yajra DataTables del `composer.json` si no se van a usar, y borrar las vistas huérfanas de Breeze.

### P2 — Más adelante

- **Comparativa lado a lado de dos camiones** en una sola pantalla.
- **Exportar a Excel y PDF** el reporte del mes (para el contador).
- **Aviso de vencimientos por WhatsApp o email**, no solo dentro del sistema.
- **PWA / instalable en el celular**, con carga offline que sincronice después.
- **Choferes**: quién manejó cada viaje, y liquidación por chofer.
- **Costo por km consolidado** (combustible + mantenimiento + cuotas + seguro sobre km recorridos), que es el número que un transportista usa para cotizar un flete.
- **Papelera / deshacer** para registros borrados.

---

## PASO 4 — Revisión de simplicidad

El usuario final nunca usó un sistema de gestión. Esto es lo que hoy le va a costar:

**1. El formulario de viaje pide 12 campos y abre con dos fechas.** "Fecha y hora orden de carga" y "Fecha de carga real", una al lado de la otra, la primera con hora obligatoria. Es muy difícil saber cuál es cuál sin que alguien te lo explique. Para la mayoría de los transportistas hay una sola fecha: el día que hizo el viaje.

**2. Hay jerga de ingenio azucarero en el formulario principal.** "Nro. Ingreso", "Tipo Ingreso", "Motivo", "Kg Netos" son términos de la planta, no del oficio del transportista. Alguien que transporta otra cosa no sabe qué poner ahí, y al ser campos visibles y de texto libre, va a sentir que le falta información para completar el formulario.

**3. "Facturado" probablemente no es lo que el dueño quiere marcar.** Facturar y cobrar son cosas distintas, y a un transportista con un camión le importa **si cobró**. Vale la pena revisar si el switch debería decir "Cobrado", o si hacen falta los dos estados.

**4. El total es de solo lectura.** Si el viaje se cobró un monto fijo, no hay forma de escribirlo. Y si el JavaScript no cargó, el campo queda vacío, el formulario no se envía, y el error que aparece no explica el problema.

**5. Siete ítems en el menú, y tres hablan de plata.** Viajes, Combustible, Camiones, Mantenimiento, Préstamos, Pagos, Reportes. Entre "Pagos", "Reportes" y "Préstamos" no es evidente cuál mirar para responder "¿estoy ganando?". Con la pantalla de inicio del punto 3 se puede reagrupar el menú y esconder lo que se usa poco.

**6. No hay pantalla de inicio.** El sistema abre en una tabla con filtros. Es lo primero que ve alguien que entra por primera vez y es la pantalla menos amistosa del sistema.

**7. El filtro por defecto es "Rango libre" de los últimos 60 días.** El default más útil sería "Este mes". Además los períodos disponibles son inconsistentes: "Hoy" está en Viajes pero no en Reportes, "Quincena" está en Reportes pero no en Viajes.

**8. El selector de camión aparece en todos lados aunque haya un solo camión.** Con un camión debería preseleccionarse y no mostrarse; el selector aparece recién cuando hay dos o más.

**9. Dos niveles de filtro en la misma pantalla.** Arriba el filtro de período del sistema, y dentro de la tabla el buscador y el "Mostrar N registros" de DataTables. Son dos cosas que hacen lo mismo a los ojos del usuario.

**10. "Medio de pago" y "Fecha de pago" en cada carga de combustible.** La lógica de cierre y vencimiento de tarjeta está muy bien resuelta técnicamente, pero es una sofisticación financiera muy por encima del usuario objetivo: para la mayoría es "pagué en efectivo". Debería estar colapsada detrás de algo como "¿lo pagaste con tarjeta?" y no ocupar dos campos visibles en el formulario de todos los días.

**11. Borrar no tiene vuelta atrás.** Solo un `confirm()` del navegador. Un dueño que borra un viaje por error pierde el dato para siempre.

**12. Nada está pensado para el celular.** Las tablas tienen hasta 10 columnas con scroll horizontal. Este usuario carga el viaje desde el teléfono, parado en la balanza, no sentado en una PC. Es probablemente el cambio más importante de todos y conviene tenerlo en cuenta desde el rediseño del formulario de viaje (punto 1 de P0), no después.

---

## Recomendación de arranque

Si hubiera que elegir un solo bloque para empezar: **P0 puntos 1 y 3** (viaje genérico + pantalla de inicio). El primero es lo que te permite salir de tu propio camión y mostrárselo a otro transportista; el segundo es lo que hace que ese transportista entienda el sistema en los primeros diez segundos. Los puntos 2, 4, 5 y 6 se apoyan sobre esos dos y son bastante mecánicos una vez resueltos.
