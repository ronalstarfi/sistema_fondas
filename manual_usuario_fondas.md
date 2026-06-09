# Manual de Usuario - Sistema FONDAS

## Introducción
Este manual describe el uso del sistema de gestión de tickets FONDAS para todos los roles: Solicitante, Especialista, Técnico y Alta Gerencia.

El sistema permite registrar solicitudes, gestionar tickets, recibir notificaciones automáticas y monitorear estados.

---

## 1. Acceso al sistema

### 1.1 Página de Login
- URL: `login.php`
- Campos:
  - Tipo de documento: `V`, `E`, `J`
  - Cédula: solo números, máximo 8 dígitos
  - Contraseña
- En caso de error se muestra un mensaje en la pantalla.
- Hay un enlace para recuperar contraseña: `gestion_clave.php`.
- Hay un botón para registrarse: `registro_usuario.php`.

#### Captura: Pantalla de Login
- Archivo: `screenshots/login.png` (colocar captura real en la carpeta `screenshots/`).
- Descripción: muestra los campos, enlaces y botones principales.

### 1.2 Roles y redirección
- Si el usuario es Especialista, inicia sesión en `views/home_especialista.php`.
- Si el usuario es Solicitante, inicia sesión en `index_solicitante.php`.

Los roles principales son:
- `Solicitante`
- `Especialista`
- `Tecnico`
- `Jefe`

---

## 2. Registro de usuario y recuperación de contraseña

### 2.1 Registro de solicitante
- El usuario puede invocar `registro_usuario.php` desde la pantalla de login.
- El sistema valida que la cédula exista y permita crear la contraseña.
- Después de registrarse, el usuario puede iniciar sesión con la nueva contraseña.

#### Campos del formulario de registro
- Cédula (numérico, requerido)
- Nombre completo (texto, requerido)
- Cargo / Gerencia / Ubicación (opcional/requerido según implementación)
- Correo electrónico (opcional)
- Contraseña (requerido; minima 6 caracteres por defecto)
- Confirmar contraseña (debe coincidir)

#### Mensajes y validaciones
- Mensaje de éxito: "Registro completado. Revise su correo (si aplica) o inicie sesión." 
- Errores comunes: cédula inválida, contraseñas no coinciden, campos requeridos vacíos.

#### Captura: Registro
- Archivo: `screenshots/registro_usuario.png`.

### 2.2 Recuperación de clave
- La opción `¿Olvidó su contraseña?` dirige a `gestion_clave.php`.
- El sistema permite recuperar o cambiar la clave según el flujo implementado.

#### Flujo de recuperación
1. Usuario ingresa cédula y/o correo en `gestion_clave.php`.
2. Sistema valida existencia y envía instrucciones o permite crear nueva clave (`nueva_clave.php`).
3. `procesar_recuperacion.php` gestiona el cambio y `guardar_clave.php` lo persiste.

#### Mensajes
- Éxito: "Contraseña actualizada".
- Error: "Usuario no encontrado" o "Token inválido/expirado".

---

## 3. Panel de Solicitante

### 3.1 Acceso
- URL: `index_solicitante.php`
- Muestra dos opciones principales:
  - Crear nueva solicitud (`registro.php`)
  - Ver mis tickets (`ver_tickets.php`)

#### Elementos de la pantalla
- Botón `Nueva solicitud` -> abre `registro.php`.
- Panel/listado rápido con estado de últimos tickets.
- Enlaces para editar perfil o cerrar sesión (`logout.php`).

### 3.2 Crear nueva solicitud
- URL: `registro.php`
- El solicitante visualiza sus datos:
  - Cédula
  - Nombre
  - Ubicación / Gerencia
- Campos del formulario:
  - Área del problema (requerido)
  - Tipo de equipo (requerido)
  - Marca (requerido)
  - Descripción de la falla (requerido)

#### Botones y acciones
- `Enviar` (guardar solicitud) -> llama a `procesar_registro.php`. Muestra pantalla de confirmación con ID de ticket.
- `Limpiar` o `Cancelar` -> descarta el formulario y regresa al panel.

#### Mensajes de validación en servidor
- Campos requeridos vacíos -> mensaje específico por campo.
- Si hay error en base de datos -> mensaje genérico "Error al crear la solicitud".

### 3.3 Áreas disponibles
- Soporte Técnico
- Infraestructura
- Desarrollo
- Impresoras y Toner
- SIGA

### 3.4 Asignación automática
- El sistema asigna el ticket al técnico activo con menor carga en el área correspondiente.
- Si el ticket es generado por un especialista y pertenece a su área activa, puede auto-asignarse.

