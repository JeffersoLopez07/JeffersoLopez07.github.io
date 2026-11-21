<?php
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../validar.php';
exigir_login();

$mensaje = '';
$tipoMensaje = 'exito';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'crear') {
        $codigo      = trim($_POST['codigo'] ?? '');
        $nombre      = trim($_POST['nombre'] ?? '');
        $categoria   = trim($_POST['categoria'] ?? 'Ternero/a');
        $sexo        = trim($_POST['sexo'] ?? 'Macho');
        $origen      = trim($_POST['origen'] ?? 'Compra');
        $pesoActual  = (float) ($_POST['peso_actual'] ?? 0);
        $precioCompra= (float) ($_POST['precio_compra'] ?? 0);
        $fechaIng    = trim($_POST['fecha_ingreso'] ?? date('Y-m-d'));
        $estado      = trim($_POST['estado'] ?? 'Activo');
        $observacion = trim($_POST['observacion'] ?? '');

        if ($codigo === '') {
            $mensaje = 'El código o arete del animal es obligatorio.';
            $tipoMensaje = 'error';
        } else {
            try {
                $stmt = $pdo->prepare(
                    "INSERT INTO ganado
                        (codigo, nombre, categoria, sexo, origen, peso_actual, precio_compra, fecha_ingreso, estado, observacion)
                     VALUES
                        (:codigo, :nombre, :categoria, :sexo, :origen, :peso_actual, :precio_compra, :fecha_ingreso, :estado, :observacion)"
                );
                $stmt->execute([
                    'codigo'        => $codigo,
                    'nombre'        => $nombre !== '' ? $nombre : null,
                    'categoria'     => $categoria,
                    'sexo'          => $sexo,
                    'origen'        => $origen,
                    'peso_actual'   => $pesoActual,
                    'precio_compra' => $precioCompra,
                    'fecha_ingreso' => $fechaIng !== '' ? $fechaIng : date('Y-m-d'),
                    'estado'        => $estado,
                    'observacion'   => $observacion !== '' ? $observacion : null,
                ]);
                $mensaje = 'Animal registrado correctamente.';
            } catch (PDOException $e) {
                $mensaje = 'No se pudo registrar el animal (revisa si el código ya existe): ' . $e->getMessage();
                $tipoMensaje = 'error';
            }
        }
    } elseif ($accion === 'actualizar_estado') {
        $id     = (int) ($_POST['id'] ?? 0);
        $peso   = (float) ($_POST['peso_actual'] ?? 0);
        $estado = trim($_POST['estado'] ?? 'Activo');

        if ($id > 0) {
            $stmt = $pdo->prepare(
                "UPDATE ganado
                 SET peso_actual = :peso, estado = :estado
                 WHERE id = :id"
            );
            $stmt->execute([
                'peso'   => $peso,
                'estado' => $estado,
                'id'     => $id,
            ]);
            $mensaje = 'Registro de ganado actualizado.';
        }
    } elseif ($accion === 'eliminar') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare("DELETE FROM ganado WHERE id = :id");
            $stmt->execute(['id' => $id]);
            $mensaje = 'Registro eliminado.';
        }
    }
}

$filtroEstado = $_GET['estado'] ?? '';
$sqlListado = "SELECT * FROM ganado";
$params = [];

if ($filtroEstado !== '' && in_array($filtroEstado, ['Activo', 'Vendido', 'Repasto', 'Baja'], true)) {
    $sqlListado .= " WHERE estado = :estado";
    $params['estado'] = $filtroEstado;
}
$sqlListado .= " ORDER BY id DESC";

$stmtList = $pdo->prepare($sqlListado);
$stmtList->execute($params);
$animales = $stmtList->fetchAll();

$totalActivos = (int) $pdo->query("SELECT COUNT(*) FROM ganado WHERE estado = 'Activo'")->fetchColumn();
$totalRepasto = (int) $pdo->query("SELECT COUNT(*) FROM ganado WHERE estado = 'Repasto'")->fetchColumn();
$totalVendidos= (int) $pdo->query("SELECT COUNT(*) FROM ganado WHERE estado = 'Vendido'")->fetchColumn();

require_once __DIR__ . '/../header.php';
require_once __DIR__ . '/../menu.php';
?>

<style>
    .grid-resumen {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 12px;
        margin-bottom: 20px;
    }
    .card-stat {
        background: #ffffff;
        border: 1px solid #d5e0da;
        border-radius: 10px;
        padding: 14px 16px;
    }
    .card-stat span {
        display: block;
        font-size: 0.82rem;
        color: #526059;
    }
    .card-stat strong {
        font-size: 1.35rem;
        color: #1b4332;
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
        grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
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
        vertical-align: middle;
    }
    th {
        background: #f1f6f3;
        color: #1b4332;
    }
    .form-inline-mini {
        display: flex;
        gap: 6px;
        align-items: center;
        flex-wrap: wrap;
    }
    .form-inline-mini input,
    .form-inline-mini select {
        padding: 5px 7px;
        border: 1px solid #c7d6ce;
        border-radius: 5px;
        font-size: 0.82rem;
    }
    .btn-mini {
        padding: 5px 9px;
        border: none;
        border-radius: 5px;
        font-size: 0.8rem;
        font-weight: bold;
        cursor: pointer;
    }
    .btn-upd {
        background: #2d6a4f;
        color: #ffffff;
    }
    .btn-del {
        background: #c1121f;
        color: #ffffff;
    }
</style>

