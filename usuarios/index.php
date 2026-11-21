<?php
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../validar.php';
exigir_admin();

$mensaje = '';
$tipoMensaje = 'exito';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'guardar_usuario') {
        $idEdit  = (int) ($_POST['id_usuario'] ?? 0);
        $nombre  = trim($_POST['nombre'] ?? '');
        $usuario = trim($_POST['usuario'] ?? '');
        $clave   = trim($_POST['clave'] ?? '');
        $rol     = trim($_POST['rol'] ?? 'Encargado');
        $estado  = trim($_POST['estado'] ?? 'Activo');

        if (!in_array($rol, ['Administrador', 'Encargado'], true)) {
            $rol = 'Encargado';
        }
        if (!in_array($estado, ['Activo', 'Inactivo'], true)) {
            $estado = 'Activo';
        }

        if ($nombre === '' || $usuario === '') {
            $mensaje = 'El nombre y el usuario son obligatorios.';
            $tipoMensaje = 'error';
        } elseif ($idEdit === 0 && $clave === '') {
            $mensaje = 'Ingresa una contraseña para el nuevo usuario.';
            $tipoMensaje = 'error';
        } else {
            try {
                if ($idEdit > 0) {
                    if ($clave !== '') {
                        $stmt = $pdo->prepare(
                            "UPDATE usuarios
                             SET nombre = :nombre, usuario = :usuario, clave = :clave, rol = :rol, estado = :estado
                             WHERE id = :id"
                        );
                        $stmt->execute([
                            'nombre'  => $nombre,
                            'usuario' => $usuario,
                            'clave'   => $clave,
                            'rol'     => $rol,
                            'estado'  => $estado,
                            'id'      => $idEdit,
                        ]);
                    } else {
                        $stmt = $pdo->prepare(
                            "UPDATE usuarios
                             SET nombre = :nombre, usuario = :usuario, rol = :rol, estado = :estado
                             WHERE id = :id"
                        );
                        $stmt->execute([
                            'nombre'  => $nombre,
                            'usuario' => $usuario,
                            'rol'     => $rol,
                            'estado'  => $estado,
                            'id'      => $idEdit,
                        ]);
                    }
                    $mensaje = 'Usuario actualizado correctamente.';
                } else {
                    $stmt = $pdo->prepare(
                        "INSERT INTO usuarios (nombre, usuario, clave, rol, estado)
                         VALUES (:nombre, :usuario, :clave, :rol, :estado)"
                    );
                    $stmt->execute([
                        'nombre'  => $nombre,
                        'usuario' => $usuario,
                        'clave'   => $clave,
                        'rol'     => $rol,
                        'estado'  => $estado,
                    ]);
                    $mensaje = 'Nuevo usuario registrado correctamente.';
                }
            } catch (PDOException $e) {
                $mensaje = 'No se pudo guardar el usuario (revisa que el nombre de usuario no esté repetido): ' . $e->getMessage();
                $tipoMensaje = 'error';
            }
        }
    } elseif ($accion === 'eliminar_usuario') {
        $idElim = (int) ($_POST['id'] ?? 0);
        if ($idElim > 0) {
            if ($idElim === (int) ($_SESSION['usuario_id'] ?? 0)) {
                $mensaje = 'No puedes eliminar tu propio usuario mientras tienes sesión iniciada.';
                $tipoMensaje = 'error';
            } else {
                $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = :id");
                $stmt->execute(['id' => $idElim]);
                $mensaje = 'Usuario eliminado.';
            }
        }
    }
}

$usuarioEditar = null;
if (isset($_GET['editar'])) {
    $idEd = (int) $_GET['editar'];
    if ($idEd > 0) {
        $stmtEd = $pdo->prepare("SELECT id, nombre, usuario, clave, rol, estado FROM usuarios WHERE id = :id LIMIT 1");
        $stmtEd->execute(['id' => $idEd]);
        $usuarioEditar = $stmtEd->fetch() ?: null;
    }
}

$buscar = trim($_GET['buscar'] ?? '');
if ($buscar !== '') {
    $stmtList = $pdo->prepare(
        "SELECT id, nombre, usuario, clave, rol, estado, creado_en
         FROM usuarios
         WHERE nombre LIKE :q OR usuario LIKE :q OR rol LIKE :q
         ORDER BY id DESC"
    );
    $stmtList->execute(['q' => '%' . $buscar . '%']);
    $usuarios = $stmtList->fetchAll();
} else {
    $usuarios = $pdo->query(
        "SELECT id, nombre, usuario, clave, rol, estado, creado_en
         FROM usuarios
         ORDER BY id DESC"
    )->fetchAll();
}

require_once __DIR__ . '/../header.php';
require_once __DIR__ . '/../menu.php';
?>

<div class="cabecera-modulo">
    <h2>Gestión de Usuarios del Sistema</h2>
    <div class="acciones-modulo">
        <a href="index.php?nuevo=1#form-usuario" class="btn-azul">+ Registrar Usuario</a>
        <a href="../index.php" class="btn-gris">Menú Principal</a>
    </div>
</div>

