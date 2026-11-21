<?php
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../validar.php';
exigir_login();

$mensaje = '';
$tipoMensaje = 'exito';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'registrar_aplicacion') {
        $tipoReg        = trim($_POST['tipo_registro'] ?? 'Medicacion');
        $ganadoId       = (int) ($_POST['ganado_id'] ?? 0);
        $producto       = trim($_POST['producto'] ?? '');
        $frecuencia     = trim($_POST['frecuencia'] ?? 'Unica');
        $fechaAplic     = trim($_POST['fecha_aplicacion'] ?? date('Y-m-d'));
        $proximaFecha   = trim($_POST['proxima_fecha'] ?? '');
        $costo          = (float) ($_POST['costo'] ?? 0);
        $observacion    = trim($_POST['observacion'] ?? '');

        if (!in_array($tipoReg, ['Medicacion', 'Comida'], true)) {
            $tipoReg = 'Medicacion';
        }
        if (!in_array($frecuencia, ['Unica', 'Semanal', 'Mensual', 'Trimestral', 'Anual'], true)) {
            $frecuencia = 'Unica';
        }

        if ($producto === '') {
            $mensaje = 'Escribe el nombre del medicamento o alimento.';
            $tipoMensaje = 'error';
        } else {
            try {
                $stmt = $pdo->prepare(
                    "INSERT INTO medicacion_comidas
                        (tipo_registro, ganado_id, producto, frecuencia, fecha_aplicacion, proxima_fecha, costo, observacion)
                     VALUES
                        (:tipo_registro, :ganado_id, :producto, :frecuencia, :fecha_aplicacion, :proxima_fecha, :costo, :observacion)"
                );
                $stmt->execute([
                    'tipo_registro'    => $tipoReg,
                    'ganado_id'        => $ganadoId > 0 ? $ganadoId : null,
                    'producto'         => $producto,
                    'frecuencia'       => $frecuencia,
                    'fecha_aplicacion' => $fechaAplic !== '' ? $fechaAplic : date('Y-m-d'),
                    'proxima_fecha'    => $proximaFecha !== '' ? $proximaFecha : null,
                    'costo'            => $costo,
                    'observacion'      => $observacion !== '' ? $observacion : null,
                ]);
                $mensaje = 'Registro guardado correctamente.';
            } catch (PDOException $e) {
                $mensaje = 'No se pudo guardar el registro: ' . $e->getMessage();
                $tipoMensaje = 'error';
            }
        }
    } elseif ($accion === 'eliminar_aplicacion') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare("DELETE FROM medicacion_comidas WHERE id = :id");
            $stmt->execute(['id' => $id]);
            $mensaje = 'Registro eliminado.';
        }
    }
}

$totalMed    = (int) $pdo->query("SELECT COUNT(*) FROM medicacion_comidas WHERE tipo_registro = 'Medicacion'")->fetchColumn();
$totalCom    = (int) $pdo->query("SELECT COUNT(*) FROM medicacion_comidas WHERE tipo_registro = 'Comida'")->fetchColumn();
$costoTotal  = (float) $pdo->query("SELECT COALESCE(SUM(costo), 0) FROM medicacion_comidas")->fetchColumn();

$animales = $pdo->query("SELECT id, codigo, nombre FROM ganado ORDER BY codigo ASC")->fetchAll();

$filtroTipo = $_GET['tipo'] ?? '';
$sqlList = "SELECT mc.*, g.codigo AS codigo_ganado
            FROM medicacion_comidas mc
            LEFT JOIN ganado g ON g.id = mc.ganado_id";
$paramsList = [];

if (in_array($filtroTipo, ['Medicacion', 'Comida'], true)) {
    $sqlList .= " WHERE mc.tipo_registro = :tipo";
    $paramsList['tipo'] = $filtroTipo;
}
$sqlList .= " ORDER BY mc.fecha_aplicacion DESC, mc.id DESC";

$stmtList = $pdo->prepare($sqlList);
$stmtList->execute($paramsList);
$registros = $stmtList->fetchAll();

require_once __DIR__ . '/../header.php';
require_once __DIR__ . '/../menu.php';
?>