<div class="grid-resumen">
    <div class="card-stat">
        <span>Activos</span>
        <strong><?= $totalActivos ?></strong>
    </div>
    <div class="card-stat">
        <span>En repasto</span>
        <strong><?= $totalRepasto ?></strong>
    </div>
    <div class="card-stat">
        <span>Vendidos</span>
        <strong><?= $totalVendidos ?></strong>
    </div>
</div>

<?php if ($mensaje !== ''): ?>
    <div class="<?= $tipoMensaje === 'error' ? 'alerta-err' : 'alerta-ok' ?>">
        <?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<section class="caja-form">
    <h2>Registrar animal en ganado</h2>
    <form method="POST" action="index.php">
        <input type="hidden" name="accion" value="crear">
        <div class="fila-campos">
            <div class="campo">
                <label for="codigo">Código / Arete *</label>
                <input type="text" id="codigo" name="codigo" required placeholder="Ej. LM-001">
            </div>
            <div class="campo">
                <label for="nombre">Nombre (opcional)</label>
                <input type="text" id="nombre" name="nombre" placeholder="Ej. Lucero">
            </div>
            <div class="campo">
                <label for="categoria">Categoría</label>
                <select id="categoria" name="categoria">
                    <option value="Ternero/a">Ternero/a</option>
                    <option value="Novillo/a">Novillo/a</option>
                    <option value="Vaca">Vaca</option>
                    <option value="Toro">Toro</option>
                </select>
            </div>
            <div class="campo">
                <label for="sexo">Sexo</label>
                <select id="sexo" name="sexo">
                    <option value="Macho">Macho</option>
                    <option value="Hembra">Hembra</option>
                </select>
            </div>
        </div>

        <div class="fila-campos">
            <div class="campo">
                <label for="origen">Origen</label>
                <select id="origen" name="origen">
                    <option value="Compra">Compra</option>
                    <option value="Nacimiento">Nacimiento</option>
                    <option value="Repasto">Repasto</option>
                </select>
            </div>
            <div class="campo">
                <label for="peso_actual">Peso actual (kg)</label>
                <input type="number" step="0.01" min="0" id="peso_actual" name="peso_actual" value="0">
            </div>
            <div class="campo">
                <label for="precio_compra">Costo / Precio compra</label>
                <input type="number" step="0.01" min="0" id="precio_compra" name="precio_compra" value="0">
            </div>
            <div class="campo">
                <label for="fecha_ingreso">Fecha ingreso</label>
                <input type="date" id="fecha_ingreso" name="fecha_ingreso" value="<?= date('Y-m-d') ?>">
            </div>
            <div class="campo">
                <label for="estado">Estado</label>
                <select id="estado" name="estado">
                    <option value="Activo">Activo</option>
                    <option value="Repasto">Repasto</option>
                    <option value="Vendido">Vendido</option>
                    <option value="Baja">Baja</option>
                </select>
            </div>
        </div>

        <div class="fila-campos">
            <div class="campo" style="grid-column: 1 / -1;">
                <label for="observacion">Observación</label>
                <input type="text" id="observacion" name="observacion" placeholder="Detalles adicionales...">
            </div>
        </div>

        <button type="submit" class="btn-primario">Guardar animal</button>
    </form>
</section>

<div class="barra-filtros">
    <a href="index.php" class="<?= $filtroEstado === '' ? 'activo' : '' ?>">Todos</a>
    <a href="index.php?estado=Activo" class="<?= $filtroEstado === 'Activo' ? 'activo' : '' ?>">Activos</a>
    <a href="index.php?estado=Repasto" class="<?= $filtroEstado === 'Repasto' ? 'activo' : '' ?>">Repasto</a>
    <a href="index.php?estado=Vendido" class="<?= $filtroEstado === 'Vendido' ? 'activo' : '' ?>">Vendidos</a>
    <a href="index.php?estado=Baja" class="<?= $filtroEstado === 'Baja' ? 'activo' : '' ?>">Baja</a>
</div>

<section class="tabla-wrap">
    <table>
        <thead>
        <tr>
            <th>Código</th>
            <th>Nombre</th>
            <th>Categoría / Sexo</th>
            <th>Origen</th>
            <th>Fecha</th>
            <th>Peso / Estado</th>
            <th>Acciones</th>
        </tr>
        </thead>
        <tbody>
        <?php if (empty($animales)): ?>
            <tr>
                <td colspan="7">No hay registros de ganado en este filtro.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($animales as $a): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($a['codigo'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                    <td><?= htmlspecialchars($a['nombre'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($a['categoria'] . ' · ' . $a['sexo'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($a['origen'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($a['fecha_ingreso'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <form method="POST" action="index.php" class="form-inline-mini">
                            <input type="hidden" name="accion" value="actualizar_estado">
                            <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                            <input type="number" step="0.01" min="0" name="peso_actual"
                                   value="<?= htmlspecialchars((string) $a['peso_actual'], ENT_QUOTES, 'UTF-8') ?>"
                                   style="width: 78px;" title="Peso (kg)">
                            <select name="estado">
                                <?php foreach (['Activo', 'Repasto', 'Vendido', 'Baja'] as $st): ?>
                                    <option value="<?= $st ?>" <?= $a['estado'] === $st ? 'selected' : '' ?>><?= $st ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn-mini btn-upd">Guardar</button>
                        </form>
                    </td>
                    <td>
                        <form method="POST" action="index.php" style="display:inline;"
                              onsubmit="return confirm('¿Eliminar este animal del registro?');">
                            <input type="hidden" name="accion" value="eliminar">
                            <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                            <button type="submit" class="btn-mini btn-del">Eliminar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</section>

<?php require_once __DIR__ . '/../footer.php'; ?>