<?php
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../validar.php';
exigir_login();

$mensaje = '';
$tipoMensaje = 'exito';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'registrar_venta') {
        $tipoVenta    = trim($_POST['tipo_venta'] ?? 'Normal');
        $ganadoId     = (int) ($_POST['ganado_id'] ?? 0);
        $comprador    = trim($_POST['comprador'] ?? '');
        $pesoVenta    = (float) ($_POST['peso_venta'] ?? 0);
        $montoTotal   = (float) ($_POST['monto_total'] ?? 0);
        $fechaVenta   = trim($_POST['fecha_venta'] ?? date('Y-m-d'));
        $observacion  = trim($_POST['observacion'] ?? '');

        if (!in_array($tipoVenta, ['Normal', 'Repasto'], true)) {
            $tipoVenta = 'Normal';
        }

        if ($montoTotal <= 0) {
            $mensaje = 'El monto total de la venta debe ser mayor que cero.';
            $tipoMensaje = 'error';
        } else {
            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare(
                    "INSERT INTO ventas
                        (tipo_venta, ganado_id, comprador, peso_venta, monto_total, fecha_venta, observacion)
                     VALUES
                        (:tipo_venta, :ganado_id, :comprador, :peso_venta, :monto_total, :fecha_venta, :observacion)"
                );
                $stmt->execute([
                    'tipo_venta'  => $tipoVenta,
                    'ganado_id'   => $ganadoId > 0 ? $ganadoId : null,
                    'comprador'   => $comprador !== '' ? $comprador : null,
                    'peso_venta'  => $pesoVenta,
                    'monto_total' => $montoTotal,
                    'fecha_venta' => $fechaVenta !== '' ? $fechaVenta : date('Y-m-d'),
                    'observacion' => $observacion !== '' ? $observacion : null,
                ]);

                if ($ganadoId > 0) {
                    $stmtUpd = $pdo->prepare(
                        "UPDATE ganado
                         SET estado = 'Vendido',
                             peso_actual = CASE WHEN :peso > 0 THEN :peso_val ELSE peso_actual END
                         WHERE id = :id"
                    );
                    $stmtUpd->execute([
                        'peso'     => $pesoVenta,
                        'peso_val' => $pesoVenta,
                        'id'       => $ganadoId,
                    ]);
                }

                $pdo->commit();
                $mensaje = 'Venta registrada correctamente.';
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $mensaje = 'No se pudo registrar la venta: ' . $e->getMessage();
                $tipoMensaje = 'error';
            }
        }
    } elseif ($accion === 'eliminar_venta') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare("DELETE FROM ventas WHERE id = :id");
            $stmt->execute(['id' => $id]);
            $mensaje = 'Registro de venta eliminado.';
        }
    }
}

$totalVentas   = (float) $pdo->query("SELECT COALESCE(SUM(monto_total), 0) FROM ventas")->fetchColumn();
$ventasNormal  = (float) $pdo->query("SELECT COALESCE(SUM(monto_total), 0) FROM ventas WHERE tipo_venta = 'Normal'")->fetchColumn();
$ventasRepasto = (float) $pdo->query("SELECT COALESCE(SUM(monto_total), 0) FROM ventas WHERE tipo_venta = 'Repasto'")->fetchColumn();

$animalesDisponibles = $pdo->query(
    "SELECT id, codigo, nombre, estado
     FROM ganado
     WHERE estado IN ('Activo', 'Repasto')
     ORDER BY codigo ASC"
)->fetchAll();

$ventas = $pdo->query(
    "SELECT v.*, g.codigo AS codigo_ganado
     FROM ventas v
     LEFT JOIN ganado g ON g.id = v.ganado_id
     ORDER BY v.fecha_venta DESC, v.id DESC"
)->fetchAll();

require_once __DIR__ . '/../header.php';
require_once __DIR__ . '/../menu.php';
?>

<style>
    .grid-ventas {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 12px;
        margin-bottom: 20px;
    }
    .card-venta {
        background: #ffffff;
        border: 1px solid #d5e0da;
        border-radius: 10px;
        padding: 15px 16px;
    }
    .card-venta span {
        display: block;
        font-size: 0.82rem;
        color: #526059;
    }
    .card-venta strong {
        display: block;
        font-size: 1.3rem;
        color: #1b4332;
        margin-top: 4px;
    }
    .caja-form {
        background: #ffffff;
        border: 1px solid #d5e0da;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 22px;
    }
    .caja-form h2 {
        color: #1b4332;
        font-size: 1.1rem;
        margin-bottom: 14px;
    }
    .fila-campos {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(185px, 1fr));
        gap: 12px;
        margin-bottom: 12px;
    }
    .campo label {
        display: block;
        font-size: 0.83rem;
        font-weight: bold;
        margin-bottom: 4px;
    }
    .campo input,
    .campo select {
        width: 100%;
        padding: 8px 10px;
        border: 1px solid #c7d6ce;
        border-radius: 7px;
        font-size: 0.9rem;
    }
    .btn-primario {
        background: #1b4332;
        color: #ffffff;
        border: none;
        padding: 9px 16px;
        border-radius: 7px;
        font-weight: bold;
        cursor: pointer;
    }
    .btn-primario:hover {
        background: #2d6a4f;
    }
    .alerta-ok {
        background: #e6f6ec;
        color: #14532d;
        border: 1px solid #9ad6b0;
        padding: 10px 14px;
        border-radius: 7px;
        margin-bottom: 16px;
    }
    .alerta-err {
        background: #ffe5e7;
        color: #9b1c24;
        border: 1px solid #f5b5ba;
        padding: 10px 14px;
        border-radius: 7px;
        margin-bottom: 16px;
    }
    .tabla-wrap {
        background: #ffffff;
        border: 1px solid #d5e0da;
        border-radius: 10px;
        padding: 16px;
        overflow-x: auto;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.88rem;
    }
    th, td {
        padding: 9px 10px;
        border-bottom: 1px solid #e2eae6;
        text-align: left;
    }
    th {
        background: #f1f6f3;
        color: #1b4332;
    }
    .btn-del {
        padding: 5px 9px;
        border: none;
        border-radius: 5px;
        font-size: 0.8rem;
        font-weight: bold;
        cursor: pointer;
        background: #c1121f;
        color: #ffffff;
    }
