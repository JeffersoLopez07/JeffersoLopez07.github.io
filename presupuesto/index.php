<?php
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../validar.php';
exigir_admin();

$mensaje = '';
$tipoMensaje = 'exito';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'agregar_fondo') {
        $tipoFondo    = trim($_POST['fondo_destino'] ?? 'Normal');
        $montoFondo   = (float) ($_POST['monto_fondo'] ?? 0);
        $fechaFondo   = trim($_POST['fecha_fondo'] ?? date('Y-m-d'));
        $detalleFondo = trim($_POST['detalle_fondo'] ?? '');

        if (!in_array($tipoFondo, ['Normal', 'Prestamo'], true)) {
            $tipoFondo = 'Normal';
        }

        if ($montoFondo <= 0) {
            $mensaje = 'El monto del fondo a agregar debe ser mayor que cero.';
            $tipoMensaje = 'error';
        } else {
            $nombreFondoTexto = $tipoFondo === 'Prestamo' ? 'Fondo de préstamo' : 'Fondo normal';
            $descFinal = $detalleFondo !== '' ? $detalleFondo : ('Agregado a ' . $nombreFondoTexto);

            try {
                $stmt = $pdo->prepare(
                    "INSERT INTO presupuesto_movimientos
                        (tipo_fondo, tipo_movimiento, monto, fecha_movimiento, ganado_id, descripcion)
                     VALUES
                        (:tipo_fondo, 'Ingreso', :monto, :fecha_movimiento, NULL, :descripcion)"
                );
                $stmt->execute([
                    'tipo_fondo'       => $tipoFondo,
                    'monto'            => $montoFondo,
                    'fecha_movimiento' => $fechaFondo !== '' ? $fechaFondo : date('Y-m-d'),
                    'descripcion'      => $descFinal,
                ]);
                $mensaje = 'Fondo agregado correctamente a ' . $nombreFondoTexto . '.';
            } catch (PDOException $e) {
                $mensaje = 'Error al agregar el fondo: ' . $e->getMessage();
                $tipoMensaje = 'error';
            }
        }
    } elseif ($accion === 'registrar_movimiento') {
        $tipoFondo   = trim($_POST['tipo_fondo'] ?? 'Normal');
        $tipoMov     = trim($_POST['tipo_movimiento'] ?? 'Gasto');
        $monto       = (float) ($_POST['monto'] ?? 0);
        $fechaMov    = trim($_POST['fecha_movimiento'] ?? date('Y-m-d'));
        $ganadoId    = (int) ($_POST['ganado_id'] ?? 0);
        $descripcion = trim($_POST['descripcion'] ?? '');

        if (!in_array($tipoFondo, ['Normal', 'Prestamo'], true)) {
            $tipoFondo = 'Normal';
        }
        if (!in_array($tipoMov, ['Ingreso', 'Gasto'], true)) {
            $tipoMov = 'Gasto';
        }

        if ($monto <= 0) {
            $mensaje = 'El monto debe ser mayor que cero.';
            $tipoMensaje = 'error';
        } elseif ($descripcion === '') {
            $mensaje = 'Escribe una descripción del movimiento.';
            $tipoMensaje = 'error';
        } else {
            try {
                $stmt = $pdo->prepare(
                    "INSERT INTO presupuesto_movimientos
                        (tipo_fondo, tipo_movimiento, monto, fecha_movimiento, ganado_id, descripcion)
                     VALUES
                        (:tipo_fondo, :tipo_movimiento, :monto, :fecha_movimiento, :ganado_id, :descripcion)"
                );
                $stmt->execute([
                    'tipo_fondo'       => $tipoFondo,
                    'tipo_movimiento'  => $tipoMov,
                    'monto'            => $monto,
                    'fecha_movimiento' => $fechaMov !== '' ? $fechaMov : date('Y-m-d'),
                    'ganado_id'        => $ganadoId > 0 ? $ganadoId : null,
                    'descripcion'      => $descripcion,
                ]);
                $mensaje = 'Movimiento de presupuesto guardado.';
            } catch (PDOException $e) {
                $mensaje = 'Error al guardar el movimiento: ' . $e->getMessage();
                $tipoMensaje = 'error';
            }
        }
    } elseif ($accion === 'eliminar_movimiento') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare("DELETE FROM presupuesto_movimientos WHERE id = :id");
            $stmt->execute(['id' => $id]);
            $mensaje = 'Movimiento eliminado.';
        }
    }
}

