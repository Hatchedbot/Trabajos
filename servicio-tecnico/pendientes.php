<?php
/*
 * SERVICIOS PENDIENTES
 * Lista ordenada por prioridad (urgentes primero) o por orden de llegada.
 */
require __DIR__ . '/includes/funciones.php';

$ordenes = [
    'prioridad' => ['Prioridad (urgentes primero)', 'prioridad ASC, fecha_ingreso ASC, id ASC'],
    'llegada'   => ['Orden de llegada (más antiguos primero)', 'fecha_ingreso ASC, id ASC'],
    'recientes' => ['Más recientes primero', 'fecha_ingreso DESC, id DESC'],
    'entrega'   => ['Fecha estimada de entrega', 'CASE WHEN fecha_estimada IS NULL THEN 1 ELSE 0 END, fecha_estimada ASC, prioridad ASC'],
];
$orden = $_GET['orden'] ?? 'prioridad';
if (!isset($ordenes[$orden])) {
    $orden = 'prioridad';
}
$f_tipo = $_GET['tipo'] ?? '';
$f_estado = $_GET['estado'] ?? '';
$f_prioridad = (int) ($_GET['prioridad'] ?? 0);
$buscar = trim((string) ($_GET['q'] ?? ''));

$where = [];
$params = [];
if (isset(ESTADOS_PENDIENTES[$f_estado])) {
    $where[] = 'estado = ?';
    $params[] = $f_estado;
} else {
    $f_estado = '';
    $where[] = 'estado IN (' . implode(',', array_fill(0, count(ESTADOS_PENDIENTES), '?')) . ')';
    array_push($params, ...array_keys(ESTADOS_PENDIENTES));
}
if (isset(TIPOS_VEHICULO[$f_tipo])) {
    $where[] = 'tipo_vehiculo = ?';
    $params[] = $f_tipo;
} else {
    $f_tipo = '';
}
if (isset(PRIORIDADES[$f_prioridad])) {
    $where[] = 'prioridad = ?';
    $params[] = $f_prioridad;
} else {
    $f_prioridad = 0;
}
if ($buscar !== '') {
    $where[] = '(cliente_nombre LIKE ? OR cliente_telefono LIKE ? OR placa_serial LIKE ? OR marca LIKE ? OR modelo LIKE ?)';
    array_push($params, ...array_fill(0, 5, '%' . $buscar . '%'));
}

$sql = 'SELECT * FROM servicios WHERE ' . implode(' AND ', $where) . ' ORDER BY ' . $ordenes[$orden][1];
$st = db()->prepare($sql);
$st->execute($params);
$servicios = $st->fetchAll();

// Resumen por prioridad (de todos los pendientes, sin filtros)
$marcas = implode(',', array_fill(0, count(ESTADOS_PENDIENTES), '?'));
$st = db()->prepare("SELECT prioridad, COUNT(*) AS n FROM servicios WHERE estado IN ($marcas) GROUP BY prioridad");
$st->execute(array_keys(ESTADOS_PENDIENTES));
$resumen = array_fill_keys(array_keys(PRIORIDADES), 0);
foreach ($st->fetchAll() as $r) {
    $resumen[(int) $r['prioridad']] = (int) $r['n'];
}

// Listos para entregar
$listos = db()->query("SELECT * FROM servicios WHERE estado = 'terminado' ORDER BY fecha_terminado ASC")->fetchAll();

$hoy = date('Y-m-d');
$titulo = 'Pendientes';
$activa = 'pendientes';
require __DIR__ . '/includes/header.php';
?>

<div class="encabezado">
    <h1>Servicios pendientes</h1>
    <a class="btn btn-primario" href="registrar.php">+ Registrar servicio</a>
</div>

<div class="tarjetas-resumen">
    <?php foreach (PRIORIDADES as $p => $texto): ?>
        <a class="tarjeta-resumen prioridad-borde-<?= $p ?> <?= $f_prioridad === $p ? 'seleccionada' : '' ?>"
           href="?<?= e(http_build_query(['orden' => $orden, 'prioridad' => $f_prioridad === $p ? '' : $p])) ?>">
            <span class="numero"><?= $resumen[$p] ?></span>
            <span><?= e($texto) ?></span>
        </a>
    <?php endforeach; ?>
</div>

<form method="get" class="filtros">
    <label>Ordenar por
        <select name="orden" onchange="this.form.submit()">
            <?php foreach ($ordenes as $clave => [$texto]): ?>
                <option value="<?= e($clave) ?>" <?= $orden === $clave ? 'selected' : '' ?>><?= e($texto) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Tipo
        <select name="tipo" onchange="this.form.submit()">
            <option value="">Todos</option>
            <?php foreach (TIPOS_VEHICULO as $clave => $texto): ?>
                <option value="<?= e($clave) ?>" <?= $f_tipo === $clave ? 'selected' : '' ?>><?= e($texto) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Estado
        <select name="estado" onchange="this.form.submit()">
            <option value="">Todos los pendientes</option>
            <?php foreach (ESTADOS_PENDIENTES as $clave => $texto): ?>
                <option value="<?= e($clave) ?>" <?= $f_estado === $clave ? 'selected' : '' ?>><?= e($texto) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Prioridad
        <select name="prioridad" onchange="this.form.submit()">
            <option value="">Todas</option>
            <?php foreach (PRIORIDADES as $clave => $texto): ?>
                <option value="<?= $clave ?>" <?= $f_prioridad === $clave ? 'selected' : '' ?>><?= e($texto) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label class="crece">Buscar
        <input type="search" name="q" value="<?= e($buscar) ?>" placeholder="Cliente, teléfono, placa, marca...">
    </label>
    <button class="btn" type="submit">Filtrar</button>
    <?php if ($f_tipo || $f_estado || $f_prioridad || $buscar !== ''): ?>
        <a class="btn btn-claro" href="?orden=<?= e($orden) ?>">Limpiar</a>
    <?php endif; ?>
