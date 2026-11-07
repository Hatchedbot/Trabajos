<?php
/*
 * ORDEN DE SERVICIO IMPRIMIBLE
 * Muestra todo lo que se le hizo al vehículo, repuestos, mano de obra y total.
 * Usa el botón "Imprimir" (o Ctrl+P). También se puede guardar como PDF desde el diálogo de impresión.
 */
require __DIR__ . '/includes/funciones.php';

$id = (int) ($_GET['id'] ?? 0);
$s = $id ? obtener_servicio($id) : null;
if (!$s) {
    flash('El servicio no existe.', 'error');
    redirigir('pendientes.php');
}
$items = obtener_items($id);
$tot = totales($items);
$taller = config()['taller'];
$borrador = isset(ESTADOS_PENDIENTES[$s['estado']]);
$mensajes = tomar_flash();
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Orden <?= e(numero_orden($id)) ?> · <?= e($s['cliente_nombre']) ?></title>
    <link rel="stylesheet" href="assets/estilos.css">
</head>
<body class="pagina-orden">

<div class="barra-orden no-imprimir">
    <a class="btn" href="servicio.php?id=<?= $id ?>">← Volver al servicio</a>
    <button class="btn btn-primario" onclick="window.print()">🖨 Imprimir orden</button>
</div>
<?php foreach ($mensajes as $f): ?>
    <div class="aviso aviso-<?= e($f['tipo']) ?> no-imprimir hoja-ancho"><?= e($f['mensaje']) ?></div>
<?php endforeach; ?>
<?php if ($borrador): ?>
    <div class="aviso aviso-alerta no-imprimir hoja-ancho">Este servicio aún no está finalizado: la orden se imprimirá como BORRADOR.</div>
<?php endif; ?>

