<?php
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../validar.php';
exigir_admin();

$ganadoActivos   = (int) $pdo->query("SELECT COUNT(*) FROM ganado WHERE estado = 'Activo'")->fetchColumn();
$ganadoRepasto  = (int) $pdo->query("SELECT COUNT(*) FROM ganado WHERE estado = 'Repasto'")->fetchColumn();
$ganadoVendidos = (int) $pdo->query("SELECT COUNT(*) FROM ganado WHERE estado = 'Vendido'")->fetchColumn();
$ganadoBajas    = (int) $pdo->query("SELECT COUNT(*) FROM ganado WHERE estado = 'Baja'")->fetchColumn();
$ganadoTotal    = $ganadoActivos + $ganadoRepasto + $ganadoVendidos + $ganadoBajas;

$ingNormal   = (float) $pdo->query("SELECT COALESCE(SUM(monto), 0) FROM presupuesto_movimientos WHERE tipo_fondo = 'Normal' AND tipo_movimiento = 'Ingreso'")->fetchColumn();
$gasNormal   = (float) $pdo->query("SELECT COALESCE(SUM(monto), 0) FROM presupuesto_movimientos WHERE tipo_fondo = 'Normal' AND tipo_movimiento = 'Gasto'")->fetchColumn();
$saldoNormal = $ingNormal - $gasNormal;

$ingPrestamo   = (float) $pdo->query("SELECT COALESCE(SUM(monto), 0) FROM presupuesto_movimientos WHERE tipo_fondo = 'Prestamo' AND tipo_movimiento = 'Ingreso'")->fetchColumn();
$gasPrestamo   = (float) $pdo->query("SELECT COALESCE(SUM(monto), 0) FROM presupuesto_movimientos WHERE tipo_fondo = 'Prestamo' AND tipo_movimiento = 'Gasto'")->fetchColumn();
$saldoPrestamo = $ingPrestamo - $gasPrestamo;

$saldoPresupuesto = $saldoNormal + $saldoPrestamo;

$ventasNormal  = (float) $pdo->query("SELECT COALESCE(SUM(monto_total), 0) FROM ventas WHERE tipo_venta = 'Normal'")->fetchColumn();
$ventasRepasto = (float) $pdo->query("SELECT COALESCE(SUM(monto_total), 0) FROM ventas WHERE tipo_venta = 'Repasto'")->fetchColumn();
$ventasTotal   = $ventasNormal + $ventasRepasto;

$costoMedCom = (float) $pdo->query("SELECT COALESCE(SUM(costo), 0) FROM medicacion_comidas")->fetchColumn();

$ultimasVentas = $pdo->query(
    "SELECT v.fecha_venta, v.tipo_venta, v.comprador, v.monto_total, g.codigo AS codigo_ganado
     FROM ventas v
     LEFT JOIN ganado g ON g.id = v.ganado_id
     ORDER BY v.fecha_venta DESC, v.id DESC
     LIMIT 6"
)->fetchAll();

$ultimosMovimientos = $pdo->query(
    "SELECT fecha_movimiento, tipo_fondo, tipo_movimiento, descripcion, monto
     FROM presupuesto_movimientos
     ORDER BY fecha_movimiento DESC, id DESC
     LIMIT 6"
)->fetchAll();

require_once __DIR__ . '/../header.php';
require_once __DIR__ . '/../menu.php';
?>

<section class="seccion-informe">
    <h2>Resumen de ganado</h2>
    <div class="grid-resumen">
        <div class="dato-box">
            <span>Total registrados</span>
            <strong><?= $ganadoTotal ?></strong>
        </div>
        <div class="dato-box">
            <span>Activos</span>
            <strong><?= $ganadoActivos ?></strong>
        </div>
        <div class="dato-box">
            <span>En repasto</span>
            <strong><?= $ganadoRepasto ?></strong>
        </div>
        <div class="dato-box">
            <span>Vendidos</span>
            <strong><?= $ganadoVendidos ?></strong>
        </div>
        <div class="dato-box">
            <span>Bajas</span>
            <strong><?= $ganadoBajas ?></strong>
        </div>
    </div>
</section>

<section class="seccion-informe">
    <h2>Resumen financiero</h2>
    <div class="grid-resumen">
        <div class="dato-box">
            <span>Saldo fondo normal</span>
            <strong><?= number_format($saldoNormal, 2) ?></strong>
        </div>
        <div class="dato-box">
            <span>Saldo fondo préstamo</span>
            <strong><?= number_format($saldoPrestamo, 2) ?></strong>
        </div>
        <div class="dato-box">
            <span>Saldo presupuesto total</span>
            <strong><?= number_format($saldoPresupuesto, 2) ?></strong>
        </div>
        <div class="dato-box">
            <span>Ventas normales</span>
            <strong><?= number_format($ventasNormal, 2) ?></strong>
        </div>
        <div class="dato-box">
            <span>Ventas repasto</span>
            <strong><?= number_format($ventasRepasto, 2) ?></strong>
        </div>
        <div class="dato-box">
            <span>Total ventas</span>
            <strong><?= number_format($ventasTotal, 2) ?></strong>
        </div>
        <div class="dato-box">
            <span>Costo medicación y comidas</span>
            <strong><?= number_format($costoMedCom, 2) ?></strong>
        </div>
    </div>
</section>

<section class="seccion-informe">
    <h2>Últimas ventas</h2>
    <div class="tabla-wrap">
        <table>
            <thead>
            <tr>
                <th>Fecha</th>
                <th>Tipo</th>
                <th>Animal</th>
                <th>Monto</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($ultimasVentas)): ?>
                <tr>
                    <td colspan="4">Sin ventas recientes.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($ultimasVentas as $uv): ?>
                    <tr>
                        <td><?= htmlspecialchars($uv['fecha_venta'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($uv['tipo_venta'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($uv['codigo_ganado'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><strong><?= number_format((float) $uv['monto_total'], 2) ?></strong></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="seccion-informe">
    <h2>Últimos movimientos de presupuesto</h2>
    <div class="tabla-wrap">
        <table>
            <thead>
            <tr>
                <th>Fecha</th>
                <th>Fondo</th>
                <th>Descripción</th>
                <th>Monto</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($ultimosMovimientos)): ?>
                <tr>
                    <td colspan="4">Sin movimientos recientes.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($ultimosMovimientos as $um): ?>
                    <tr>
                        <td><?= htmlspecialchars($um['fecha_movimiento'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($um['tipo_fondo'] === 'Prestamo' ? 'Préstamo' : 'Normal', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($um['descripcion'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><strong><?= number_format((float) $um['monto'], 2) ?></strong></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require_once __DIR__ . '/../footer.php'; ?>