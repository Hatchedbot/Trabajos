<?php
/*
 * REGISTRAR SERVICIO
 * - registrar.php            -> nuevo servicio
 * - registrar.php?id=5       -> editar el servicio 5
 * - registrar.php?desde=5    -> nuevo servicio con los datos de cliente/vehículo del servicio 5
 */
require __DIR__ . '/includes/funciones.php';

$campos_texto = [
    'cliente_nombre' => 120, 'cliente_documento' => 30, 'cliente_telefono' => 30, 'cliente_email' => 120,
    'marca' => 60, 'modelo' => 60, 'placa_serial' => 60, 'color' => 30,
    'kilometraje' => 20, 'nivel_bateria' => 20, 'descripcion_problema' => 5000,
    'accesorios' => 2000, 'tecnico' => 80,
];

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$editando = $id > 0;
$errores = [];

$datos = [
    'cliente_nombre' => '', 'cliente_documento' => '', 'cliente_telefono' => '', 'cliente_email' => '',
    'tipo_vehiculo' => 'moto', 'marca' => '', 'modelo' => '', 'placa_serial' => '', 'color' => '',
    'kilometraje' => '', 'nivel_bateria' => '', 'motivo' => 'mantenimiento', 'descripcion_problema' => '',
    'accesorios' => '', 'prioridad' => 3, 'tecnico' => '',
    'fecha_ingreso' => date('Y-m-d\TH:i'), 'fecha_estimada' => '',
];

