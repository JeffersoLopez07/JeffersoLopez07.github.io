<?php
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../validar.php';
exigir_admin();

$carpetaBackup = __DIR__ . '/../backup';
if (!is_dir($carpetaBackup)) {
    mkdir($carpetaBackup, 0775, true);
}

function generar_sql_respaldo(PDO $pdo, string $nombreDb): string {
    $sql = "-- Respaldo de base de datos: {$nombreDb}\n";
    $sql .= "-- Fecha: " . date('Y-m-d H:i:s') . "\n\n";
    $sql .= "SET NAMES utf8mb4;\n";
    $sql .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

    $tablas = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_NUM);

    foreach ($tablas as $filaTabla) {
        $tabla = $filaTabla[0];
        $tablaEsc = str_replace('`', '``', $tabla);

        $createRow = $pdo->query("SHOW CREATE TABLE `{$tablaEsc}`")->fetch(PDO::FETCH_NUM);
        $sql .= "-- Estructura de tabla `{$tabla}`\n";
        $sql .= "DROP TABLE IF EXISTS `{$tablaEsc}`;\n";
        $sql .= ($createRow[1] ?? '') . ";\n\n";

        $stmtRows = $pdo->query("SELECT * FROM `{$tablaEsc}`");
        $rows = $stmtRows->fetchAll(PDO::FETCH_NUM);

        if (!empty($rows)) {
            $sql .= "-- Datos de tabla `{$tabla}`\n";
            foreach ($rows as $row) {
                $vals = [];
                foreach ($row as $val) {
                    if ($val === null) {
                        $vals[] = 'NULL';
                    } elseif (is_int($val) || is_float($val)) {
                        $vals[] = (string) $val;
                    } else {
                        $vals[] = $pdo->quote((string) $val);
                    }
                }
                $sql .= "INSERT INTO `{$tablaEsc}` VALUES (" . implode(', ', $vals) . ");\n";
            }
            $sql .= "\n";
        }
    }

    $sql .= "SET FOREIGN_KEY_CHECKS = 1;\n";
    return $sql;
}

function ejecutar_script_sql(PDO $pdo, string $contenidoSql): void {
    $lineas = preg_split("/\r\n|\n|\r/", $contenidoSql);
    $limpio = '';
    foreach ($lineas as $linea) {
        $trim = ltrim($linea);
        if ($trim === '' || str_starts_with($trim, '--') || str_starts_with($trim, '#')) {
            continue;
        }
        $limpio .= $linea . "\n";
    }

    $sentencias = explode(';', $limpio);
    foreach ($sentencias as $sentencia) {
        $consulta = trim($sentencia);
        if ($consulta !== '') {
            $pdo->exec($consulta);
        }
    }
}

$mensaje = '';
$tipoMensaje = 'exito';