<article class="hoja <?= $borrador ? 'borrador' : '' ?>">
    <header class="orden-cabecera">
        <div>
            <h1><?= e($taller['nombre']) ?></h1>
            <p><?= e($taller['nit']) ?><br><?= e($taller['direccion']) ?><br>Tel. <?= e($taller['telefono']) ?> · <?= e($taller['email']) ?></p>
        </div>
        <div class="orden-numero">
            <span>ORDEN DE SERVICIO</span>
            <strong><?= e(numero_orden($id)) ?></strong>
            <small><?= e(etiqueta(estados(), $s['estado'])) ?></small>
        </div>
    </header>

    <table class="orden-datos">
        <tr>
            <th>Fecha de ingreso</th><td><?= e(fecha_fmt($s['fecha_ingreso'])) ?></td>
            <th>Fecha de terminación</th><td><?= e(fecha_fmt($s['fecha_terminado'])) ?></td>
        </tr>
        <tr>
            <th>Fecha de entrega</th><td><?= e(fecha_fmt($s['fecha_entrega'])) ?></td>
            <th>Técnico</th><td><?= e($s['tecnico'] ?: '—') ?></td>
        </tr>
    </table>

    <div class="orden-bloques">
        <section>
            <h2>Cliente</h2>
            <table class="orden-datos">
                <tr><th>Nombre</th><td><?= e($s['cliente_nombre']) ?></td></tr>
                <tr><th>Cédula / NIT</th><td><?= e($s['cliente_documento'] ?: '—') ?></td></tr>
                <tr><th>Teléfono</th><td><?= e($s['cliente_telefono']) ?></td></tr>
                <tr><th>Correo</th><td><?= e($s['cliente_email'] ?: '—') ?></td></tr>
            </table>
        </section>
        <section>
            <h2>Vehículo</h2>
            <table class="orden-datos">
                <tr><th>Tipo</th><td><?= e(etiqueta(TIPOS_VEHICULO, $s['tipo_vehiculo'])) ?></td></tr>
                <tr><th>Marca / Modelo</th><td><?= e(trim($s['marca'] . ' ' . $s['modelo']) ?: '—') ?></td></tr>
                <tr><th>Placa / Serie</th><td><?= e($s['placa_serial'] ?: '—') ?></td></tr>
                <tr><th>Color · Km · Batería</th><td><?= e(implode(' · ', [$s['color'] ?: '—', $s['kilometraje'] ?: '—', $s['nivel_bateria'] ?: '—'])) ?></td></tr>
            </table>
        </section>
    </div>

    <section>
        <h2>Motivo del ingreso: <?= e(etiqueta(MOTIVOS, $s['motivo'])) ?></h2>
        <p class="texto"><strong>Problema reportado:</strong> <?= e_multilinea($s['descripcion_problema']) ?></p>
        <?php if ($s['accesorios']): ?>
            <p class="texto"><strong>Accesorios / estado al ingreso:</strong> <?= e_multilinea($s['accesorios']) ?></p>
        <?php endif; ?>
    </section>

    <?php if ($s['diagnostico']): ?>
        <section>
            <h2>Diagnóstico</h2>
            <p class="texto"><?= e_multilinea($s['diagnostico']) ?></p>
        </section>
    <?php endif; ?>

    <section>
        <h2>Trabajo realizado</h2>
        <p class="texto"><?= e_multilinea($s['trabajo_realizado'] ?: '—') ?></p>
    </section>

    <section>
        <h2>Repuestos y mano de obra</h2>
        <table class="orden-items">
            <thead>
            <tr><th>Tipo</th><th>Descripción</th><th class="num">Cant.</th><th class="num">Valor unit.</th><th class="num">Total</th></tr>
            </thead>
            <tbody>
            <?php if (!$items): ?>
                <tr><td colspan="5">Sin ítems registrados.</td></tr>
            <?php endif; ?>
            <?php foreach ($items as $it): ?>
                <tr>
                    <td><?= e(etiqueta(TIPOS_ITEM, $it['tipo'])) ?></td>
                    <td><?= e($it['descripcion']) ?></td>
                    <td class="num"><?= e(cantidad_fmt($it['cantidad'])) ?></td>
                    <td class="num"><?= e(dinero($it['precio_unitario'])) ?></td>
                    <td class="num"><?= e(dinero((float) $it['cantidad'] * (float) $it['precio_unitario'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
            <tr><td colspan="4" class="num">Mano de obra</td><td class="num"><?= e(dinero($tot['mano_obra'])) ?></td></tr>
            <tr><td colspan="4" class="num">Repuestos</td><td class="num"><?= e(dinero($tot['repuestos'])) ?></td></tr>
            <?php if ($tot['iva_pct'] > 0): ?>
                <tr><td colspan="4" class="num">Subtotal</td><td class="num"><?= e(dinero($tot['subtotal'])) ?></td></tr>
                <tr><td colspan="4" class="num">IVA (<?= e(cantidad_fmt($tot['iva_pct'])) ?>%)</td><td class="num"><?= e(dinero($tot['iva'])) ?></td></tr>
            <?php endif; ?>
            <tr class="total"><td colspan="4" class="num">TOTAL</td><td class="num"><?= e(dinero($tot['total'])) ?></td></tr>
            </tfoot>
        </table>
    </section>

    <?php if ($s['recomendaciones'] || (int) $s['garantia_dias'] > 0): ?>
        <section>
            <h2>Recomendaciones y garantía</h2>
            <?php if ($s['recomendaciones']): ?><p class="texto"><?= e_multilinea($s['recomendaciones']) ?></p><?php endif; ?>
            <?php if ((int) $s['garantia_dias'] > 0): ?>
                <p class="texto"><strong>Garantía:</strong> <?= (int) $s['garantia_dias'] ?> días
                    <?php if ($s['fecha_terminado']): ?>
                        (hasta el <?= e(date('d/m/Y', strtotime($s['fecha_terminado'] . ' +' . (int) $s['garantia_dias'] . ' days'))) ?>)
                    <?php endif; ?>
                    sobre el trabajo realizado.</p>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <div class="firmas">
        <div><span></span>Firma del técnico</div>
        <div><span></span>Firma del cliente (recibido a satisfacción)</div>
    </div>

    <p class="pie-orden"><?= e($taller['nota_orden']) ?></p>
</article>

</body>
</html>
