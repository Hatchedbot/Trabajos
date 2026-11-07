<?php
/*
 * DETALLE DE UN SERVICIO
 * Aquí se registra el diagnóstico, el trabajo realizado, los repuestos y la mano de obra,
 * se cambia el estado y se finaliza el servicio para generar la orden.
 */
require __DIR__ . '/includes/funciones.php';

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$servicio = $id ? obtener_servicio($id) : null;
if (!$servicio) {
    flash('El servicio no existe.', 'error');
    redirigir('pendientes.php');
}

/* ---------- Acciones (POST) ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = (string) ($_POST['accion'] ?? '');
    $volver = ($_POST['volver'] ?? '') === 'pendientes' ? 'pendientes.php' : 'servicio.php?id=' . $id;
    $pdo = db();

    $guardar_trabajo = function () use ($pdo, $id): void {
        $pdo->prepare('UPDATE servicios SET diagnostico = ?, trabajo_realizado = ?, recomendaciones = ?,
                       tecnico = ?, garantia_dias = ? WHERE id = ?')
            ->execute([
                post_texto('diagnostico'),
                post_texto('trabajo_realizado'),
                post_texto('recomendaciones'),
                post_texto('tecnico', 80),
                max(0, (int) ($_POST['garantia_dias'] ?? 0)),
                $id,
            ]);
    };

    switch ($accion) {
        case 'rapido':
            $estado = (string) ($_POST['estado'] ?? '');
            $prioridad = (int) ($_POST['prioridad'] ?? 0);
            if (isset(ESTADOS_PENDIENTES[$estado]) && isset(PRIORIDADES[$prioridad])) {
                $pdo->prepare('UPDATE servicios SET estado = ?, prioridad = ? WHERE id = ?')
                    ->execute([$estado, $prioridad, $id]);
                flash(numero_orden($id) . ': ' . ESTADOS_PENDIENTES[$estado] . ' · prioridad ' . PRIORIDADES[$prioridad] . '.');
            }
            break;

        case 'guardar_trabajo':
            $guardar_trabajo();
            flash('Información del trabajo guardada.');
            break;

        case 'agregar_item':
            $tipo = (string) ($_POST['tipo'] ?? '');
            $desc = post_texto('descripcion', 200);
            $cant = (float) str_replace(',', '.', (string) ($_POST['cantidad'] ?? '1'));
            $precio = (float) str_replace(',', '.', (string) ($_POST['precio_unitario'] ?? '0'));
            if (!isset(TIPOS_ITEM[$tipo]) || !$desc || $cant <= 0 || $precio < 0) {
                flash('Para agregar un ítem escribe la descripción, una cantidad mayor a 0 y un precio válido.', 'error');
            } else {
                $pdo->prepare('INSERT INTO servicio_items (servicio_id, tipo, descripcion, cantidad, precio_unitario)
                               VALUES (?, ?, ?, ?, ?)')
                    ->execute([$id, $tipo, $desc, $cant, $precio]);
                flash('Ítem agregado.');
            }
            $volver .= '#items';
            break;

        case 'eliminar_item':
            $pdo->prepare('DELETE FROM servicio_items WHERE id = ? AND servicio_id = ?')
                ->execute([(int) ($_POST['item_id'] ?? 0), $id]);
            flash('Ítem eliminado.');
            $volver .= '#items';
            break;

        case 'finalizar':
            $guardar_trabajo();
            if (!post_texto('trabajo_realizado')) {
                flash('Para finalizar, describe el trabajo realizado.', 'error');
                break;
            }
            $pdo->prepare("UPDATE servicios SET estado = 'terminado', fecha_terminado = ? WHERE id = ?")
                ->execute([ahora(), $id]);
            flash('Servicio ' . numero_orden($id) . ' finalizado. Ya puedes imprimir la orden.');
            redirigir('orden.php?id=' . $id);

        case 'entregar':
            $pdo->prepare("UPDATE servicios SET estado = 'entregado', fecha_entrega = ?,
                           fecha_terminado = COALESCE(fecha_terminado, ?) WHERE id = ?")
                ->execute([ahora(), ahora(), $id]);
            flash(numero_orden($id) . ' marcado como entregado al cliente.');
            break;

        case 'reabrir':
            $pdo->prepare("UPDATE servicios SET estado = 'en_reparacion', fecha_terminado = NULL, fecha_entrega = NULL WHERE id = ?")
                ->execute([$id]);
            flash(numero_orden($id) . ' reabierto. Volvió a la lista de pendientes.');
            break;

        case 'cancelar':
            $pdo->prepare("UPDATE servicios SET estado = 'cancelado', fecha_terminado = ? WHERE id = ?")
                ->execute([ahora(), $id]);
            flash(numero_orden($id) . ' cancelado.');
            break;

        case 'eliminar':
            $pdo->prepare('DELETE FROM servicio_items WHERE servicio_id = ?')->execute([$id]);
            $pdo->prepare('DELETE FROM servicios WHERE id = ?')->execute([$id]);
            flash(numero_orden($id) . ' eliminado definitivamente.');
            redirigir('pendientes.php');
    }
    redirigir($volver);
}

/* ---------- Datos para mostrar ---------- */
$items = obtener_items($id);
$tot = totales($items);
$pendiente = isset(ESTADOS_PENDIENTES[$servicio['estado']]);

