<?php
/*
 * HISTORIAL
 * Servicios ya terminados, entregados o cancelados. Se puede buscar por cliente,
 * placa/serie, filtrar por tipo de vehículo, estado y rango de fechas.
 */
require __DIR__ . '/includes/funciones.php';

$f_estado = $_GET['estado'] ?? '';
$f_tipo = $_GET['tipo'] ?? '';
$desde = $_GET['desde'] ?? '';
$hasta = $_GET['hasta'] ?? '';
$buscar = trim((string) ($_GET['q'] ?? ''));
$por_pagina = 50;
$pagina = max(1, (int) ($_GET['pagina'] ?? 1));

$where = [];
$params = [];
if (isset(ESTADOS_CERRADOS[$f_estado])) {
    $where[] = 'estado = ?';
    $params[] = $f_estado;
} else {
    $f_estado = '';
    $where[] = 'estado IN (' . implode(',', array_fill(0, count(ESTADOS_CERRADOS), '?')) . ')';
    array_push($params, ...array_keys(ESTADOS_CERRADOS));
}
if (isset(TIPOS_VEHICULO[$f_tipo])) {
    $where[] = 'tipo_vehiculo = ?';
    $params[] = $f_tipo;
} else {
    $f_tipo = '';
}
if ($desde !== '' && strtotime($desde) !== false) {
    $where[] = 'fecha_terminado >= ?';
    $params[] = date('Y-m-d 00:00:00', strtotime($desde));
} else {
    $desde = '';
}
if ($hasta !== '' && strtotime($hasta) !== false) {
    $where[] = 'fecha_terminado <= ?';
    $params[] = date('Y-m-d 23:59:59', strtotime($hasta));
} else {
    $hasta = '';
}
if ($buscar !== '') {
    $columnas = ['cliente_nombre', 'cliente_telefono', 'cliente_documento', 'placa_serial', 'marca', 'modelo', 'trabajo_realizado'];
    $opciones = array_map(fn($c) => "$c LIKE ?", $columnas);
    array_push($params, ...array_fill(0, count($columnas), '%' . $buscar . '%'));
    // También permite buscar por número de orden (OS-00012 o 12)
    if (preg_match('/^(?:OS-?)?0*(\d+)$/i', $buscar, $m)) {
        $opciones[] = 'id = ?';
        $params[] = (int) $m[1];
    }
    $where[] = '(' . implode(' OR ', $opciones) . ')';
}
$cond = implode(' AND ', $where);

$st = db()->prepare("SELECT COUNT(*) FROM servicios WHERE $cond");
$st->execute($params);
$total = (int) $st->fetchColumn();
$paginas = max(1, (int) ceil($total / $por_pagina));
$pagina = min($pagina, $paginas);
$offset = ($pagina - 1) * $por_pagina;

$st = db()->prepare("SELECT s.*,
        (SELECT COALESCE(SUM(cantidad * precio_unitario), 0) FROM servicio_items i WHERE i.servicio_id = s.id) AS subtotal
    FROM servicios s WHERE $cond
    ORDER BY COALESCE(fecha_entrega, fecha_terminado, fecha_ingreso) DESC, id DESC
    LIMIT $por_pagina OFFSET $offset");
$st->execute($params);
$servicios = $st->fetchAll();

$filtros_activos = $f_estado || $f_tipo || $desde || $hasta || $buscar !== '';
$titulo = 'Historial';
$activa = 'historial';
require __DIR__ . '/includes/header.php';
?>

<div class="encabezado">
    <h1>Historial de servicios</h1>
    <span class="nota"><?= $total ?> servicio<?= $total === 1 ? '' : 's' ?></span>
</div>

<form method="get" class="filtros">
    <label class="crece">Buscar
        <input type="search" name="q" value="<?= e($buscar) ?>" placeholder="N.º de orden, cliente, cédula, placa/serie, trabajo...">
    </label>
    <label>Tipo
        <select name="tipo">
            <option value="">Todos</option>
            <?php foreach (TIPOS_VEHICULO as $clave => $texto): ?>
                <option value="<?= e($clave) ?>" <?= $f_tipo === $clave ? 'selected' : '' ?>><?= e($texto) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Estado
        <select name="estado">
            <option value="">Todos</option>
            <?php foreach (ESTADOS_CERRADOS as $clave => $texto): ?>
                <option value="<?= e($clave) ?>" <?= $f_estado === $clave ? 'selected' : '' ?>><?= e($texto) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Terminado desde
        <input type="date" name="desde" value="<?= e($desde) ?>">
    </label>
    <label>hasta
        <input type="date" name="hasta" value="<?= e($hasta) ?>">
    </label>
    <button class="btn" type="submit">Buscar</button>
    <?php if ($filtros_activos): ?><a class="btn btn-claro" href="historial.php">Limpiar</a><?php endif; ?>
</form>

<?php if (!$servicios): ?>
    <div class="vacio">No hay servicios en el historial<?= $filtros_activos ? ' con esos filtros' : ' todavía' ?>.</div>
<?php else: ?>
    <div class="tabla-contenedor">
    <table class="tabla">
        <thead>
        <tr>
            <th>Orden</th><th>Cliente</th><th>Vehículo</th><th>Motivo</th><th>Trabajo realizado</th>
            <th>Ingreso</th><th>Terminado</th><th>Estado</th><th class="num">Total</th><th></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($servicios as $s): ?>
            <?php $sub = (float) $s['subtotal']; $tot = $sub + round($sub * (float) config()['iva'] / 100, (int) config()['decimales']); ?>
            <tr>
                <td><a href="servicio.php?id=<?= (int) $s['id'] ?>"><strong><?= e(numero_orden((int) $s['id'])) ?></strong></a></td>
                <td><?= e($s['cliente_nombre']) ?><br><small><?= e($s['cliente_telefono']) ?></small></td>
                <td>
                    <?= e(vehiculo_resumen($s)) ?>
                    <?php if ($s['placa_serial']): ?><br><a href="?q=<?= urlencode($s['placa_serial']) ?>" title="Ver todos los servicios de este vehículo"><small><?= e($s['placa_serial']) ?></small></a><?php endif; ?>
                </td>
                <td><?= e(etiqueta(MOTIVOS, $s['motivo'])) ?></td>
                <td><small class="recorte" title="<?= e($s['trabajo_realizado']) ?>"><?= e($s['trabajo_realizado'] ?: '—') ?></small></td>
                <td><?= e(fecha_fmt($s['fecha_ingreso'], false)) ?></td>
                <td><?= e(fecha_fmt($s['fecha_terminado'], false)) ?></td>
                <td><?= badge_estado($s['estado']) ?></td>
                <td class="num"><?= e(dinero($tot)) ?></td>
                <td class="acciones-fila">
                    <a class="btn btn-chico" href="servicio.php?id=<?= (int) $s['id'] ?>">Ver</a>
                    <?php if ($s['estado'] !== 'cancelado'): ?>
                        <a class="btn btn-chico" href="orden.php?id=<?= (int) $s['id'] ?>" target="_blank">🖨 Orden</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>

    <?php if ($paginas > 1): ?>
        <nav class="paginacion">
            <?php for ($p = 1; $p <= $paginas; $p++): ?>
                <a class="<?= $p === $pagina ? 'activa' : '' ?>"
                   href="?<?= e(http_build_query(['q' => $buscar, 'tipo' => $f_tipo, 'estado' => $f_estado, 'desde' => $desde, 'hasta' => $hasta, 'pagina' => $p])) ?>"><?= $p ?></a>
            <?php endfor; ?>
        </nav>
    <?php endif; ?>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