<style>
    .grid-mc {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 12px;
        margin-bottom: 20px;
    }
    .card-mc {
        background: #ffffff;
        border: 1px solid #d5e0da;
        border-radius: 10px;
        padding: 15px 16px;
    }
    .card-mc span {
        display: block;
        font-size: 0.82rem;
        color: #526059;
    }
    .card-mc strong {
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
    .barra-filtros {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 14px;
    }
    .barra-filtros a {
        text-decoration: none;
        font-size: 0.84rem;
        font-weight: bold;
        padding: 6px 12px;
        border-radius: 6px;
        background: #ffffff;
        color: #1b4332;
        border: 1px solid #c7d6ce;
    }
    .barra-filtros a.activo {
        background: #2d6a4f;
        color: #ffffff;
        border-color: #2d6a4f;
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

<div class="grid-mc">
    <div class="card-mc">
        <span>Aplicaciones medicación</span>
        <strong><?= $totalMed ?></strong>
    </div>
    <div class="card-mc">
        <span>Registros comida</span>
        <strong><?= $totalCom ?></strong>
    </div>
    <div class="card-mc">
        <span>Costo acumulado</span>
        <strong><?= number_format($costoTotal, 2) ?></strong>
    </div>
</div>

<?php if ($mensaje !== ''): ?>
    <div class="<?= $tipoMensaje === 'error' ? 'alerta-err' : 'alerta-ok' ?>">
        <?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<section class="caja-form">
    <h2>Registrar medicación o comida</h2>
    <form method="POST" action="index.php">
        <input type="hidden" name="accion" value="registrar_aplicacion">
        <div class="fila-campos">
            <div class="campo">
                <label for="tipo_registro">Tipo</label>
                <select id="tipo_registro" name="tipo_registro">
                    <option value="Medicacion">Medicación</option>
                    <option value="Comida">Comida / Suplemento</option>
                </select>
            </div>
            <div class="campo">
                <label for="ganado_id">Animal (opcional)</label>
                <select id="ganado_id" name="ganado_id">
                    <option value="0">-- Todo el lote / General --</option>
                    <?php foreach ($animales as $an): ?>
                        <option value="<?= (int) $an['id'] ?>">
                            <?= htmlspecialchars($an['codigo'] . ($an['nombre'] ? ' - ' . $an['nombre'] : ''), ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="campo">
                <label for="producto">Medicamento o alimento *</label>
                <input type="text" id="producto" name="producto" required placeholder="Ej. Desparasitante, Concentrado...">
            </div>
            <div class="campo">
                <label for="frecuencia">Frecuencia</label>
                <select id="frecuencia" name="frecuencia">
                    <option value="Unica">Única</option>
                    <option value="Semanal">Semanal</option>
                    <option value="Mensual">Mensual</option>
                    <option value="Trimestral">Trimestral</option>
                    <option value="Anual">Anual</option>
                </select>
            </div>
        </div>

        <div class="fila-campos">
            <div class="campo">
                <label for="fecha_aplicacion">Fecha aplicación</label>
                <input type="date" id="fecha_aplicacion" name="fecha_aplicacion" value="<?= date('Y-m-d') ?>">
            </div>
            <div class="campo">
                <label for="proxima_fecha">Próxima fecha (opcional)</label>
                <input type="date" id="proxima_fecha" name="proxima_fecha">
            </div>
            <div class="campo">
                <label for="costo">Costo</label>
                <input type="number" step="0.01" min="0" id="costo" name="costo" value="0">
            </div>
            <div class="campo">
                <label for="observacion">Observación</label>
                <input type="text" id="observacion" name="observacion" placeholder="Dosis, ración o nota...">
            </div>
        </div>

        <button type="submit" class="btn-primario">Guardar registro</button>
    </form>
</section>

<div class="barra-filtros">
    <a href="index.php" class="<?= $filtroTipo === '' ? 'activo' : '' ?>">Todos</a>
    <a href="index.php?tipo=Medicacion" class="<?= $filtroTipo === 'Medicacion' ? 'activo' : '' ?>">Medicación</a>
    <a href="index.php?tipo=Comida" class="<?= $filtroTipo === 'Comida' ? 'activo' : '' ?>">Comida</a>
</div>

<section class="tabla-wrap">
    <table>
        <thead>
        <tr>
            <th>Fecha</th>
            <th>Tipo</th>
            <th>Animal</th>
            <th>Producto</th>
            <th>Frecuencia</th>
            <th>Próxima</th>
            <th>Costo</th>
            <th>Acción</th>
        </tr>
        </thead>
        <tbody>
        <?php if (empty($registros)): ?>
            <tr>
                <td colspan="8">No hay registros en este módulo.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($registros as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['fecha_aplicacion'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($r['tipo_registro'] === 'Comida' ? 'Comida' : 'Medicación', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($r['codigo_ganado'] ?? 'General', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($r['producto'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($r['frecuencia'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($r['proxima_fecha'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><strong><?= number_format((float) $r['costo'], 2) ?></strong></td>
                    <td>
                        <form method="POST" action="index.php" style="display:inline;"
                              onsubmit="return confirm('¿Eliminar este registro?');">
                            <input type="hidden" name="accion" value="eliminar_aplicacion">
                            <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
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