<?php
/** @var string $titulo */
/** @var string $activa */
$titulo = $titulo ?? 'Servicio técnico';
$activa = $activa ?? '';
$n_pendientes = contar_pendientes();
$menu = [
    'registrar'  => ['registrar.php', 'Registrar servicio'],
    'pendientes' => ['pendientes.php', 'Pendientes'],
    'historial'  => ['historial.php', 'Historial'],
];
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo) ?> · <?= e(config()['taller']['nombre']) ?></title>
    <link rel="stylesheet" href="assets/estilos.css">
</head>
<body>
<header class="barra">
    <a class="marca" href="pendientes.php">⚡ <?= e(config()['taller']['nombre']) ?></a>
    <nav>
        <?php foreach ($menu as $clave => [$url, $texto]): ?>
            <a href="<?= e($url) ?>" class="<?= $activa === $clave ? 'activa' : '' ?>">
                <?= e($texto) ?>
                <?php if ($clave === 'pendientes' && $n_pendientes > 0): ?>
                    <span class="contador"><?= $n_pendientes ?></span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </nav>
</header>
<main class="contenido">
<?php foreach (tomar_flash() as $f): ?>
    <div class="aviso aviso-<?= e($f['tipo']) ?>"><?= e($f['mensaje']) ?></div>
<?php endforeach; ?>