<?php if ($mensaje !== ''): ?>
    <div class="<?= $tipoMensaje === 'error' ? 'alerta-err' : 'alerta-ok' ?>">
        <?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<section class="caja-form" id="form-usuario">
    <h2><?= $usuarioEditar ? 'Editar usuario #' . (int) $usuarioEditar['id'] : 'Registrar nuevo usuario' ?></h2>
    <form method="POST" action="index.php">
        <input type="hidden" name="accion" value="guardar_usuario">
        <input type="hidden" name="id_usuario" value="<?= $usuarioEditar ? (int) $usuarioEditar['id'] : 0 ?>">

        <div class="fila-campos">
            <div class="campo">
                <label for="nombre">Nombre completo *</label>
                <input type="text" id="nombre" name="nombre" required
                       value="<?= htmlspecialchars($usuarioEditar['nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                       placeholder="Ej. Encargado Juan">
            </div>
            <div class="campo">
                <label for="usuario">Usuario (login) *</label>
                <input type="text" id="usuario" name="usuario" required
                       value="<?= htmlspecialchars($usuarioEditar['usuario'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                       placeholder="Ej. encargado2">
            </div>
            <div class="campo">
                <label for="clave"><?= $usuarioEditar ? 'Nueva contraseña (dejar igual o cambiar)' : 'Contraseña *' ?></label>
                <input type="text" id="clave" name="clave" <?= $usuarioEditar ? '' : 'required' ?>
                       value="<?= htmlspecialchars($usuarioEditar['clave'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                       placeholder="Ej. 12345">
            </div>
            <div class="campo">
                <label for="rol">Rol</label>
                <select id="rol" name="rol">
                    <option value="Encargado" <?= (($usuarioEditar['rol'] ?? '') === 'Encargado') ? 'selected' : '' ?>>Encargado</option>
                    <option value="Administrador" <?= (($usuarioEditar['rol'] ?? '') === 'Administrador') ? 'selected' : '' ?>>Administrador</option>
                </select>
            </div>
            <div class="campo">
                <label for="estado">Estado</label>
                <select id="estado" name="estado">
                    <option value="Activo" <?= (($usuarioEditar['estado'] ?? 'Activo') === 'Activo') ? 'selected' : '' ?>>Activo</option>
                    <option value="Inactivo" <?= (($usuarioEditar['estado'] ?? '') === 'Inactivo') ? 'selected' : '' ?>>Inactivo</option>
                </select>
            </div>
        </div>

        <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
            <button type="submit" class="btn-primario">
                <?= $usuarioEditar ? 'Guardar cambios' : 'Registrar usuario' ?>
            </button>
            <?php if ($usuarioEditar): ?>
                <a href="index.php" class="btn-gris">Cancelar edición</a>
            <?php endif; ?>
        </div>
    </form>
</section>

<section class="caja-form">
    <form method="GET" action="index.php" class="fila-campos" style="margin-bottom:0; align-items:end;">
        <div class="campo" style="grid-column: span 2;">
            <label for="buscar">Buscar usuario</label>
            <input type="text" id="buscar" name="buscar"
                   value="<?= htmlspecialchars($buscar, ENT_QUOTES, 'UTF-8') ?>"
                   placeholder="Buscar por usuario, nombre o rol...">
        </div>
        <div class="campo">
            <button type="submit" class="btn-azul">Filtrar</button>
            <?php if ($buscar !== ''): ?>
                <a href="index.php" class="btn-gris">Limpiar</a>
            <?php endif; ?>
        </div>
    </form>
</section>

<section class="tabla-wrap">
    <table>
        <thead>
        <tr>
            <th>ID</th>
            <th>Usuario (Login)</th>
            <th>Nombre completo</th>
            <th>Rol</th>
            <th>Estado</th>
            <th>Acciones</th>
        </tr>
        </thead>
        <tbody>
        <?php if (empty($usuarios)): ?>
            <tr>
                <td colspan="6">No se encontraron usuarios.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($usuarios as $u): ?>
                <tr>
                    <td><strong>#<?= str_pad((string) $u['id'], 3, '0', STR_PAD_LEFT) ?></strong></td>
                    <td><strong><?= htmlspecialchars($u['usuario'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                    <td><?= htmlspecialchars($u['nombre'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <span class="rol-badge"><?= htmlspecialchars($u['rol'], ENT_QUOTES, 'UTF-8') ?></span>
                    </td>
                    <td><?= htmlspecialchars($u['estado'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <div style="display:flex; gap:6px; align-items:center;">
                            <a href="index.php?editar=<?= (int) $u['id'] ?>#form-usuario" class="btn-warn">Editar</a>
                            <?php if ((int) $u['id'] !== (int) ($_SESSION['usuario_id'] ?? 0)): ?>
                                <form method="POST" action="index.php" style="display:inline;"
                                      onsubmit="return confirm('¿Eliminar este usuario?');">
                                    <input type="hidden" name="accion" value="eliminar_usuario">
                                    <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                    <button type="submit" class="btn-del">Eliminar</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</section>

<?php require_once __DIR__ . '/../footer.php'; ?>