$ingNormal   = (float) $pdo->query("SELECT COALESCE(SUM(monto), 0) FROM presupuesto_movimientos WHERE tipo_fondo = 'Normal' AND tipo_movimiento = 'Ingreso'")->fetchColumn();
$gasNormal   = (float) $pdo->query("SELECT COALESCE(SUM(monto), 0) FROM presupuesto_movimientos WHERE tipo_fondo = 'Normal' AND tipo_movimiento = 'Gasto'")->fetchColumn();
$saldoNormal = $ingNormal - $gasNormal;

$ingPrestamo   = (float) $pdo->query("SELECT COALESCE(SUM(monto), 0) FROM presupuesto_movimientos WHERE tipo_fondo = 'Prestamo' AND tipo_movimiento = 'Ingreso'")->fetchColumn();
$gasPrestamo   = (float) $pdo->query("SELECT COALESCE(SUM(monto), 0) FROM presupuesto_movimientos WHERE tipo_fondo = 'Prestamo' AND tipo_movimiento = 'Gasto'")->fetchColumn();
$saldoPrestamo = $ingPrestamo - $gasPrestamo;

$saldoTotal = $saldoNormal + $saldoPrestamo;

$animales = $pdo->query("SELECT id, codigo, nombre FROM ganado ORDER BY codigo ASC")->fetchAll();

$filtroFondo = $_GET['fondo'] ?? '';
$sqlMov = "SELECT pm.*, g.codigo AS codigo_ganado
           FROM presupuesto_movimientos pm
           LEFT JOIN ganado g ON g.id = pm.ganado_id";
$paramsMov = [];

if (in_array($filtroFondo, ['Normal', 'Prestamo'], true)) {
    $sqlMov .= " WHERE pm.tipo_fondo = :fondo";
    $paramsMov['fondo'] = $filtroFondo;
}
$sqlMov .= " ORDER BY pm.fecha_movimiento DESC, pm.id DESC";

$stmtMov = $pdo->prepare($sqlMov);
$stmtMov->execute($paramsMov);
$movimientos = $stmtMov->fetchAll();

require_once __DIR__ . '/../header.php';
require_once __DIR__ . '/../menu.php';
?>

<div class="grid-fondos">
    <div class="card-fondo">
        <span>Saldo fondo normal</span>
        <strong><?= number_format($saldoNormal, 2) ?></strong>
        <small>Ingresos: <?= number_format($ingNormal, 2) ?> · Gastos: <?= number_format($gasNormal, 2) ?></small>
    </div>
    <div class="card-fondo">
        <span>Saldo fondo préstamo</span>
        <strong><?= number_format($saldoPrestamo, 2) ?></strong>
        <small>Ingresos: <?= number_format($ingPrestamo, 2) ?> · Gastos: <?= number_format($gasPrestamo, 2) ?></small>
    </div>
    <div class="card-fondo">
        <span>Saldo presupuesto total</span>
        <strong><?= number_format($saldoTotal, 2) ?></strong>
        <small>Normal + Préstamo disponible</small>
    </div>
</div>

<?php if ($mensaje !== ''): ?>
    <div class="<?= $tipoMensaje === 'error' ? 'alerta-err' : 'alerta-ok' ?>">
        <?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<section class="caja-form">
    <h2>Agregar fondo</h2>
    <form method="POST" action="index.php">
        <input type="hidden" name="accion" value="agregar_fondo">
        <div class="fila-campos">
            <div class="campo">
                <label for="fondo_destino">Seleccionar fondo</label>
                <select id="fondo_destino" name="fondo_destino">
                    <option value="Normal">Fondo normal</option>
                    <option value="Prestamo">Fondo de préstamo</option>
                </select>
            </div>
            <div class="campo">
                <label for="monto_fondo">Monto a agregar *</label>
                <input type="number" step="0.01" min="0.01" id="monto_fondo" name="monto_fondo" required placeholder="0.00">
            </div>
            <div class="campo">
                <label for="fecha_fondo">Fecha</label>
                <input type="date" id="fecha_fondo" name="fecha_fondo" value="<?= date('Y-m-d') ?>">
            </div>
            <div class="campo">
                <label for="detalle_fondo">Nota / Concepto (opcional)</label>
                <input type="text" id="detalle_fondo" name="detalle_fondo" placeholder="Ej. Capital inicial, préstamo...">
            </div>
        </div>
        <button type="submit" class="btn-primario">Agregar fondo</button>
    </form>