### 3.5 Tipos y marcas dinámicos
- Al seleccionar el área, el sistema carga los tipos y marcas disponibles para esa área desde `ajax/get_options.php`.
- Esto ayuda a asegurar datos correctos y consistentes.

### 3.6 Confirmación de registro
- Después de enviar el formulario, aparece una pantalla de éxito con el ID del ticket.
- Desde allí se puede ir a `ver_tickets.php`.

---

## 4. Consulta de tickets

### 4.1 Página de tickets
- URL: `ver_tickets.php`
- Muestra una tabla con los tickets disponibles.
- Si el usuario es Solicitante, ve únicamente sus propios tickets.
- Si el usuario es Especialista o Técnico, ve todos los tickets abiertos y en proceso.

### 4.2 Columnas principales
- ID
- Detalles de la falla
- Solicitante / Ubicación (solo especialistas/técnicos)
- Técnico asignado
- Estado
- Fecha

#### Columnas adicionales posibles
- Prioridad
- Tiempo transcurrido (desde creación)
- Acciones (ACEPTAR, ASIGNAR, CERRAR, VER DETALLE, GENERAR PDF)

### 4.3 Filtros y búsquedas
- Búsqueda global por palabra clave: ticket, falla, técnico.
- Filtros de fecha: Desde / Hasta.
- Botón para limpiar filtros.

#### Uso de filtros
- Seleccionar intervalo de fechas y presionar `Buscar`.
- `Limpiar filtros` restablece la lista al estado por defecto.

### 4.4 Estados de ticket
- ABIERTO
- EN PROCESO
- URGENTE
- CERRADO

#### Transiciones y reglas
- ABIERTO -> ACEPTAR (especialista/técnico) -> EN PROCESO.
- EN PROCESO -> CERRAR (cuando la falla está resuelta) -> CERRADO.
- Un ticket puede etiquetarse como URGENTE automáticamente o manualmente.
- Guardar comentario o detalle al cerrar ticket (registro de auditoría).

### 4.5 Tiempos de urgencia
- Un ticket pasa a urgente si está abierto más de 1 hora.
- En la tabla se muestra como `URGENTE (XH)`.

> Nota: Este umbral puede estar definido en la lógica del sistema (`ajax/check_new_tickets.php` o en el backend).

---

## 5. Flujo de Especialista y Técnico

### 5.1 Panel de Especialista
- URL: `views/home_especialista.php`
- Muestra accesos a los módulos disponibles:
  - Gestión de Tickets
  - Control de Personal
  - Estadísticas y Auditoría (solo rol Jefe)

#### Widgets comunes
- Resumen de tickets asignados
- Botón para `Ver tickets` -> `ver_tickets.php`
- Enlaces rápidos para generar reportes (`views/generate_reporte_pdf.php`)

### 5.2 Gestión de tickets
- El especialista accede a `ver_tickets.php` desde su panel.
- Puede aceptar un ticket en estado `ABIERTO`.
- Al aceptar, el ticket cambia a `EN PROCESO` y el especialista recibe la asignación.

#### Botones dentro de la fila de ticket
- `ACEPTAR`: asigna el ticket al especialista (actualiza `especialista_id`) y cambia estado.
- `ASIGNAR` (si existe): permite reasignar a otro técnico.
- `VER DETALLE`: abre `cerrar_ticket_detalle.php` o similar con historial.
- `CERRAR`: cierra el ticket, solicita confirmación y registra cierre.

### 5.3 Notificaciones automáticas
- En `ver_tickets.php` el sistema consulta automáticamente nuevos tickets cada 3 segundos.
- Si hay tickets nuevos, suena una notificación y la página se recarga automáticamente.
- Si el navegador tiene permiso, también se muestra notificación de escritorio.
- En el panel de especialista también se muestra una alerta visual con botón para ir a tickets.

#### Endpoint y frecuencia
- `ajax/check_new_tickets.php` responde con JSON indicando nuevos tickets.
- Frecuencia por defecto: 3 segundos (configurable en el front-end).

### 5.4 Aceptar ticket
- En la columna `Estado`, el especialista/técnico ve el botón `ACEPTAR` para tickets abiertos.
- Al aceptar, el sistema asigna su ID como `especialista_id` y suma 1 a `tickets_activos`.

#### Efectos colaterales
- Se registra evento en auditoría (`auditoria_sistema.php`).
- Se actualizan contadores de carga del técnico.

---

## 6. Funcionalidades adicionales

