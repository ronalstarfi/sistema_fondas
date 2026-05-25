Instrucciones para el sonido de notificación

Coloque aquí un archivo de audio corto en formato MP3 o OGG que se use como fallback cuando WebAudio no esté disponible o esté bloqueado por el navegador.

Nombre de archivo recomendado:
- `notification_fallback.mp3`

Ruta dentro del proyecto:
- `assets/notification_fallback.mp3`

Recomendaciones:
- Duración: 0.25s a 0.8s (sonido corto y reconocible).
- Volumen: no muy bajo; el código JS ajusta el volumen a 0.9 por defecto.
- Formatos: MP3 y OGG funcionan bien en la mayoría de navegadores; si usa ambos, puede modificar `ver_tickets.php` para añadir múltiples fuentes dentro de la etiqueta `<audio>`.

Fuentes libres de efectos de sonido:
- https://freesound.org/
- https://notificationsounds.com/

Si quieres, puedo descargar y añadir un archivo de ejemplo (libre de derechos) directamente en `assets/notification_fallback.mp3`.