</section>

<section class="caja-form">
    <h2>Registrar movimiento de presupuesto</h2>
    <form method="POST" action="index.php">
        <input type="hidden" name="accion" value="registrar_movimiento">
        <div class="fila-campos">
            <div class="campo">
                <label for="tipo_fondo">Fondo</label>
                <select id="tipo_fondo" name="tipo_fondo">
                    <option value="Normal">Fondo normal</option>
                    <option value="Prestamo">Fondo de préstamo</option>
                </select>
            </div>
            <div class="campo">
                <label for="tipo_movimiento">Tipo de movimiento</label>
                <select id="tipo_movimiento" name="tipo_movimiento">
                    <option value="Ingreso">Ingreso / Aporte</option>
                    <option value="Gasto">Gasto</option>
                </select>
            </div>
            <div class="campo">
                <label for="monto">Monto *</label>
                <input type="number" step="0.01" min="0.01" id="monto" name="monto" required placeholder="0.00">
            </div>
            <div class="campo">
                <label for="fecha_movimiento">Fecha</label>
                <input type="date" id="fecha_movimiento" name="fecha_movimiento" value="<?= date('Y-m-d') ?>">
            </div>
        </div>

        <div class="fila-campos">
            <div class="campo">
                <label for="ganado_id">Animal relacionado (opcional)</label>
                <select id="ganado_id" name="ganado_id">
                    <option value="0">-- General / Sin animal --</option>
                    <?php foreach ($animales as $an): ?>
                        <option value="<?= (int) $an['id'] ?>">
                            <?= htmlspecialchars($an['codigo'] . ($an['nombre'] ? ' - ' . $an['nombre'] : ''), ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="campo" style="grid-column: span 2;">
                <label for="descripcion">Descripción *</label>
                <input type="text" id="descripcion" name="descripcion" required placeholder="Ej. Compra de sal mineral, abono a préstamo...">
            </div>
        </div>

        <button type="submit" class="btn-primario">Guardar movimiento</button>
    </form>
</section>

<div class="barra-filtros">
    <a href="index.php" class="<?= $filtroFondo === '' ? 'activo' : '' ?>">Todos los fondos</a>
    <a href="index.php?fondo=Normal" class="<?= $filtroFondo === 'Normal' ? 'activo' : '' ?>">Fondo normal</a>
    <a href="index.php?fondo=Prestamo" class="<?= $filtroFondo === 'Prestamo' ? 'activo' : '' ?>">Fondo de préstamo</a>
</div>

<section class="tabla-wrap">
    <table>
        <thead>
        <tr>
            <th>Fecha</th>
            <th>Fondo</th>
            <th>Tipo</th>
            <th>Animal</th>
            <th>Descripción</th>
            <th>Monto</th>
            <th>Acción</th>
        </tr>
        </thead>
        <tbody>
        <?php if (empty($movimientos)): ?>
            <tr>
                <td colspan="7">No hay movimientos registrados.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($movimientos as $m): ?>
                <tr>
                    <td><?= htmlspecialchars($m['fecha_movimiento'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($m['tipo_fondo'] === 'Prestamo' ? 'Préstamo' : 'Normal', ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <?php if ($m['tipo_movimiento'] === 'Ingreso'): ?>
                            <span class="badge-ing">Ingreso</span>
                        <?php else: ?>
                            <span class="badge-gas">Gasto</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($m['codigo_ganado'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($m['descripcion'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><strong><?= number_format((float) $m['monto'], 2) ?></strong></td>
                    <td>
                        <form method="POST" action="index.php" style="display:inline;"
                              onsubmit="return confirm('¿Eliminar este movimiento?');">
                            <input type="hidden" name="accion" value="eliminar_movimiento">
                            <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
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