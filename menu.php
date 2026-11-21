<?php
require_once __DIR__ . '/validar.php';

$rutaActual = str_replace('\\', '/', $_SERVER['PHP_SELF'] ?? '');
$esInicio      = (substr($rutaActual, -9) === '/index.php' && substr_count($rutaActual, '/') <= 2);
$esGanado      = (strpos($rutaActual, '/ganado/') !== false);
$esPresupuesto = (strpos($rutaActual, '/presupuesto/') !== false);
$esVentas      = (strpos($rutaActual, '/ventas/') !== false);
$esMedCom      = (strpos($rutaActual, '/medicacion_comidas/') !== false);
$esUsuarios    = (strpos($rutaActual, '/usuarios/') !== false);
$esInforme     = (strpos($rutaActual, '/informe/') !== false);
$esRespaldo    = (strpos($rutaActual, '/respaldo/') !== false);

$base = ($esGanado || $esPresupuesto || $esVentas || $esMedCom || $esUsuarios || $esInforme || $esRespaldo) ? '../' : '';
$esAdmin = es_administrador();
?>
<nav class="menu-nav">
    <a href="<?= $base ?>index.php" class="<?= $esInicio ? 'activo' : '' ?>">Inicio</a>
    <a href="<?= $base ?>ganado/index.php" class="<?= $esGanado ? 'activo' : '' ?>">Ganado</a>
    <a href="<?= $base ?>ventas/index.php" class="<?= $esVentas ? 'activo' : '' ?>">Ventas</a>
    <a href="<?= $base ?>medicacion_comidas/index.php" class="<?= $esMedCom ? 'activo' : '' ?>">Medicación y comidas</a>
    <?php if ($esAdmin): ?>
        <a href="<?= $base ?>presupuesto/index.php" class="<?= $esPresupuesto ? 'activo' : '' ?>">Presupuesto</a>
        <a href="<?= $base ?>usuarios/index.php" class="<?= $esUsuarios ? 'activo' : '' ?>">Usuarios</a>
        <a href="<?= $base ?>informe/index.php" class="<?= $esInforme ? 'activo' : '' ?>">Informe general</a>
        <a href="<?= $base ?>respaldo/index.php" class="<?= $esRespaldo ? 'activo' : '' ?>">Copia de seguridad</a>
    <?php endif; ?>
</nav>