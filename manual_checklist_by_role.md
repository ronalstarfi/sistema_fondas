# Checklist por Roles — Manual de Usuario FONDAS

Este checklist sirve para validar que el manual documenta todas las pantallas, botones, mensajes y flujos por rol. Marcar cada ítem cuando esté documentado y con captura asociada.

---

## Solicitante

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

---

## Especialista

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

---

## Técnico

- [ ] Acceso y panel (si difiere del especialista)
  - [ ] Ver tickets asignados y abiertos
  - [ ] Acciones permitidas: ACEPTAR, VER, CERRAR
  - [ ] Visualizar carga de trabajo y tickets activos
  - [ ] Captura: `screenshots/panel_tecnico.png`

- [ ] Reglas de tiempo y prioridad
  - [ ] Umbral para URGENTE (documentar: 1 hora u otro valor)
  - [ ] Mostrar tiempo transcurrido en la tabla

---

## Jefe / Administrador

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

---

## Operaciones Backend y Endpoints AJAX

- [ ] `ajax/get_options.php`
  - [ ] Parámetros de entrada (`area_id`) y ejemplo de respuesta JSON
  - [ ] Errores y formatos (códigos HTTP, mensajes)

- [ ] `ajax/check_new_tickets.php`
  - [ ] Formato de respuesta, campos clave y ejemplo
  - [ ] Frecuencia de polling y recomendaciones para producción

- [ ] `procesar_registro.php`, `guardar_clave.php`, `procesar_recuperacion.php`
  - [ ] Validaciones realizadas en servidor
  - [ ] Mensajes de error especíﬁcos y códigos de respuesta

---

## Base de datos y mantenimiento

- [ ] Documentar tablas críticas y columnas (ejemplos):
  - [ ] `solicitud`, `solicitante`, `especialista`, `tipo`, `marca`, `area_problema`, `auditoria_general`
- [ ] Incluir referencias a scripts: `tablas_auditoria.sql`, `update_triggers.sql`, `scripts/*` para inicialización

---

## Comprobaciones finales antes de publicar manual

- [ ] Todas las pantallas tienen captura y están referenciadas en el MD y HTML
- [ ] Todos los botones y acciones listados tienen su flujo y mensajes documentados
- [ ] Endpoints AJAX con ejemplos JSON incluidos
- [ ] Lista de errores comunes y sus soluciones rápidas
- [ ] Checklist aprobado por al menos un especialista funcional del sistema

---

Si quieres, incorporo esta checklist en la parte final de `manual_usuario_fondas.md` y actualizo el HTML para imprimirla. ¿Deseas que lo haga ahora?
