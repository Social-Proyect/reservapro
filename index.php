<?php
require_once 'config/supabase.php';
$empresa_id = isset($_GET['empresa_id']) ? (int)$_GET['empresa_id'] : null;
if (!$empresa_id) {
    // Landing page con estadísticas globales usando Supabase
    $total_empresas = 0;
    $total_usuarios = 0;
    $total_citas = 0;
    $total_clientes = 0;

    $empresas = supabase_request('/rest/v1/empresas?select=id');
    if (isset($empresas['data'])) {
        $total_empresas = count($empresas['data']);
    }
    $usuarios = supabase_request('/rest/v1/usuarios?select=id');
    if (isset($usuarios['data'])) {
        $total_usuarios = count($usuarios['data']);
    }
    $citas = supabase_request('/rest/v1/citas?select=id');
    if (isset($citas['data'])) {
        $total_citas = count($citas['data']);
    }
    $clientes = supabase_request('/rest/v1/clientes?select=id');
    if (isset($clientes['data'])) {
        $total_clientes = count($clientes['data']);
    }
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>ReservaPro - Plataforma de Reservas</title>
        <link rel="stylesheet" href="assets/css/styles.css">
        <style>
            .landing-hero {text-align:center;padding:60px 0;background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;}
            .landing-hero h1 {font-size:2.5rem;margin-bottom:20px;}
            .landing-hero p {font-size:1.2rem;max-width:600px;margin:0 auto 30px;}
            .stats-grid {display:flex;justify-content:center;gap:40px;margin:40px 0;flex-wrap:wrap;}
            .stat-card {background:#fff;color:#1f2937;padding:30px 40px;border-radius:16px;box-shadow:0 4px 16px rgba(0,0,0,0.08);min-width:180px;}
            .stat-card h2 {font-size:2.2rem;margin-bottom:8px;}
            .stat-card span {font-size:1.1rem;color:#6366f1;font-weight:600;}
            .landing-footer {text-align:center;padding:30px 0;color:#6b7280;}
        </style>
    </head>
    <body>
        <section class="landing-hero" style="position:relative;overflow:hidden;min-height:100vh;display:flex;align-items:center;justify-content:center;">
            <img src="assets/img/fondo_inicio.jpeg" alt="Fondo" style="position:absolute;top:0;left:0;width:100%;height:100%;object-fit:cover;z-index:0;pointer-events:none;">
            <div style="position:relative;z-index:1;width:100%;">
            <h1>ReservaPro</h1>
            <p>La plataforma SaaS líder para la gestión de reservas y citas de negocios locales.<br>Automatiza tu agenda, mejora la experiencia de tus clientes y haz crecer tu empresa.</p>
            <div class="stats-grid">
                <div class="stat-card">
                    <h2><?= $total_empresas ?></h2>
                    <span>Empresas registradas</span>
                </div>
                <div class="stat-card">
                    <h2><?= $total_usuarios ?></h2>
                    <span>Usuarios del sistema</span>
                </div>
                <div class="stat-card">
                    <h2><?= $total_clientes ?></h2>
                    <span>Clientes atendidos</span>
                </div>
                <div class="stat-card">
                    <h2><?= $total_citas ?></h2>
                    <span>Citas agendadas</span>
                </div>
            </div>
            <p style="margin-top:40px;font-size:1.1rem;">¿Eres dueño de un negocio? <a href="admin/" style="color:#fff;text-decoration:underline;font-weight:bold;">Registrate Aqui</a></p>
            </div>
        </section>
        <footer class="landing-footer">
            <p>&copy; <?= date('Y') ?> ReservaPro. Todos los derechos reservados.</p>
        </footer>
    </body>
    </html>
    <?php
    exit;
}

// Si hay empresa_id, cargar la configuración y servicios de esa empresa desde Supabase
$config = null;
$empresa = supabase_request("/rest/v1/configuracion?empresa_id=eq.$empresa_id&select=*,empresas(nombre,logo,direccion,telefono,email,moneda,color_primario,color_secundario)");
if (isset($empresa['data'][0])) {
    $config = $empresa['data'][0];
}
if (!$config) {
    echo '<h2>Empresa no encontrada o sin configuración.</h2>';
    exit;
}
$servicios = [];
$servicios_resp = supabase_request("/rest/v1/servicios?activo=eq.1&empresa_id=eq.$empresa_id&order=orden,nombre");
if (isset($servicios_resp['data'])) {
    $servicios = $servicios_resp['data'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($config['nombre_negocio']) ?> - Reserva tu Cita</title>
    <link rel="icon" type="image/png" href="assets/img/favicon-reserva.png">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/reserva.css">
    <?php
    $colorPrimario = !empty($config['color_primario']) ? $config['color_primario'] : '#6366f1';
    $colorSecundario = !empty($config['color_secundario']) ? $config['color_secundario'] : '#8b5cf6';
    ?>
    <style>
        :root {
            --color-primario: <?= json_encode($colorPrimario) ?>;
            --color-secundario: <?= json_encode($colorSecundario) ?>;
        }
    </style>
</head>
<body style="position:relative;z-index:1;">
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="logo-section">
                    <?php if ($config['logo']): ?>
                        <img src="<?= htmlspecialchars($config['logo']) ?>" alt="Logo" class="logo">
                    <?php endif; ?>
                    <span class="logo-text" style="margin-left:12px;font-size:1.5rem;font-weight:700;color:#fff;">
                        <?= htmlspecialchars($config['nombre_negocio']) ?>
                    </span>
                </div>
                <nav class="nav">
                    <a href="index.php?empresa_id=<?= $empresa_id ?>" class="nav-link active">Reservar Cita</a>
                    <a href="mis-citas.php?empresa_id=<?= $empresa_id ?>" class="nav-link">Mis Citas</a>
                </nav>
            </div>
            <div class="header-info">
                <div class="info-item">
                    <span class="icon">📍</span>
                    <span><?= nl2br(htmlspecialchars($config['direccion'])) ?></span>
                </div>
                <div class="info-item">
                    <span class="icon">📞</span>
                    <span><?= htmlspecialchars($config['telefono']) ?></span>
                </div>
                <div class="info-item">
                    <span class="icon">🕐</span>
                    <span><?= nl2br(htmlspecialchars($config['horario_general'])) ?></span>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <!-- Progress Bar -->
            <div class="progress-bar">
                <div class="progress-step active" data-step="1">
                    <div class="step-circle">1</div>
                    <div class="step-label">Servicio</div>
                </div>
                <div class="progress-line"></div>
                <div class="progress-step" data-step="2">
                    <div class="step-circle">2</div>
                    <div class="step-label">Empleado</div>
                </div>
                <div class="progress-line"></div>
                <div class="progress-step" data-step="3">
                    <div class="step-circle">3</div>
                    <div class="step-label">Fecha y Hora</div>
                </div>
                <div class="progress-line"></div>
                <div class="progress-step" data-step="4">
                    <div class="step-circle">4</div>
                    <div class="step-label">Confirmar</div>
                </div>
                </div>
            </div>

            <!-- Welcome Message -->
            <?php if ($config['mensaje_bienvenida']): ?>
            <div class="welcome-message">
                <p style="color:#000;"><?= htmlspecialchars($config['mensaje_bienvenida']) ?></p>
            </div>
            <?php endif; ?>

            <!-- Step 1: Seleccionar Servicio -->
            <div id="step-1" class="step-content active">
                <h2 class="section-title">Selecciona tu Servicio</h2>
                <div class="services-grid">
                    <?php foreach ($servicios as $servicio): ?>
                    <div class="service-card" data-service-id="<?= $servicio['id'] ?>" 
                         data-duration="<?= $servicio['duracion_minutos'] ?>"
                         data-price="<?= $servicio['precio'] ?>">
                        <div class="service-icon">
                            <?php
                            if (preg_match('/\.(jpg|jpeg|png|gif|svg)$/i', $servicio['icono'])) {
                                echo '<img src="' . htmlspecialchars($servicio['icono']) . '" alt="icono" style="width:2.5em;height:2.5em;object-fit:contain;">';
                            } else {
                                echo $servicio['icono'] ?: '✨';
                            }
                            ?>
                        </div>
                        <h3 class="service-name"><?= htmlspecialchars($servicio['nombre']) ?></h3>
                        <p class="service-description"><?= htmlspecialchars($servicio['descripcion']) ?></p>
                        <div class="service-details">
                            <span class="service-duration">⏱️ <?= $servicio['duracion_minutos'] ?> min</span>
                            <span class="service-price"><?php
                                $simbolo = isset($config['moneda']) ? trim($config['moneda']) : '';
                                if ($simbolo !== '' && $simbolo !== '-' && strlen($simbolo) <= 5) {
                                    echo htmlspecialchars($simbolo) . ' ';
                                }
                            ?><?= number_format($servicio['precio'], 2) ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Step 2: Seleccionar Empleado -->
            <div id="step-2" class="step-content">
                <h2 class="section-title">Selecciona tu Especialista</h2>
                <div id="employees-container" class="employees-grid">
                    <!-- Se llenará dinámicamente con JavaScript -->
                </div>
            </div>

            <!-- Step 3: Seleccionar Fecha y Hora -->
            <div id="step-3" class="step-content">
                <h2 class="section-title">Selecciona Fecha y Hora</h2>
                <div class="datetime-container">
                    <div class="calendar-section">
                        <div id="calendar"></div>
                    </div>
                    <div class="time-section">
                        <h3>Horarios Disponibles</h3>
                        <div id="available-times" class="time-slots">
                            <p class="text-muted">Selecciona una fecha para ver horarios disponibles</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 4: Confirmar y Datos -->
            <div id="step-4" class="step-content">
                <h2 class="section-title">Confirma tu Reserva</h2>
                <div class="confirmation-container">
                    <div class="booking-summary">
                        <h3>Resumen de tu Cita</h3>
                        <div class="summary-item">
                            <span class="label">Servicio:</span>
                            <span id="summary-service" class="value"></span>
                        </div>
                        <div class="summary-item">
                            <span class="label">Empleado:</span>
                            <span id="summary-employee" class="value"></span>
                        </div>
                        <div class="summary-item">
                            <span class="label">Fecha y Hora:</span>
                            <span id="summary-datetime" class="value"></span>
                        </div>
                        <div class="summary-item">
                            <span class="label">Duración:</span>
                            <span id="summary-duration" class="value"></span>
                        </div>
                        <div class="summary-item total">
                            <span class="label">Total:</span>
                            <span id="summary-price" class="value"></span>
                        </div>
                    </div>

                    <div class="customer-form">
                        <h3>Tus Datos</h3>
                        <form id="booking-form">
                            <div class="form-group">
                                <label for="customer-name">Nombre Completo *</label>
                                <input type="text" id="customer-name" name="nombre" required 
                                       placeholder="Ej: Juan Pérez">
                            </div>
                            <div class="form-group">
                                <label for="customer-phone">Teléfono (WhatsApp) *</label>
                                <input type="tel" id="customer-phone" name="telefono" required 
                                       placeholder="Ej: 123-456-7890">
                            </div>
                            <div class="form-group">
                                <label for="customer-email">Correo Electrónico *</label>
                                <input type="email" id="customer-email" name="email" required 
                                       placeholder="Ej: correo@ejemplo.com">
                            </div>
                            <div class="form-group">
                                <label for="customer-password">Crear Contraseña *</label>
                                <input type="password" id="customer-password" name="password" required minlength="4" maxlength="32 placeholder="Crea una contraseña para tu cuenta">
                            </div>
                            <div class="form-group">
                                <label for="customer-notes">Notas adicionales (opcional)</label>
                                <textarea id="customer-notes" name="notas" rows="3" 
                                          placeholder="¿Algo que debamos saber?"></textarea>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Navigation Buttons -->
            <div class="navigation-buttons">
                <button id="btn-prev" class="btn btn-secondary" style="display: none;">
                    ← Anterior
                </button>
                <button id="btn-next" class="btn btn-primary">
                    Siguiente →
                </button>
                <button id="btn-confirm" class="btn btn-success" style="display: none;">
                    ✓ Confirmar Reserva
                </button>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($config['nombre_negocio']) ?>. Todos los derechos reservados.</p>
        </div>
    </footer>

    <!-- Modal de Confirmación -->
    <div id="success-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <span class="success-icon">✓</span>
                <h2>¡Reserva Confirmada!</h2>
            </div>
            <div class="modal-body">
                <p>Tu cita ha sido reservada exitosamente.</p>
                <div class="confirmation-code">
                    <span>Código de confirmación:</span>
                    <strong id="confirmation-code"></strong>
                </div>

            </div>
            <div class="modal-footer">
                <a href="mis-citas.php" class="btn btn-primary">Ver Mis Citas</a>
                <a href="index.php?empresa_id=<?= $empresa_id ?>" class="btn btn-secondary">Nueva Reserva</a>
            </div>
        </div>
    </div>

    <script src="assets/js/calendar.js"></script>
        <script>
            // Pasar el símbolo de moneda a JS
            window.empresaMoneda = "<?= isset($config['moneda']) ? addslashes(trim($config['moneda'])) : '$' ?>";
        </script>
        <script src="assets/js/reserva.js"></script>
</body>
</html>