$anteriores = [];
if ($servicio['placa_serial']) {
    $st = db()->prepare('SELECT id, fecha_ingreso, motivo, estado, trabajo_realizado FROM servicios
                         WHERE placa_serial = ? AND id <> ? ORDER BY fecha_ingreso DESC');
    $st->execute([$servicio['placa_serial'], $id]);
    $anteriores = $st->fetchAll();
}

$titulo = numero_orden($id);
$activa = $pendiente ? 'pendientes' : 'historial';
require __DIR__ . '/includes/header.php';
?>

<div class="encabezado">
    <div>
        <h1>Servicio <?= e(numero_orden($id)) ?></h1>
        <div class="etiquetas"><?= badge_estado($servicio['estado']) ?> <?= badge_prioridad((int) $servicio['prioridad']) ?></div>
    </div>
    <div class="acciones-fila">
        <a class="btn" href="registrar.php?id=<?= $id ?>">Editar datos</a>
        <a class="btn" href="orden.php?id=<?= $id ?>" target="_blank"><?= $pendiente ? 'Vista previa de orden' : '🖨 Imprimir orden' ?></a>
    </div>
</div>

<div class="dos-columnas">
    <section class="panel">
        <h2>Cliente</h2>
        <dl class="datos">
            <dt>Nombre</dt><dd><?= e($servicio['cliente_nombre']) ?></dd>
            <dt>Cédula / NIT</dt><dd><?= e($servicio['cliente_documento'] ?: '—') ?></dd>
            <dt>Teléfono</dt><dd><?= e($servicio['cliente_telefono']) ?></dd>
            <dt>Correo</dt><dd><?= e($servicio['cliente_email'] ?: '—') ?></dd>
        </dl>
    </section>
    <section class="panel">
        <h2>Vehículo</h2>
        <dl class="datos">
            <dt>Tipo</dt><dd><?= e(etiqueta(TIPOS_VEHICULO, $servicio['tipo_vehiculo'])) ?></dd>
            <dt>Marca / Modelo</dt><dd><?= e(trim($servicio['marca'] . ' ' . $servicio['modelo']) ?: '—') ?></dd>
            <dt>Placa / Serie</dt><dd><?= e($servicio['placa_serial'] ?: '—') ?></dd>
            <dt>Color</dt><dd><?= e($servicio['color'] ?: '—') ?></dd>
            <dt>Kilometraje</dt><dd><?= e($servicio['kilometraje'] ?: '—') ?></dd>
            <dt>Batería al ingreso</dt><dd><?= e($servicio['nivel_bateria'] ?: '—') ?></dd>
        </dl>
    </section>
</div>

<section class="panel">
    <h2>Ingreso</h2>
    <dl class="datos datos-ancho">
        <dt>Motivo</dt><dd><?= e(etiqueta(MOTIVOS, $servicio['motivo'])) ?></dd>
        <dt>Fecha de ingreso</dt><dd><?= e(fecha_fmt($servicio['fecha_ingreso'])) ?> <small>(<?= e(tiempo_transcurrido($servicio['fecha_ingreso'])) ?>)</small></dd>
        <dt>Entrega estimada</dt><dd><?= e(fecha_fmt($servicio['fecha_estimada'], false)) ?></dd>
        <?php if ($servicio['fecha_terminado']): ?><dt>Terminado</dt><dd><?= e(fecha_fmt($servicio['fecha_terminado'])) ?></dd><?php endif; ?>
        <?php if ($servicio['fecha_entrega']): ?><dt>Entregado</dt><dd><?= e(fecha_fmt($servicio['fecha_entrega'])) ?></dd><?php endif; ?>
        <dt>Problema reportado</dt><dd><?= e_multilinea($servicio['descripcion_problema']) ?></dd>
        <dt>Accesorios / estado</dt><dd><?= e_multilinea($servicio['accesorios'] ?: '—') ?></dd>
    </dl>

    <?php if ($pendiente): ?>
        <form method="post" class="filtros">
            <input type="hidden" name="id" value="<?= $id ?>">
            <input type="hidden" name="accion" value="rapido">
            <label>Estado
                <select name="estado">
                    <?php foreach (ESTADOS_PENDIENTES as $clave => $texto): ?>
                        <option value="<?= e($clave) ?>" <?= $servicio['estado'] === $clave ? 'selected' : '' ?>><?= e($texto) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Prioridad
                <select name="prioridad">
                    <?php foreach (PRIORIDADES as $clave => $texto): ?>
                        <option value="<?= $clave ?>" <?= (int) $servicio['prioridad'] === $clave ? 'selected' : '' ?>><?= e($texto) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button class="btn" type="submit">Actualizar</button>
        </form>
    <?php endif; ?>
</section>

<section class="panel" id="items">
    <h2>Repuestos y mano de obra</h2>
    <div class="tabla-contenedor">
    <table class="tabla tabla-items">
        <thead>
        <tr><th>Tipo</th><th>Descripción</th><th class="num">Cant.</th><th class="num">Valor unit.</th><th class="num">Total</th><th></th></tr>
        </thead>
        <tbody>
        <?php if (!$items): ?>
            <tr><td colspan="6" class="vacio-celda">Aún no hay ítems. Agrega abajo los repuestos usados y la mano de obra.</td></tr>
        <?php endif; ?>
        <?php foreach ($items as $it): ?>
            <tr>
                <td><?= e(etiqueta(TIPOS_ITEM, $it['tipo'])) ?></td>
                <td><?= e($it['descripcion']) ?></td>
                <td class="num"><?= e(cantidad_fmt($it['cantidad'])) ?></td>
                <td class="num"><?= e(dinero($it['precio_unitario'])) ?></td>
                <td class="num"><?= e(dinero((float) $it['cantidad'] * (float) $it['precio_unitario'])) ?></td>
                <td>
                    <form method="post" onsubmit="return confirm('¿Eliminar este ítem?')">
                        <input type="hidden" name="id" value="<?= $id ?>">
                        <input type="hidden" name="accion" value="eliminar_item">
                        <input type="hidden" name="item_id" value="<?= (int) $it['id'] ?>">
                        <button class="btn btn-chico btn-peligro" type="submit" title="Eliminar">✕</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
        <tr><td colspan="4" class="num">Mano de obra</td><td class="num"><?= e(dinero($tot['mano_obra'])) ?></td><td></td></tr>
        <tr><td colspan="4" class="num">Repuestos</td><td class="num"><?= e(dinero($tot['repuestos'])) ?></td><td></td></tr>
        <?php if ($tot['iva_pct'] > 0): ?>
            <tr><td colspan="4" class="num">IVA (<?= e(cantidad_fmt($tot['iva_pct'])) ?>%)</td><td class="num"><?= e(dinero($tot['iva'])) ?></td><td></td></tr>
        <?php endif; ?>
        <tr class="total"><td colspan="4" class="num">TOTAL</td><td class="num"><?= e(dinero($tot['total'])) ?></td><td></td></tr>
        </tfoot>
    </table>
    </div>

    <form method="post" class="filtros agregar-item">
        <input type="hidden" name="id" value="<?= $id ?>">
        <input type="hidden" name="accion" value="agregar_item">
        <label>Tipo
            <select name="tipo">
                <?php foreach (TIPOS_ITEM as $clave => $texto): ?>
                    <option value="<?= e($clave) ?>"><?= e($texto) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="crece">Descripción
            <input name="descripcion" required maxlength="200" placeholder="Ej: Cambio de pastillas de freno / Controlador 48V">
        </label>
        <label>Cantidad
            <input name="cantidad" type="number" min="0.01" step="any" value="1" required class="corto">
        </label>
        <label>Valor unitario
            <input name="precio_unitario" type="number" min="0" step="any" value="0" required class="medio">
        </label>
        <button class="btn" type="submit">+ Agregar</button>
    </form>
</section>

<section class="panel">
    <h2>Diagnóstico y trabajo realizado</h2>
    <form method="post" class="formulario">
        <input type="hidden" name="id" value="<?= $id ?>">
        <label>Diagnóstico
            <textarea name="diagnostico" rows="3" placeholder="¿Qué se encontró al revisar el vehículo?"><?= e($servicio['diagnostico']) ?></textarea>
        </label>
        <label>Trabajo realizado <?= $pendiente ? '(obligatorio para finalizar)' : '' ?>
            <textarea name="trabajo_realizado" rows="5" placeholder="Describe todo lo que se le hizo al vehículo"><?= e($servicio['trabajo_realizado']) ?></textarea>
        </label>
        <label>Recomendaciones para el cliente
            <textarea name="recomendaciones" rows="2"><?= e($servicio['recomendaciones']) ?></textarea>
        </label>
        <div class="rejilla">
            <label>Técnico responsable
                <input name="tecnico" maxlength="80" value="<?= e($servicio['tecnico']) ?>">
            </label>
            <label>Garantía (días)
                <input name="garantia_dias" type="number" min="0" step="1" value="<?= (int) $servicio['garantia_dias'] ?>">
            </label>
        </div>
        <div class="acciones">
            <button class="btn" type="submit" name="accion" value="guardar_trabajo">Guardar</button>
            <?php if ($pendiente): ?>
                <button class="btn btn-exito" type="submit" name="accion" value="finalizar"
                        onclick="return confirm('¿Finalizar el servicio y generar la orden?')">✔ Finalizar servicio y generar orden</button>
            <?php endif; ?>
        </div>
    </form>
</section>

<section class="panel acciones-estado">
    <h2>Estado del servicio</h2>
    <div class="acciones-fila">
        <?php if ($servicio['estado'] === 'terminado'): ?>
            <form method="post"><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="accion" value="entregar">
                <button class="btn btn-exito" type="submit">Marcar como entregado al cliente</button></form>
        <?php endif; ?>
        <?php if (!$pendiente): ?>
            <form method="post" onsubmit="return confirm('¿Reabrir este servicio? Volverá a pendientes.')"><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="accion" value="reabrir">
                <button class="btn" type="submit">Reabrir servicio</button></form>
        <?php else: ?>
            <form method="post" onsubmit="return confirm('¿Cancelar este servicio?')"><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="accion" value="cancelar">
                <button class="btn" type="submit">Cancelar servicio</button></form>
        <?php endif; ?>
        <a class="btn" href="registrar.php?desde=<?= $id ?>">Nuevo servicio para este vehículo</a>
        <form method="post" onsubmit="return confirm('Esto borra el servicio y todos sus ítems para siempre. ¿Continuar?')"><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="accion" value="eliminar">
            <button class="btn btn-peligro" type="submit">Eliminar</button></form>
    </div>
</section>

<?php if ($anteriores): ?>
    <section class="panel">
        <h2>Otros servicios de este vehículo (<?= e($servicio['placa_serial']) ?>)</h2>
        <div class="tabla-contenedor">
        <table class="tabla">
            <thead><tr><th>Orden</th><th>Ingreso</th><th>Motivo</th><th>Estado</th><th>Trabajo realizado</th></tr></thead>
            <tbody>
            <?php foreach ($anteriores as $a): ?>
                <tr>
                    <td><a href="servicio.php?id=<?= (int) $a['id'] ?>"><?= e(numero_orden((int) $a['id'])) ?></a></td>
                    <td><?= e(fecha_fmt($a['fecha_ingreso'], false)) ?></td>
                    <td><?= e(etiqueta(MOTIVOS, $a['motivo'])) ?></td>
                    <td><?= badge_estado($a['estado']) ?></td>
                    <td><small class="recorte"><?= e($a['trabajo_realizado'] ?: '—') ?></small></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </section>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
