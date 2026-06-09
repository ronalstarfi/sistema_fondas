<?php
session_start();

// 1. SEGURIDAD: Control de acceso para especialistas
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'especialista') {
    header("Location: ../login.php"); 
    exit();
}

// 2. CONEXIÓN
require_once '../config/database.php';
$db = (new Database())->getConnection();

// 3. LÓGICA DINÁMICA PARA EL TÍTULO
$nombre_usuario = $_SESSION['nombre'];
$titulo_dinamico = "ESPECIALISTA"; // Valor por defecto

if ($db) {
    // Buscamos el área específica (Gerente, Coordinadora, Desarrollo) en la tabla especialista
    $query = "SELECT area_especifica FROM especialista WHERE especialista = :nombre LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':nombre', $nombre_usuario);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($row && !empty($row['area_especifica'])) {
        $titulo_dinamico = $row['area_especifica'];
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Especialista - FONDAS</title>
    <!-- FontAwesome para iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Estilos originales preservados */
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            overflow: hidden; 
        }

        body {
            background-color: #f4f4f4;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            flex-direction: column;
        }

        .wrapper {
            width: 100%;
            max-width: 100%;
            margin: 0;
            background: white;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            border-radius: 0;
            box-shadow: none;
        }

        .banner-container {
            width: 100%;
            max-width: 100%;
            margin: 0;
            background: white;
            border-radius: 0;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            /* Hacer que la caja blanca ocupe casi toda la altura de la ventana */
            min-height: calc(100vh - 120px);
        }

        .cintillo-container, .banner-container {
            width: 100%;
            margin: 0;
            background: white;
            overflow: hidden;
        }

        .cintillo-container img, .banner-container img {
            width: 100%;
            height: 95px;
            object-fit: fill;
            display: block;
            margin: 0;
        }

        .navbar {
            background-color: #2e7d32;
            color: white;
            padding: 12px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.95rem;
            max-height: 140px;
            object-fit: contain;
            display: block;
            margin: 0 auto;
        }

        .header-info {
            width: 100%;
            max-width: 100%;
            margin: 0;
            background-color: #2e7d32;
            color: white;
            padding: 10px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 0;
            box-sizing: border-box;
            font-size: 14px;
        }

        .navbar .brand { font-weight: 700; text-transform: uppercase; }
        .container {
            background: white;
            padding: 40px 30px;
            width: 100%;
            box-sizing: border-box;
            min-height: 420px;
            /* Permitir que el contenido central crezca y empuje el footer al final */
            flex: 1 1 auto;
            display: flex;
            flex-direction: column;
        }

        .user-dropdown { position: relative; display: inline-block; }
        .user-dropdown-btn { 
            background: transparent; color: white; border: none; cursor: pointer; 
            font-size: 14px; font-family: inherit; display: flex; align-items: center; gap: 8px;
        }
        .user-dropdown-btn:focus { outline: none; }
        .dropdown-arrow { font-size: 10px; }
        .user-dropdown-content {
            display: none; position: absolute; right: 0; top: 100%;
            background-color: #fff; min-width: 160px;
            box-shadow: 0px 8px 16px rgba(0,0,0,0.2);
            border-radius: 6px; overflow: hidden; z-index: 100;
        }
        .user-dropdown:hover .user-dropdown-content { display: block; }
        .user-dropdown-content a {
            color: #333 !important; padding: 12px 16px; text-decoration: none;
            display: block; background: transparent; font-weight: normal; border-radius: 0;
        }
        .user-dropdown-content a:hover { background-color: #f1f8e9; color: #2e7d32 !important; }
        .user-dropdown-content .logout-link { color: #d32f2f !important; font-weight: bold; border-top: 1px solid #eee; }
        .user-dropdown-content .logout-link:hover { background-color: #ffebee; color: #b71c1c !important; }

        .panel-title {
            text-align: center;
            margin: 30px 0;
            color: #1b5e20;
            font-size: 24px;
            text-transform: uppercase;
            font-weight: bold;
        }

        main {
            flex: 1; 
            display: flex;
            justify-content: center;
            /* Subir los módulos: alineamos al inicio en vertical */
            align-items: flex-start;
            padding-top: 40px; /* pequeño espacio superior para separar del título */
        }

        .icon-container {
            display: flex;
            gap: 40px;
            justify-content: center;
            align-items: flex-start;
            flex-wrap: wrap;
        }

        .opcion-modulo {
            text-align: center;
            width: 180px;
            background: transparent;
            border: none;
            transition: transform 0.3s ease;
        }

        .opcion-modulo:hover {
            transform: scale(1.1);
        }

        .opcion-modulo img {
            width: 160px;
            height: auto;
            display: block;
            margin: 0 auto 15px;
            mix-blend-mode: multiply; 
        }

        .opcion-modulo p {
            font-weight: bold;
            font-size: 13px;
            margin: 0;
            color: #1b5e20;
            text-shadow: 1px 1px 2px rgba(255,255,255,0.8);
        }

        .opcion-modulo a { text-decoration: none; }

        footer {
            text-align: center;
            padding: 15px;
            color: #666;
            font-size: 11px;
            /* Empuja el footer hacia el fondo de la caja blanca */
            margin-top: auto;
            background: transparent;
        }
    </style>
</head>
<body>

    <div class="wrapper">
        <div class="cintillo-container">
            <img src="../img/logo3.png" alt="Banner FONDAS">
        </div>

        <div class="header-info">
            <div><span>Sistema de Gestión de Incidencias</span></div>
            <div class="user-dropdown">
                <button class="user-dropdown-btn">
                    <span>Bienvenido(a): <strong><?php echo htmlspecialchars($_SESSION['nombre']); ?></strong></span>
                    <span style="font-size: 10px; margin-left:8px;">▼</span>
                </button>
                <div class="user-dropdown-content">
                    <a href="../logout.php" class="logout-link">Cerrar Sesión</a>
                </div>
            </div>
        </div>

        <div class="container">
            <h2 class="panel-title">PANEL DE CONTROL <?php echo htmlspecialchars(strtoupper($titulo_dinamico)); ?></h2>

            <main>
                <div class="icon-container">
            <!-- Módulo de Tickets: Visible para todos -->
            <div class="opcion-modulo">
                <a href="../ver_tickets.php">
                    <img src="../img/tickets1.png" alt="Tickets">
                    <p>GESTIÓN DE TICKETS</p>
                </a>
            </div>

            <!-- MEJORA: Control de Personal ahora disponible para todos los especialistas -->
            <div class="opcion-modulo">
                <a href="control_personal.php">
                    <img src="../img/personal1.png" alt="Personal">
                    <p>CONTROL DE PERSONAL</p>
                </a>
            </div>

            <!-- Bloque restringido: Solo para usuarios con rol 'Jefe' -->
            <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] == 'Jefe'): ?>
                <div class="opcion-modulo">
                    <a href="dashboard.php">
                        <img src="../img/estadisticas2.png" alt="Reportes">
                        <p>ESTADÍSTICAS</p>
                    </a>
                </div>

                <div class="opcion-modulo">
                    <a href="../auditoria_sistema.php">
                        <img src="../img/auditoria2.png" alt="Auditoría">
                        <p>AUDITORÍA FORENSE</p>
                    </a>
                </div>
            <?php endif; ?>

            <div class="opcion-modulo">
                <a href="../manual.php">
                    <img src="../img/manual_icon.png" alt="Manual de Usuario" style="width: 140px; height: auto; margin-bottom: 15px; display: block; mix-blend-mode: multiply;">
                    <p>MANUAL DE USUARIO</p>
                </a>
            </div>
                </div> <!-- .icon-container -->
            </main>
        </div> <!-- .container -->
    </div> <!-- .wrapper -->

    <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] !== 'Solicitante'): ?>
    <div id="specialistNotification" class="specialist-notification" role="status" aria-live="polite">
        <div class="specialist-notification-content">
            <div>
                <strong id="notifTitle">¡Nuevo ticket disponible!</strong>
                <div id="notifMessage">Hay nuevos tickets abiertos. Haz clic para revisar.</div>
            </div>
            <div class="specialist-notification-actions">
                <button id="btnOpenTickets" class="btn-notif">Ir a Tickets</button>
                <button id="btnEnableNotifications" class="btn-notif-secondary" style="display:none;">Activar notificaciones</button>
            </div>
        </div>
    </div>
    <audio id="audioNotify" preload="auto">
        <source src="../assets/tono1.mp3" type="audio/mpeg">
    </audio>
    <?php endif; ?>

    <footer style="background: transparent; padding: 18px 30px; text-align:center; font-size:11px; color:#666; margin-top:20px;">
        <p style="margin:0;">Fondo para el Desarrollo Agrario Socialista (FONDAS) - Venezuela</p>
        <p style="margin:0;">Desarrollado por el Departamento de Tecnología e Información © 2026</p>
    </footer>

    <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] !== 'Solicitante'): ?>
    <script>
        (function() {
            var notificationBar = document.getElementById('specialistNotification');
            var notifTitle = document.getElementById('notifTitle');
            var notifMessage = document.getElementById('notifMessage');
            var btnOpenTickets = document.getElementById('btnOpenTickets');
            var btnEnableNotifications = document.getElementById('btnEnableNotifications');
            var audioNotify = document.getElementById('audioNotify');
            var lastOpenCount = 0;

            function hasNotificationSupport() {
                return typeof Notification !== 'undefined' && 'Notification' in window;
            }

            function updateNotificationButton() {
                if (!btnEnableNotifications) return;
                if (!hasNotificationSupport()) {
                    btnEnableNotifications.style.display = 'none';
                    return;
                }
                if (Notification.permission === 'granted') {
                    btnEnableNotifications.style.display = 'none';
                } else {
                    btnEnableNotifications.style.display = 'inline-flex';
                }
            }

            function requestNotificationPermission() {
                if (!hasNotificationSupport()) return;
                if (Notification.permission === 'default') {
                    Notification.requestPermission().then(function(permission) {
                        updateNotificationButton();
                    });
                }
            }

            function playBeep() {
                try {
                    var AudioCtx = window.AudioContext || window.webkitAudioContext;
                    if (AudioCtx) {
                        var ctx = new AudioCtx();
                        var master = ctx.createGain();
                        master.gain.setValueAtTime(0.7, ctx.currentTime);
                        master.connect(ctx.destination);
                        [880, 660, 990].forEach(function(freq, i) {
                            var osc = ctx.createOscillator();
                            var gain = ctx.createGain();
                            osc.type = (i === 1) ? 'triangle' : 'sine';
                            osc.frequency.setValueAtTime(freq, ctx.currentTime);
                            gain.gain.setValueAtTime(0.001, ctx.currentTime);
                            gain.gain.linearRampToValueAtTime(0.9, ctx.currentTime + 0.008);
                            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.28 + i * 0.04);
                            osc.connect(gain);
                            gain.connect(master);
                            osc.start(ctx.currentTime + i * 0.03);
                            osc.stop(ctx.currentTime + 0.28 + i * 0.03);
                        });
                        setTimeout(function() {
                            if (ctx && ctx.close) ctx.close();
                        }, 1400);
                        return;
                    }
                } catch (e) {
                    console.warn('WebAudio fallback:', e);
                }
                if (audioNotify) {
                    try {
                        audioNotify.currentTime = 0;
                        audioNotify.volume = 0.9;
                        audioNotify.play().catch(function(err) {
                            console.warn('Audio fallback play blocked:', err);
                        });
                    } catch (e) {
                        console.error('Audio fallback error:', e);
                    }
                }
            }

            function showDesktopNotification(diff) {
                if (!hasNotificationSupport() || Notification.permission !== 'granted') return;
                var title = diff === 1 ? 'Nuevo ticket en FONDAS' : diff + ' nuevos tickets en FONDAS';
                var body = diff === 1 ? '1 ticket nuevo abierto. Haz clic para revisar.' : 'Hay ' + diff + ' tickets nuevos. Haz clic para revisar.';
                var options = {
                    body: body,
                    icon: '../img/logo3.png',
                    tag: 'fondas-new-ticket',
                    renotify: true,
                    requireInteraction: false
                };
                try {
                    var notification = new Notification(title, options);
                    notification.onclick = function() {
                        window.focus();
                        window.location.href = '../ver_tickets.php';
                        this.close();
                    };
                } catch (e) {
                    console.warn('Notification show failed', e);
                }
            }

            function showAlert(diff) {
                if (!notificationBar) return;
                var title = diff === 1 ? '¡Nuevo ticket disponible!' : '¡' + diff + ' nuevos tickets!';
                notifTitle.textContent = title;
                notifMessage.textContent = diff === 1 ? 'Hay 1 nuevo ticket abierto.' : 'Hay ' + diff + ' nuevos tickets abiertos.';
                playBeep();

                var canDesktop = hasNotificationSupport() && Notification.permission === 'granted';
                if (canDesktop) {
                    showDesktopNotification(diff);
                    notificationBar.classList.remove('show');
                } else {
                    notificationBar.classList.add('show');
                    setTimeout(function() {
                        notificationBar.classList.remove('show');
                    }, diff >= 5 ? 20000 : 12000);
                }
            }

            function fetchOpenCount() {
                fetch('../ajax/check_new_tickets.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
                })
                .then(function(resp) { return resp.json(); })
                .then(function(data) {
                    if (data && typeof data.open === 'number') {
                        var currentOpen = data.open;
                        if (lastOpenCount === 0) {
                            lastOpenCount = currentOpen;
                            return;
                        }
                        if (currentOpen > lastOpenCount) {
                            showAlert(currentOpen - lastOpenCount);
                        }
                        lastOpenCount = currentOpen;
                    }
                })
                .catch(function(err) {
                    console.warn('Error comprobando tickets:', err);
                });
            }

            btnOpenTickets.addEventListener('click', function() {
                window.location.href = '../ver_tickets.php';
            });

            btnEnableNotifications.addEventListener('click', function(event) {
                event.preventDefault();
                requestNotificationPermission();
            });

            if (hasNotificationSupport()) {
                requestNotificationPermission();
            }
            updateNotificationButton();
            fetchOpenCount();
            setInterval(fetchOpenCount, 3000);
        })();
    </script>
    <style>
        .specialist-notification {
            position: fixed;
            top: 16px;
            left: 16px;
            right: 16px;
            z-index: 1200;
            display: none;
            justify-content: center;
        }
        .specialist-notification.show {
            display: flex;
        }
        .specialist-notification-content {
            width: 100%;
            max-width: 1100px;
            background: rgba(255,255,255,0.97);
            border: 1px solid #ffb300;
            border-radius: 14px;
            padding: 16px 22px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            box-shadow: 0 16px 30px rgba(0,0,0,0.12);
            color: #333;
        }
        .specialist-notification-content strong {
            display: block;
            margin-bottom: 4px;
            font-size: 1.05rem;
        }
        .specialist-notification-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .btn-notif,
        .btn-notif-secondary {
            border: none;
            padding: 10px 18px;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.2s ease;
        }
        .btn-notif {
            background: #2e7d32;
            color: #fff;
        }
        .btn-notif:hover {
            background: #276b2b;
        }
        .btn-notif-secondary {
            background: #f9a825;
            color: #fff;
        }
        .btn-notif-secondary:hover {
            background: #c17900;
        }
    </style>
    <?php endif; ?>
</body>
</html>