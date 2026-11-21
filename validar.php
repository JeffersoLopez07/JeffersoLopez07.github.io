<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function ruta_raiz_sistema(): string {
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');

    $posSub = strpos($scriptName, '/control_ganado_lm/');
    if ($posSub !== false) {
        return substr($scriptName, 0, $posSub) . '/control_ganado_lm';
    }

    $dir = dirname($scriptName);
    if (basename($dir) === 'control_ganado_lm') {
        return ($dir === '/' || $dir === '.') ? '' : $dir;
    }

    $parent = dirname($dir);
    if (basename($parent) === 'control_ganado_lm') {
        return ($parent === '/' || $parent === '.') ? '' : $parent;
    }

    $subcarpetas = ['/ganado/', '/presupuesto/', '/ventas/', '/medicacion_comidas/', '/usuarios/', '/informe/', '/respaldo/'];
    foreach ($subcarpetas as $sub) {
        $pos = strpos($scriptName, $sub);
        if ($pos !== false) {
            $raiz = substr($scriptName, 0, $pos);
            return ($raiz === '/' || $raiz === '.') ? '' : $raiz;
        }
    }

    return '';
}

function es_administrador(): bool {
    return strtolower(trim((string) ($_SESSION['rol'] ?? ''))) === 'administrador';
}

function exigir_login(): void {
    if (empty($_SESSION['usuario_id'])) {
        header('Location: ' . ruta_raiz_sistema() . '/index.php');
        exit;
    }
}

function exigir_admin(): void {
    exigir_login();
    if (!es_administrador()) {
        header('Location: ' . ruta_raiz_sistema() . '/index.php?sin_permiso=1');
        exit;
    }
}