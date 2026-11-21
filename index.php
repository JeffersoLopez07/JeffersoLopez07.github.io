<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/validar.php';

$errorLogin = '';
$avisoPermiso = isset($_GET['sin_permiso']) ? 'No tienes permiso para acceder a ese módulo con tu rol actual.' : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'login') {
    $usuario = trim($_POST['usuario'] ?? '');
    $clave   = $_POST['password'] ?? '';

    if ($usuario === '' || $clave === '') {
        $errorLogin = 'Ingresa tu usuario y contraseña.';
    } else {
        $stmt = $pdo->prepare(
            "SELECT id, nombre, usuario, clave, rol, estado
             FROM usuarios
             WHERE usuario = :usuario
             LIMIT 1"
        );
        $stmt->execute(['usuario' => $usuario]);
        $userRow = $stmt->fetch();

        if (!$userRow || $userRow['estado'] !== 'Activo') {
            $errorLogin = 'Usuario inactivo o no encontrado.';
        } elseif ($clave !== $userRow['clave']) {
            $errorLogin = 'Contraseña incorrecta.';
        } else {
            session_regenerate_id(true);
            $_SESSION['usuario_id'] = (int) $userRow['id'];
            $_SESSION['nombre']     = $userRow['nombre'];
            $_SESSION['usuario']    = $userRow['usuario'];
            $_SESSION['rol']        = $userRow['rol'];
            header('Location: index.php');
            exit;
        }
    }
}

$pendientesMedCom = 0;
if (!empty($_SESSION['usuario_id'])) {
    try {
        $stmtPend = $pdo->prepare(
            "SELECT COUNT(*)
             FROM medicacion_comidas
             WHERE proxima_fecha IS NOT NULL
               AND proxima_fecha <= :hoy"
        );
        $stmtPend->execute(['hoy' => date('Y-m-d')]);
        $pendientesMedCom = (int) $stmtPend->fetchColumn();
    } catch (PDOException $e) {
        $pendientesMedCom = 0;
    }
}

require_once __DIR__ . '/header.php';
$esAdmin = !empty($_SESSION['usuario_id']) && es_administrador();
?>

<?php if (empty($_SESSION['usuario_id'])): ?>
    <div class="caja-login">
        <h2>Iniciar sesión</h2>
        <p class="subtexto-login">Acceso al control de ganado LM (Administrador o Encargado).</p>

        <?php if ($errorLogin !== ''): ?>
            <div class="alerta-err">
                <?= htmlspecialchars($errorLogin, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="index.php">
            <input type="hidden" name="accion" value="login">
            <div class="grupo-input">
                <label for="usuario">Usuario</label>
                <input type="text" id="usuario" name="usuario" required autofocus
                       value="<?= htmlspecialchars($_POST['usuario'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="grupo-input">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn-login">Entrar</button>
        </form>
    </div>
<?php else: ?>
    <p class="subtitulo-acceso">
        <?php if ($esAdmin): ?>
            Acceso completo de Administrador.
        <?php else: ?>
            Acceso operativo de Encargado.
        <?php endif; ?>
    </p>

    <?php if ($avisoPermiso !== ''): ?>
        <div class="alerta-err">
            <?= htmlspecialchars($avisoPermiso, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <section class="panel-alerta">
        <div class="panel-alerta-titulo">Alertas de medicación y comidas</div>
        <?php if ($pendientesMedCom > 0): ?>
            <div class="franja-aviso">
                <span class="franja-aviso-texto">
                    ¡Advertencia! Hay <?= $pendientesMedCom ?> aplicación(es) de medicación o comida programada para hoy o vencida.
                </span>
                <a href="medicacion_comidas/index.php" class="btn-aviso">Ver aplicaciones</a>
            </div>
        <?php else: ?>
            <div class="franja-aviso" style="background: #f0fdf4; border-color: #bbf7d0;">
                <span class="franja-aviso-texto" style="color: #15803d;">
                    Todo al día: no hay medicaciones ni comidas vencidas o pendientes hoy.
                </span>
                <a href="medicacion_comidas/index.php" class="btn-aviso" style="background: #16a34a;">Ir al módulo</a>
            </div>
        <?php endif; ?>
    </section>

    <div class="grid-tarjetas-menu">
        <a href="ganado/index.php" class="tarjeta-accion">
            <div class="icono-tarjeta ic-verde">GN</div>
            <div class="texto-tarjeta">
                <strong>Ganado</strong>
                <span class="desc-tarjeta">Compra, nacimiento y repasto</span>
            </div>
        </a>

        <a href="ventas/index.php" class="tarjeta-accion">
            <div class="icono-tarjeta ic-ambar">VT</div>
            <div class="texto-tarjeta">
                <strong>Ventas</strong>
                <span class="desc-tarjeta">Ventas normales y de repasto</span>
            </div>
        </a>

        <a href="medicacion_comidas/index.php" class="tarjeta-accion">
            <div class="icono-tarjeta ic-morado">MC</div>
            <div class="texto-tarjeta">
                <strong>Medicación y comidas</strong>
                <span class="desc-tarjeta">Aplicaciones periódicas y raciones</span>
            </div>
        </a>

        <?php if ($esAdmin): ?>
            <a href="presupuesto/index.php" class="tarjeta-accion">
                <div class="icono-tarjeta ic-azul">PR</div>
                <div class="texto-tarjeta">
                    <strong>Presupuesto</strong>
                    <span class="desc-tarjeta">Fondo normal, préstamo y gastos</span>
                </div>
            </a>

            <a href="usuarios/index.php" class="tarjeta-accion">
                <div class="icono-tarjeta ic-indigo">US</div>
                <div class="texto-tarjeta">
                    <strong>Usuarios</strong>
                    <span class="desc-tarjeta">Registrar y gestionar usuarios</span>
                </div>
            </a>

            <a href="informe/index.php" class="tarjeta-accion">
                <div class="icono-tarjeta ic-cian">IN</div>
                <div class="texto-tarjeta">
                    <strong>Informe general</strong>
                    <span class="desc-tarjeta">Resumen de animales, ventas y saldos</span>
                </div>
            </a>

            <a href="respaldo/index.php" class="tarjeta-accion">
                <div class="icono-tarjeta ic-gris">BD</div>
                <div class="texto-tarjeta">
                    <strong>Copia de seguridad</strong>
                    <span class="desc-tarjeta">Respaldos y restauración SQL</span>
                </div>
            </a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/footer.php'; ?>