</form>

<?php if (!$servicios): ?>
    <div class="vacio">No hay servicios pendientes<?= ($f_tipo || $f_estado || $f_prioridad || $buscar !== '') ? ' con esos filtros' : '' ?>. 🎉</div>
<?php else: ?>
    <div class="tabla-contenedor">
    <table class="tabla">
        <thead>
        <tr>
            <th>#</th>
            <th>Orden</th>
            <th>Prioridad</th>
            <th>Cliente</th>
            <th>Vehículo</th>
            <th>Motivo</th>
            <th>Ingreso</th>
            <th>Entrega est.</th>
            <th>Estado / Cambio rápido</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($servicios as $i => $s): ?>
            <?php $atrasado = $s['fecha_estimada'] && substr($s['fecha_estimada'], 0, 10) < $hoy; ?>
            <tr class="fila-prioridad-<?= (int) $s['prioridad'] ?>">
                <td class="posicion"><?= $i + 1 ?></td>
                <td><a href="servicio.php?id=<?= (int) $s['id'] ?>"><strong><?= e(numero_orden((int) $s['id'])) ?></strong></a></td>
                <td><?= badge_prioridad((int) $s['prioridad']) ?></td>
                <td>
                    <?= e($s['cliente_nombre']) ?><br>
                    <small><?= e($s['cliente_telefono']) ?></small>
                </td>
                <td>
                    <?= e(vehiculo_resumen($s)) ?>
                    <?php if ($s['placa_serial']): ?><br><small><?= e($s['placa_serial']) ?></small><?php endif; ?>
                </td>
                <td>
                    <?= e(etiqueta(MOTIVOS, $s['motivo'])) ?><br>
                    <small class="recorte" title="<?= e($s['descripcion_problema']) ?>"><?= e($s['descripcion_problema']) ?></small>
                </td>
                <td>
                    <?= e(fecha_fmt($s['fecha_ingreso'])) ?><br>
                    <small><?= e(tiempo_transcurrido($s['fecha_ingreso'])) ?></small>
                </td>
                <td class="<?= $atrasado ? 'atrasado' : '' ?>">
                    <?= e(fecha_fmt($s['fecha_estimada'], false)) ?>
                    <?php if ($atrasado): ?><br><small>Atrasado</small><?php endif; ?>
                </td>
                <td>
                    <form method="post" action="servicio.php" class="cambio-rapido">
                        <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                        <input type="hidden" name="accion" value="rapido">
                        <input type="hidden" name="volver" value="pendientes">
                        <select name="estado" onchange="this.form.submit()" aria-label="Estado">
                            <?php foreach (ESTADOS_PENDIENTES as $clave => $texto): ?>
                                <option value="<?= e($clave) ?>" <?= $s['estado'] === $clave ? 'selected' : '' ?>><?= e($texto) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select name="prioridad" onchange="this.form.submit()" aria-label="Prioridad">
                            <?php foreach (PRIORIDADES as $clave => $texto): ?>
                                <option value="<?= $clave ?>" <?= (int) $s['prioridad'] === $clave ? 'selected' : '' ?>><?= e($texto) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </td>
                <td><a class="btn btn-chico" href="servicio.php?id=<?= (int) $s['id'] ?>">Abrir</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
<?php endif; ?>

<?php if ($listos): ?>
    <h2 class="subtitulo">Terminados, listos para entregar (<?= count($listos) ?>)</h2>
    <div class="tabla-contenedor">
    <table class="tabla">
        <thead>
        <tr><th>Orden</th><th>Cliente</th><th>Vehículo</th><th>Terminado</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($listos as $s): ?>
            <tr>
                <td><a href="servicio.php?id=<?= (int) $s['id'] ?>"><strong><?= e(numero_orden((int) $s['id'])) ?></strong></a></td>
                <td><?= e($s['cliente_nombre']) ?><br><small><?= e($s['cliente_telefono']) ?></small></td>
                <td><?= e(vehiculo_resumen($s)) ?></td>
                <td><?= e(fecha_fmt($s['fecha_terminado'])) ?></td>
                <td class="acciones-fila">
                    <a class="btn btn-chico" href="orden.php?id=<?= (int) $s['id'] ?>" target="_blank">Orden</a>
                    <form method="post" action="servicio.php">
                        <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                        <input type="hidden" name="accion" value="entregar">
                        <input type="hidden" name="volver" value="pendientes">
                        <button class="btn btn-chico btn-exito" type="submit">Marcar entregado</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