</style>

<div class="grid-ventas">
    <div class="card-venta">
        <span>Total ventas</span>
        <strong><?= number_format($totalVentas, 2) ?></strong>
    </div>
    <div class="card-venta">
        <span>Ventas normales</span>
        <strong><?= number_format($ventasNormal, 2) ?></strong>
    </div>
    <div class="card-venta">
        <span>Ventas de repasto</span>
        <strong><?= number_format($ventasRepasto, 2) ?></strong>
    </div>
</div>

<?php if ($mensaje !== ''): ?>
    <div class="<?= $tipoMensaje === 'error' ? 'alerta-err' : 'alerta-ok' ?>">
        <?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<section class="caja-form">
    <h2>Registrar venta</h2>
    <form method="POST" action="index.php">
        <input type="hidden" name="accion" value="registrar_venta">
        <div class="fila-campos">
            <div class="campo">
                <label for="tipo_venta">Tipo de venta</label>
                <select id="tipo_venta" name="tipo_venta">
                    <option value="Normal">Normal</option>
                    <option value="Repasto">Repasto</option>
                </select>
            </div>
            <div class="campo">
                <label for="ganado_id">Animal (opcional)</label>
                <select id="ganado_id" name="ganado_id">
                    <option value="0">-- Venta general / Sin animal --</option>
                    <?php foreach ($animalesDisponibles as $ad): ?>
                        <option value="<?= (int) $ad['id'] ?>">
                            <?= htmlspecialchars($ad['codigo'] . ($ad['nombre'] ? ' - ' . $ad['nombre'] : '') . ' (' . $ad['estado'] . ')', ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="campo">
                <label for="comprador">Comprador</label>
                <input type="text" id="comprador" name="comprador" placeholder="Nombre del comprador">
            </div>
            <div class="campo">
                <label for="fecha_venta">Fecha</label>
                <input type="date" id="fecha_venta" name="fecha_venta" value="<?= date('Y-m-d') ?>">
            </div>
        </div>

        <div class="fila-campos">
            <div class="campo">
                <label for="peso_venta">Peso de venta (kg)</label>
                <input type="number" step="0.01" min="0" id="peso_venta" name="peso_venta" value="0">
            </div>
            <div class="campo">
                <label for="monto_total">Monto total *</label>
                <input type="number" step="0.01" min="0.01" id="monto_total" name="monto_total" required placeholder="0.00">
            </div>
            <div class="campo" style="grid-column: span 2;">
                <label for="observacion">Observación</label>
                <input type="text" id="observacion" name="observacion" placeholder="Detalles de la venta...">
            </div>
        </div>

        <button type="submit" class="btn-primario">Guardar venta</button>
    </form>
</section>

<section class="tabla-wrap">
    <table>
        <thead>
        <tr>
            <th>Fecha</th>
            <th>Tipo</th>
            <th>Animal</th>
            <th>Comprador</th>
            <th>Peso (kg)</th>
            <th>Monto</th>
            <th>Acción</th>
        </tr>
        </thead>
        <tbody>
        <?php if (empty($ventas)): ?>
            <tr>
                <td colspan="7">No hay ventas registradas.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($ventas as $v): ?>
                <tr>
                    <td><?= htmlspecialchars($v['fecha_venta'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($v['tipo_venta'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($v['codigo_ganado'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($v['comprador'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= number_format((float) $v['peso_venta'], 2) ?></td>
                    <td><strong><?= number_format((float) $v['monto_total'], 2) ?></strong></td>
                    <td>
                        <form method="POST" action="index.php" style="display:inline;"
                              onsubmit="return confirm('¿Eliminar este registro de venta?');">
                            <input type="hidden" name="accion" value="eliminar_venta">
                            <input type="hidden" name="id" value="<?= (int) $v['id'] ?>">
                            <button type="submit" class="btn-del">Eliminar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</section>

<?php require_once __DIR__ . '/../footer.php'; ?>