### 6.1 Registro de auditoría
- El sistema registra eventos de login exitoso y fallido en `auditoria_general`.
- Se guarda IP, tipo de movimiento y descripción.

#### Consultar auditoría
- Página: `auditoria_sistema.php` (requiere permisos de jefe/administrador).
- Filtros: fecha, usuario, tipo de evento.

### 6.2 Administración de datos
- El sistema utiliza tablas `tipo`, `marca`, `solicitud`, `especialista` y `solicitante`.
- Las consultas de listado traen tipo y marca del equipo desde las tablas relacionadas.

#### Scripts y mantenimiento
- `scripts/init_metadata.php`: inicializa catálogos.
- `scripts/generate_mapping.php` y `mapping_suggested.json`: ayudan en mapeos.
- `scripts/check_*_schema.php`: validadores del esquema.

### 6.3 Sesión y seguridad
- Se requiere sesión iniciada para acceder a `ver_tickets.php` y `registro.php`.
- Si no hay sesión, redirige a `login.php`.

#### Reglas de seguridad adicionales
- Tiempo de expiración de sesión (según configuración PHP).
- Registros de intentos de login en `auditoria_general`.
- Políticas de contraseña (longitud mínima, complejidad si aplica).

---

## 7. Recomendaciones de uso

- Siempre usar el tipo de documento correcto y la cédula exacta.
- Leer bien el área del problema para que el sistema asigne correctamente.
- Para especialistas, mantener el navegador con permisos de notificación si desea avisos inmediatos.
- Usar los filtros de fecha y la búsqueda para localizar tickets con rapidez.

#### Buenas prácticas operativas
- Completar descripciones detalladas y adjuntar fotos si es posible.
- Añadir pasos realizados en el ticket cuando se acepta o cierra.
- Para reportes masivos, usar filtros por fecha y área antes de generar PDF.

---

## 8. Resumen rápido de rutas

- `login.php`: acceso de usuarios.
- `registro_usuario.php`: registro de nuevos solicitantes.
- `gestion_clave.php`: recuperación de contraseña.
- `index_solicitante.php`: panel de solicitante.
- `registro.php`: crear nuevo ticket.
- `ver_tickets.php`: lista de tickets.
- `views/home_especialista.php`: panel de especialista.
- `ajax/get_options.php`: carga de tipos y marcas por área.
- `procesar_registro.php`: procesa el registro de solicitudes.

Agrego rutas y archivos relevantes:
- `procesar_registro.php`: valida y persiste nuevas solicitudes.
- `asignar_ticket.php`: reasigna tickets a técnicos.
- `cerrar_ticket_detalle.php`: interfaz para cerrar tickets con comentarios.
- `gestionar_tickets.php`: vista administrativa para manejo masivo.
- `views/control_personal.php`: gestión de especialistas y técnicos.
- `views/generate_reporte_pdf.php`: genera reportes en PDF.
- `auditoria_sistema.php`: visor de registros de auditoría.
- `ajax/check_new_tickets.php`: endpoint de polling para notificaciones.
- `guardar_clave.php`, `nueva_clave.php`, `procesar_recuperacion.php`: flujos de clave.

---

## 9. Glosario de roles

- `Solicitante`: usuario que abre tickets.
- `Especialista`: usuario que revisa y acepta tickets, puede tener área específica.
- `Tecnico`: puede ser asignado a tickets y acepta solicitudes abiertas.
- `Jefe`: rol con acceso a reportes y auditoría desde el panel de especialista.

---

## 10. Documentación detallada por archivo y pantallas (guía paso a paso)

### `login.php` (Inicio de sesión)
- Campos: `tipo_documento`, `cedula`, `password`.
- Botones: `Ingresar`, `¿Olvidó su contraseña?`, `Registrarse`.
- Errores: credenciales inválidas -> muestra alerta en la misma página.

### `registro_usuario.php` (Registro de solicitante)
- Flujo: completar formulario -> `procesar_registro_usuario` (interno) -> mensaje.

### `registro.php` (Crear solicitud)
- Paso 1: seleccionar `Área del problema`.
- Paso 2: seleccionar `Tipo` y `Marca` (cargados por `ajax/get_options.php`).
- Paso 3: describir la falla y adjuntar archivos (si aplica).
- Resultado: número de ticket y pantalla de confirmación.

### `ver_tickets.php` (Listado y acciones)
- Acciones por fila:
  - `ACEPTAR`: toma el ticket para el usuario.
  - `ASIGNAR`: dialogo para elegir otro técnico.
  - `VER`: muestra historial y comentarios.
  - `CERRAR`: confirma cierre y solicita comentario de cierre.