if ($editando) {
    $existente = obtener_servicio($id);
    if (!$existente) {
        flash('El servicio no existe.', 'error');
        redirigir('pendientes.php');
    }
    $datos = array_merge($datos, array_intersect_key($existente, $datos));
    $datos['fecha_ingreso'] = date('Y-m-d\TH:i', strtotime($existente['fecha_ingreso']));
    $datos['fecha_estimada'] = $existente['fecha_estimada'] ? substr($existente['fecha_estimada'], 0, 10) : '';
} elseif (!empty($_GET['desde'])) {
    $previo = obtener_servicio((int) $_GET['desde']);
    if ($previo) {
        foreach (['cliente_nombre', 'cliente_documento', 'cliente_telefono', 'cliente_email', 'tipo_vehiculo',
                  'marca', 'modelo', 'placa_serial', 'color'] as $k) {
            $datos[$k] = $previo[$k];
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($campos_texto as $campo => $max) {
        $datos[$campo] = post_texto($campo, $max);
    }
    $datos['tipo_vehiculo'] = (string) ($_POST['tipo_vehiculo'] ?? '');
    $datos['motivo'] = (string) ($_POST['motivo'] ?? '');
    $datos['prioridad'] = (int) ($_POST['prioridad'] ?? 3);
    $datos['fecha_ingreso'] = (string) ($_POST['fecha_ingreso'] ?? '');
    $datos['fecha_estimada'] = (string) ($_POST['fecha_estimada'] ?? '');

    if (!$datos['cliente_nombre']) {
        $errores[] = 'Escribe el nombre del cliente.';
    }
    if (!$datos['cliente_telefono']) {
        $errores[] = 'Escribe el teléfono del cliente.';
    }
    if (!isset(TIPOS_VEHICULO[$datos['tipo_vehiculo']])) {
        $errores[] = 'Selecciona el tipo de vehículo.';
    }
    if (!isset(MOTIVOS[$datos['motivo']])) {
        $errores[] = 'Selecciona el motivo del ingreso.';
    }
    if (!isset(PRIORIDADES[$datos['prioridad']])) {
        $errores[] = 'Selecciona la prioridad.';
    }
    if (!$datos['descripcion_problema']) {
        $errores[] = 'Describe el problema o lo que solicita el cliente.';
    }
    if ($datos['cliente_email'] && !filter_var($datos['cliente_email'], FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'El correo electrónico no es válido.';
    }
    $t_ingreso = strtotime($datos['fecha_ingreso']);
    if ($t_ingreso === false) {
        $errores[] = 'La fecha de ingreso no es válida.';
    }
    $fecha_estimada = null;
    if ($datos['fecha_estimada'] !== '') {
        $t_est = strtotime($datos['fecha_estimada']);
        if ($t_est === false) {
            $errores[] = 'La fecha estimada de entrega no es válida.';
        } else {
            $fecha_estimada = date('Y-m-d', $t_est);
        }
    }

    if (!$errores) {
        $valores = [];
        foreach (array_keys($campos_texto) as $campo) {
            $valores[$campo] = $datos[$campo];
        }
        $valores['tipo_vehiculo'] = $datos['tipo_vehiculo'];
        $valores['motivo'] = $datos['motivo'];
        $valores['prioridad'] = $datos['prioridad'];
        $valores['fecha_ingreso'] = date('Y-m-d H:i:s', $t_ingreso);
        $valores['fecha_estimada'] = $fecha_estimada;

        if ($editando) {
            $sets = implode(', ', array_map(fn($k) => "$k = :$k", array_keys($valores)));
            $valores['id'] = $id;
            db()->prepare("UPDATE servicios SET $sets WHERE id = :id")->execute($valores);
            flash('Datos del servicio ' . numero_orden($id) . ' actualizados.');
        } else {
            $valores['estado'] = 'recibido';
            $cols = implode(', ', array_keys($valores));
            $marcas = ':' . implode(', :', array_keys($valores));
            db()->prepare("INSERT INTO servicios ($cols) VALUES ($marcas)")->execute($valores);
            $id = (int) db()->lastInsertId();
            flash('Servicio registrado con el número ' . numero_orden($id) . '.');
        }
        redirigir('servicio.php?id=' . $id);
    }
}

$titulo = $editando ? 'Editar ' . numero_orden($id) : 'Registrar servicio';
$activa = $editando ? '' : 'registrar';
require __DIR__ . '/includes/header.php';
?>

<div class="encabezado">
    <h1><?= $editando ? 'Editar servicio ' . e(numero_orden($id)) : 'Registrar nuevo servicio' ?></h1>
    <?php if ($editando): ?>
        <a class="btn" href="servicio.php?id=<?= $id ?>">← Volver al servicio</a>
    <?php endif; ?>
</div>

<?php if ($errores): ?>
    <div class="aviso aviso-error">
        <strong>Revisa lo siguiente:</strong>
        <ul><?php foreach ($errores as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<form method="post" class="formulario">
    <?php if ($editando): ?><input type="hidden" name="id" value="<?= $id ?>"><?php endif; ?>

    <fieldset>
        <legend>Cliente</legend>
        <div class="rejilla">
            <label>Nombre completo *
                <input name="cliente_nombre" required maxlength="120" value="<?= e($datos['cliente_nombre']) ?>">
            </label>
            <label>Cédula / NIT
                <input name="cliente_documento" maxlength="30" value="<?= e($datos['cliente_documento']) ?>">
            </label>
            <label>Teléfono *
                <input name="cliente_telefono" type="tel" required maxlength="30" value="<?= e($datos['cliente_telefono']) ?>">
            </label>
            <label>Correo electrónico
                <input name="cliente_email" type="email" maxlength="120" value="<?= e($datos['cliente_email']) ?>">
            </label>
        </div>
    </fieldset>

    <fieldset>
        <legend>Vehículo</legend>
        <div class="opciones-vehiculo">
            <?php foreach (TIPOS_VEHICULO as $clave => $texto): ?>
                <label class="opcion">
                    <input type="radio" name="tipo_vehiculo" value="<?= e($clave) ?>" <?= $datos['tipo_vehiculo'] === $clave ? 'checked' : '' ?>>
                    <span><?= e($texto) ?></span>
                </label>
            <?php endforeach; ?>
        </div>
        <div class="rejilla">
            <label>Marca
                <input name="marca" maxlength="60" value="<?= e($datos['marca']) ?>">
            </label>
            <label>Modelo
                <input name="modelo" maxlength="60" value="<?= e($datos['modelo']) ?>">
            </label>
            <label>Placa / N.º de serie
                <input name="placa_serial" maxlength="60" value="<?= e($datos['placa_serial']) ?>">
            </label>
            <label>Color
                <input name="color" maxlength="30" value="<?= e($datos['color']) ?>">
            </label>
            <label>Kilometraje
                <input name="kilometraje" maxlength="20" value="<?= e($datos['kilometraje']) ?>">
            </label>
            <label>Nivel de batería al ingresar
                <input name="nivel_bateria" maxlength="20" placeholder="Ej: 60%" value="<?= e($datos['nivel_bateria']) ?>">
            </label>
        </div>
    </fieldset>

    <fieldset>
        <legend>Servicio solicitado</legend>
        <div class="rejilla">
            <label>Motivo del ingreso *
                <select name="motivo" required>
                    <?php foreach (MOTIVOS as $clave => $texto): ?>
                        <option value="<?= e($clave) ?>" <?= $datos['motivo'] === $clave ? 'selected' : '' ?>><?= e($texto) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Prioridad *
                <select name="prioridad" required>
                    <?php foreach (PRIORIDADES as $clave => $texto): ?>
                        <option value="<?= $clave ?>" <?= (int) $datos['prioridad'] === $clave ? 'selected' : '' ?>><?= e($texto) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Técnico asignado
                <input name="tecnico" maxlength="80" value="<?= e($datos['tecnico']) ?>">
            </label>
            <label>Fecha y hora de ingreso
                <input name="fecha_ingreso" type="datetime-local" required value="<?= e($datos['fecha_ingreso']) ?>">
            </label>
            <label>Fecha estimada de entrega
                <input name="fecha_estimada" type="date" value="<?= e($datos['fecha_estimada']) ?>">
            </label>
        </div>
        <label>Problema reportado / trabajo solicitado *
            <textarea name="descripcion_problema" rows="4" required><?= e($datos['descripcion_problema']) ?></textarea>
        </label>
        <label>Accesorios y estado en que se recibe
            <textarea name="accesorios" rows="2" placeholder="Ej: cargador, llaves, rayón en guardabarros..."><?= e($datos['accesorios']) ?></textarea>
        </label>
    </fieldset>

    <div class="acciones">
        <button class="btn btn-primario" type="submit"><?= $editando ? 'Guardar cambios' : 'Registrar servicio' ?></button>
    </div>
</form>

<?php require __DIR__ . '/includes/footer.php'; ?>
