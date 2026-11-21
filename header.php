<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$scriptFile = str_replace('\\', '/', realpath($_SERVER['SCRIPT_FILENAME'] ?? __FILE__) ?: ($_SERVER['SCRIPT_FILENAME'] ?? __FILE__));

$enSubcarpeta = (strpos($scriptFile, '/ganado/') !== false
    || strpos($scriptFile, '/presupuesto/') !== false
    || strpos($scriptFile, '/ventas/') !== false
    || strpos($scriptFile, '/medicacion_comidas/') !== false
    || strpos($scriptFile, '/informe/') !== false
    || strpos($scriptFile, '/respaldo/') !== false
    || strpos($scriptFile, '/usuarios/') !== false);

$prefijo = $enSubcarpeta ? '../' : '';
$nombreUsuario = $_SESSION['nombre'] ?? 'Administrador';
$usuarioLogin  = $_SESSION['usuario'] ?? 'admin';
$rolUsuario    = $_SESSION['rol'] ?? 'Administrador';
$inicial       = mb_strtoupper(mb_substr($nombreUsuario, 0, 1, 'UTF-8'), 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control de ganado LM</title>
    <link rel="stylesheet" href="<?= htmlspecialchars($prefijo, ENT_QUOTES, 'UTF-8') ?>estilos.css">
</head>
<body>
<div class="contenedor">
    <header class="topbar">
        <a href="<?= htmlspecialchars($prefijo, ENT_QUOTES, 'UTF-8') ?>index.php" class="marca-titulo">
            <span class="icono-marca">LM</span>
            <span>Control de ganado LM</span>
        </a>

        <?php if (!empty($_SESSION['usuario_id'])): ?>
            <div class="barra-sesion">
                <div class="pildora-usuario">
                    <span class="avatar-usuario"><?= htmlspecialchars($inicial, ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="info-usuario-texto">
                        <span class="nombre-usuario"><?= htmlspecialchars($nombreUsuario, ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="meta-usuario">
                            <span>@<?= htmlspecialchars($usuarioLogin, ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="rol-badge"><?= htmlspecialchars($rolUsuario, ENT_QUOTES, 'UTF-8') ?></span>
                        </span>
                    </span>
                </div>
                <a href="<?= htmlspecialchars($prefijo, ENT_QUOTES, 'UTF-8') ?>logout.php" class="btn-salir">Salir</a>
            </div>
        <?php endif; ?>
    </header>