- Filtros: estado, área, técnico, fecha, búsqueda por texto.

### `asignar_ticket.php` y `gestionar_tickets.php`
- Reasignación manual, edición masiva de estado, exportar listado a CSV/PDF.

### `views/generate_reporte_pdf.php` y `views/test_pdf.php`
- Parámetros: rango de fechas, área, estado.
- Salida: PDF descargable con estilo del sistema.

### `auditoria_sistema.php`
- Permite filtrar por usuario, fecha y tipo de evento.
- Muestra IP, timestamp, descripción y usuario responsable.

### `views/control_personal.php`
- Gestión de especialistas: activar/desactivar, asignar áreas, ver carga de trabajo.

### Endpoints AJAX
- `ajax/get_options.php`: recibe `area_id` y devuelve JSON con `tipos` y `marcas`.
- `ajax/check_new_tickets.php`: devuelve número de tickets nuevos y detalles mínimos.

## 11. Base de datos y tablas importantes

- `solicitante` (id, cedula, nombre, correo, ubicacion)
- `especialista` (id, nombre, area_id, activos, tickets_activos)
- `solicitud` (id, solicitante_id, tipo_id, marca_id, area_id, descripcion, estado, fecha_creacion, fecha_cierre, especialista_id)
- `tipo`, `marca`, `area_problema`
- `auditoria_general` (id, usuario_id, ip, tipo_movimiento, descripcion, fecha)

> Ver también: [tablas_auditoria.sql](tablas_auditoria.sql), [update_triggers.sql](update_triggers.sql)

## 12. Anexos y capturas (marcadores)
- `screenshots/login.png` — Login
- `screenshots/registro_usuario.png` — Registro
- `screenshots/registro_ticket.png` — Crear solicitud
- `screenshots/ver_tickets.png` — Listado de tickets
- `screenshots/aceptar_ticket.png` — Aceptar ticket
- `screenshots/cerrar_ticket.png` — Cerrar ticket
- `screenshots/control_personal.png` — Control de personal
- `screenshots/auditoria.png` — Auditoría y reportes

> Instrucción: coloque las capturas reales en la carpeta `screenshots/` con los nombres indicados para que el HTML muestre las imágenes.

---

Si quieres, puedo ahora:
- 1) Añadir las capturas de ejemplo (imágenes marcadas) si me proporcionas archivos.
- 2) Actualizar el HTML para incluir todas las secciones expandidas y las miniaturas.
- 3) Generar un checklist por rol para revisión.

Indica si quieres que ahora actualice también el archivo HTML (`manual_usuario_fondas.html`) con las secciones completas y marcadores de imagen.

---

## Checklist por Roles

Este checklist sirve para validar que el manual documenta todas las pantallas, botones, mensajes y flujos por rol. Marcar cada ítem cuando esté documentado y con captura asociada.

### Solicitante

- [ ] Pantalla de Login (`login.php`)
  - [ ] Campos: Tipo de documento, Cédula, Contraseña
  - [ ] Enlaces: `¿Olvidó su contraseña?`, `Registrarse`
  - [ ] Mensajes de error (credenciales inválidas)
  - [ ] Captura: `screenshots/login.png`

- [ ] Registro de usuario (`registro_usuario.php`)
  - [ ] Campos detallados (cédula, nombre, gerencia, contraseña, confirmar)
  - [ ] Validaciones por campo y mensajes de error
  - [ ] Mensaje de éxito y redirección
  - [ ] Captura: `screenshots/registro_usuario.png`

- [ ] Panel de solicitante (`index_solicitante.php`)
  - [ ] Botón `Nueva solicitud`
  - [ ] Enlace `Ver mis tickets`
  - [ ] Enlace `Cerrar sesión` (`logout.php`)
  - [ ] Captura: `screenshots/index_solicitante.png`

- [ ] Crear nueva solicitud (`registro.php`)
  - [ ] Campos: Área, Tipo, Marca, Descripción, adjuntos
  - [ ] Dependencia AJAX: `ajax/get_options.php` (documentar parámetros y ejemplo JSON)
  - [ ] Botones: `Enviar`, `Limpiar/Cancelar`
  - [ ] Mensajes de éxito (ID de ticket) y errores (DB/validación)
  - [ ] Captura: `screenshots/registro_ticket.png`

- [ ] Ver mis tickets (`ver_tickets.php` - vista solicitante)
  - [ ] Filtros disponibles y búsqueda
  - [ ] Columnas visibles para solicitante
  - [ ] Acciones permitidas (VER detalle, descargar PDF si aplica)
  - [ ] Captura: `screenshots/ver_tickets_solicitante.png`