if (isset($_GET['descargar'])) {
    $archivoSolicitado = basename((string) $_GET['descargar']);
    $rutaArchivo = $carpetaBackup . DIRECTORY_SEPARATOR . $archivoSolicitado;

    if (preg_match('/^[a-zA-Z0-9._-]+\.sql$/', $archivoSolicitado) === 1 && is_file($rutaArchivo)) {
        header('Content-Type: application/sql; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $archivoSolicitado . '"');
        header('Content-Length: ' . filesize($rutaArchivo));
        readfile($rutaArchivo);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'crear_respaldo') {
        try {
            $nombreArchivo = 'respaldo_control_ganado_lm_' . date('Y-m-d_H-i-s') . '.sql';
            $rutaDestino = $carpetaBackup . DIRECTORY_SEPARATOR . $nombreArchivo;
            $contenido = generar_sql_respaldo($pdo, 'control_ganado_lm');

            if (file_put_contents($rutaDestino, $contenido) === false) {
                throw new RuntimeException('No se pudo escribir el archivo en backup/.');
            }
            $mensaje = 'Respaldo creado en backup/: ' . $nombreArchivo;
        } catch (Throwable $e) {
            $mensaje = 'Error al crear respaldo: ' . $e->getMessage();
            $tipoMensaje = 'error';
        }
    } elseif ($accion === 'restaurar_archivo') {
        $archivoSel = basename((string) ($_POST['archivo_sql'] ?? ''));
        $rutaSel = $carpetaBackup . DIRECTORY_SEPARATOR . $archivoSel;

        if (preg_match('/^[a-zA-Z0-9._-]+\.sql$/', $archivoSel) !== 1 || !is_file($rutaSel)) {
            $mensaje = 'Selecciona un archivo .sql válido.';
            $tipoMensaje = 'error';
        } else {
            try {
                $sqlData = file_get_contents($rutaSel);
                if ($sqlData === false) {
                    throw new RuntimeException('No se pudo leer el archivo seleccionado.');
                }
                ejecutar_script_sql($pdo, $sqlData);
                $mensaje = 'Base de datos restaurada desde: ' . $archivoSel;
            } catch (Throwable $e) {
                $mensaje = 'Error al restaurar: ' . $e->getMessage();
                $tipoMensaje = 'error';
            }
        }
    } elseif ($accion === 'subir_y_restaurar') {
        if (empty($_FILES['archivo_subido']['tmp_name']) || ($_FILES['archivo_subido']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $mensaje = 'Selecciona un archivo .sql para subir.';
            $tipoMensaje = 'error';
        } else {
            $nombreOrig = basename((string) $_FILES['archivo_subido']['name']);
            if (!str_ends_with(strtolower($nombreOrig), '.sql')) {
                $mensaje = 'Solo se permiten archivos con extensión .sql.';
                $tipoMensaje = 'error';
            } else {
                try {
                    $sqlData = file_get_contents($_FILES['archivo_subido']['tmp_name']);
                    if ($sqlData === false) {
                        throw new RuntimeException('No se pudo leer el archivo subido.');
                    }
                    ejecutar_script_sql($pdo, $sqlData);
                    $mensaje = 'Base de datos restaurada desde el archivo subido: ' . $nombreOrig;
                } catch (Throwable $e) {
                    $mensaje = 'Error al restaurar archivo subido: ' . $e->getMessage();
                    $tipoMensaje = 'error';
                }
            }
        }
    } elseif ($accion === 'eliminar_archivo') {
        $archivoDel = basename((string) ($_POST['archivo_sql'] ?? ''));
        $rutaDel = $carpetaBackup . DIRECTORY_SEPARATOR . $archivoDel;

        if (preg_match('/^[a-zA-Z0-9._-]+\.sql$/', $archivoDel) === 1 && is_file($rutaDel)) {
            unlink($rutaDel);
            $mensaje = 'Archivo de respaldo eliminado: ' . $archivoDel;
        }
    }
}

$archivosSql = [];
if (is_dir($carpetaBackup)) {
    $lista = scandir($carpetaBackup);
    if ($lista !== false) {
        foreach ($lista as $item) {
            if ($item !== '.' && $item !== '..' && str_ends_with(strtolower($item), '.sql')) {
                $rutaFull = $carpetaBackup . DIRECTORY_SEPARATOR . $item;
                $archivosSql[] = [
                    'nombre'     => $item,
                    'tamano'     => filesize($rutaFull) ?: 0,
                    'modificado' => date('Y-m-d H:i:s', filemtime($rutaFull) ?: time()),
                ];
            }
        }
        usort($archivosSql, static fn(array $a, array $b): int => strcmp($b['modificado'], $a['modificado']));
    }
}

require_once __DIR__ . '/../header.php';
require_once __DIR__ . '/../menu.php';
?>

<?php if ($mensaje !== ''): ?>
    <div class="<?= $tipoMensaje === 'error' ? 'alerta-err' : 'alerta-ok' ?>">
        <?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<div class="fila-campos" style="margin-bottom: 18px;">
    <section class="caja-respaldo" style="margin-bottom: 0;">
        <h2>Generar respaldo ahora</h2>
        <p style="font-size: 0.85rem; color: #64748b; margin-bottom: 12px;">
            Crea un archivo <code>.sql</code> dentro de la carpeta <code>backup/</code>.
        </p>
        <form method="POST" action="index.php">
            <input type="hidden" name="accion" value="crear_respaldo">
            <button type="submit" class="btn-primario">Crear respaldo .sql</button>
        </form>
    </section>

    <section class="caja-respaldo" style="margin-bottom: 0;">
        <h2>Restaurar desde archivo .sql</h2>
        <form method="POST" action="index.php" enctype="multipart/form-data"
              onsubmit="return confirm('¿Restaurar la base de datos con este archivo? Se sobrescribirán los datos actuales.');">
            <input type="hidden" name="accion" value="subir_y_restaurar">
            <div class="campo">
                <label for="archivo_subido">Seleccionar archivo (.sql)</label>
                <input type="file" id="archivo_subido" name="archivo_subido" accept=".sql" required>
            </div>
            <button type="submit" class="btn-primario">Subir y restaurar</button>
        </form>
    </section>
</div>

<section class="tabla-wrap">
    <h2>Respaldos guardados en backup/</h2>
    <table>
        <thead>
        <tr>
            <th>Archivo</th>
            <th>Fecha</th>
            <th>Tamaño</th>
            <th>Acciones</th>
        </tr>
        </thead>
        <tbody>
        <?php if (empty($archivosSql)): ?>
            <tr>
                <td colspan="4">No hay archivos .sql en la carpeta backup/.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($archivosSql as $arc): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($arc['nombre'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                    <td><?= htmlspecialchars($arc['modificado'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= number_format($arc['tamano'] / 1024, 2) ?> KB</td>
                    <td>
                        <div style="display:flex; gap:6px; flex-wrap:wrap; align-items:center;">
                            <a href="index.php?descargar=<?= urlencode($arc['nombre']) ?>" class="btn-sec">Descargar</a>

                            <form method="POST" action="index.php" style="display:inline;"
                                  onsubmit="return confirm('¿Restaurar la base de datos desde <?= htmlspecialchars($arc['nombre'], ENT_QUOTES, 'UTF-8') ?>?');">
                                <input type="hidden" name="accion" value="restaurar_archivo">
                                <input type="hidden" name="archivo_sql" value="<?= htmlspecialchars($arc['nombre'], ENT_QUOTES, 'UTF-8') ?>">
                                <button type="submit" class="btn-warn">Restaurar</button>
                            </form>

                            <form method="POST" action="index.php" style="display:inline;"
                                  onsubmit="return confirm('¿Eliminar este archivo de respaldo?');">
                                <input type="hidden" name="accion" value="eliminar_archivo">
                                <input type="hidden" name="archivo_sql" value="<?= htmlspecialchars($arc['nombre'], ENT_QUOTES, 'UTF-8') ?>">
                                <button type="submit" class="btn-del">Eliminar</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</section>

<?php require_once __DIR__ . '/../footer.php'; ?>