### Especialista

- [ ] Panel de Especialista (`views/home_especialista.php`)
  - [ ] Widgets: resumen tickets, accesos rápidos
  - [ ] Permisos: ver estadísticas/ auditoría (según rol Jefe)
  - [ ] Captura: `screenshots/home_especialista.png`

- [ ] Listado de tickets (`ver_tickets.php` - vista especialista)
  - [ ] Columnas completas (ID, descripción, solicitante, técnico asignado, estado, fecha, prioridad)
  - [ ] Filtros por área, estado, técnico, rango de fechas
  - [ ] Polling de nuevos tickets (endpoint: `ajax/check_new_tickets.php`, frecuencia)
  - [ ] Captura: `screenshots/ver_tickets.png`

- [ ] Acciones en fila
  - [ ] `ACEPTAR`: asignación propia (efectos: `especialista_id`, `tickets_activos`)
  - [ ] `ASIGNAR`: diálogo de reasignación (documentar validaciones)
  - [ ] `VER DETALLE`: historial, comentarios y adjuntos (`cerrar_ticket_detalle.php`)
  - [ ] `CERRAR`: confirmación, comentario de cierre, actualización de estado y auditoría
  - [ ] Capturas: `screenshots/aceptar_ticket.png`, `screenshots/cerrar_ticket.png`

- [ ] Notificaciones y alertas
  - [ ] Sonora y visual al detectar nuevos tickets
  - [ ] Notificación de escritorio si el navegador lo permite
  - [ ] Documentar comportamiento cuando el usuario está fuera de línea

### Técnico

- [ ] Acceso y panel (si difiere del especialista)
  - [ ] Ver tickets asignados y abiertos
  - [ ] Acciones permitidas: ACEPTAR, VER, CERRAR
  - [ ] Visualizar carga de trabajo y tickets activos
  - [ ] Captura: `screenshots/panel_tecnico.png`

- [ ] Reglas de tiempo y prioridad
  - [ ] Umbral para URGENTE (documentar: 1 hora u otro valor)
  - [ ] Mostrar tiempo transcurrido en la tabla

### Jefe / Administrador

- [ ] Acceso a auditoría (`auditoria_sistema.php`)
  - [ ] Filtros: usuario, fecha, tipo de movimiento
  - [ ] Visualizar IP, timestamp, descripción
  - [ ] Captura: `screenshots/auditoria.png`

- [ ] Generación de reportes (`views/generate_reporte_pdf.php`)
  - [ ] Parámetros: rango de fechas, área, estado
  - [ ] Exportar a PDF (ejemplo de salida y restricciones)
  - [ ] Captura: `screenshots/generate_reporte.png`

- [ ] Gestión de personal (`views/control_personal.php`)
  - [ ] Activar/desactivar especialistas
  - [ ] Asignar áreas y revisar carga por especialista
  - [ ] Captura: `screenshots/control_personal.png`

### Operaciones Backend y Endpoints AJAX

- [ ] `ajax/get_options.php`
  - [ ] Parámetros de entrada (`area_id`) y ejemplo de respuesta JSON
  - [ ] Errores y formatos (códigos HTTP, mensajes)

- [ ] `ajax/check_new_tickets.php`
  - [ ] Formato de respuesta, campos clave y ejemplo
  - [ ] Frecuencia de polling y recomendaciones para producción

- [ ] `procesar_registro.php`, `guardar_clave.php`, `procesar_recuperacion.php`
  - [ ] Validaciones realizadas en servidor
  - [ ] Mensajes de error especíﬁcos y códigos de respuesta

### Base de datos y mantenimiento

- [ ] Documentar tablas críticas y columnas (ejemplos):
  - [ ] `solicitud`, `solicitante`, `especialista`, `tipo`, `marca`, `area_problema`, `auditoria_general`
- [ ] Incluir referencias a scripts: `tablas_auditoria.sql`, `update_triggers.sql`, `scripts/*` para inicialización

### Comprobaciones finales antes de publicar manual

- [ ] Todas las pantallas tienen captura y están referenciadas en el MD y HTML
- [ ] Todos los botones y acciones listados tienen su flujo y mensajes documentados
- [ ] Endpoints AJAX con ejemplos JSON incluidos
- [ ] Lista de errores comunes y sus soluciones rápidas
- [ ] Checklist aprobado por al menos un especialista funcional del sistema

---

> Nota: este checklist también existe en el archivo separado `manual_checklist_by_role.md`.
