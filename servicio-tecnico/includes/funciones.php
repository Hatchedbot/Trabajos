<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function config(): array
{
    static $config = null;
    if ($config === null) {
        $config = require __DIR__ . '/../config.php';
    }
    return $config;
}

date_default_timezone_set(config()['zona_horaria']);
require_once __DIR__ . '/db.php';

/* ---------- Catálogos ---------- */

const TIPOS_VEHICULO = [
    'carro'     => 'Carro eléctrico',
    'moto'      => 'Moto eléctrica',
    'scooter'   => 'Scooter / Patineta',
    'bicicleta' => 'Bicicleta eléctrica',
    'otro'      => 'Otro',
];

const MOTIVOS = [
    'mantenimiento' => 'Mantenimiento',
    'reparacion'    => 'Servicio técnico / Reparación',
    'revision'      => 'Revisión / Diagnóstico',
    'garantia'      => 'Garantía',
    'otro'          => 'Otro',
];

const PRIORIDADES = [
    1 => 'Urgente',
    2 => 'Alta',
    3 => 'Normal',
    4 => 'Baja',
];

// Estados en los que el servicio sigue "pendiente"
const ESTADOS_PENDIENTES = [
    'recibido'            => 'Recibido',
    'diagnostico'         => 'En diagnóstico',
    'en_reparacion'       => 'En reparación',
    'esperando_repuestos' => 'Esperando repuestos',
];

// Estados en los que el servicio ya quedó cerrado (van al historial)
const ESTADOS_CERRADOS = [
    'terminado' => 'Terminado (por entregar)',
    'entregado' => 'Entregado',
    'cancelado' => 'Cancelado',
];

const TIPOS_ITEM = [
    'mano_obra' => 'Mano de obra',
    'repuesto'  => 'Repuesto / Material',
];

function estados(): array
{
    return ESTADOS_PENDIENTES + ESTADOS_CERRADOS;
}

/* ---------- Utilidades ---------- */

/** Escapa texto para mostrarlo en HTML. */
function e($valor): string
{
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
}

/** Texto con saltos de línea respetados. */
function e_multilinea($valor): string
{
    return nl2br(e($valor));
}

function redirigir(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function flash(string $mensaje, string $tipo = 'ok'): void
{
    $_SESSION['flash'][] = ['mensaje' => $mensaje, 'tipo' => $tipo];
}

function tomar_flash(): array
{
    $mensajes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $mensajes;
}

function numero_orden(int $id): string
{
    return 'OS-' . str_pad((string) $id, 5, '0', STR_PAD_LEFT);
}

function ahora(): string
{
    return date('Y-m-d H:i:s');
}

function dinero($valor): string
{
    $c = config();
    return $c['moneda'] . ' ' . number_format((float) $valor, (int) $c['decimales'], ',', '.');
}

function cantidad_fmt($valor): string
{
    $v = (float) $valor;
    return floor($v) == $v ? (string) (int) $v : number_format($v, 2, ',', '.');
}

function fecha_fmt(?string $fecha, bool $con_hora = true): string
{
    if (!$fecha) {
        return '—';
    }
    $t = strtotime($fecha);
    if ($t === false) {
        return '—';
    }
    return $con_hora ? date('d/m/Y h:i a', $t) : date('d/m/Y', $t);
}

/** "hace 2 días", "hace 3 horas"... */
function tiempo_transcurrido(?string $fecha): string
{
    if (!$fecha) {
        return '';
    }
    $seg = time() - strtotime($fecha);
    if ($seg < 60) {
        return 'hace un momento';
    }
    $min = intdiv($seg, 60);
    if ($min < 60) {
        return "hace $min min";
    }
    $horas = intdiv($min, 60);
    if ($horas < 24) {
        return "hace $horas " . ($horas === 1 ? 'hora' : 'horas');
    }
    $dias = intdiv($horas, 24);
    return "hace $dias " . ($dias === 1 ? 'día' : 'días');
}

function etiqueta(array $catalogo, $clave): string
{
    return $catalogo[$clave] ?? (string) $clave;
}

function badge_prioridad(int $p): string
{
    return '<span class="badge prioridad-' . $p . '">' . e(etiqueta(PRIORIDADES, $p)) . '</span>';
}

function badge_estado(string $estado): string
{
    return '<span class="badge estado-' . e($estado) . '">' . e(etiqueta(estados(), $estado)) . '</span>';
}

/** Valor de texto desde POST, recortado; null si viene vacío. */
function post_texto(string $campo, int $max = 5000): ?string
{
    $v = trim((string) ($_POST[$campo] ?? ''));
    if ($v === '') {
        return null;
    }
    return mb_substr($v, 0, $max);
}

/* ---------- Consultas comunes ---------- */

function obtener_servicio(int $id): ?array
{
    $st = db()->prepare('SELECT * FROM servicios WHERE id = ?');
    $st->execute([$id]);
    $fila = $st->fetch();
    return $fila ?: null;
}

function obtener_items(int $servicio_id): array
{
    $st = db()->prepare('SELECT * FROM servicio_items WHERE servicio_id = ? ORDER BY tipo, id');
    $st->execute([$servicio_id]);
    return $st->fetchAll();
}

/** Calcula subtotal de mano de obra, repuestos, IVA y total. */
function totales(array $items): array
{
    $mano_obra = 0.0;
    $repuestos = 0.0;
    foreach ($items as $it) {
        $linea = (float) $it['cantidad'] * (float) $it['precio_unitario'];
        if ($it['tipo'] === 'mano_obra') {
            $mano_obra += $linea;
        } else {
            $repuestos += $linea;
        }
    }
    $subtotal = $mano_obra + $repuestos;
    $iva_pct = (float) config()['iva'];
    $iva = round($subtotal * $iva_pct / 100, (int) config()['decimales']);
    return [
        'mano_obra' => $mano_obra,
        'repuestos' => $repuestos,
        'subtotal'  => $subtotal,
        'iva_pct'   => $iva_pct,
        'iva'       => $iva,
        'total'     => $subtotal + $iva,
    ];
}

function contar_pendientes(): int
{
    $marcas = implode(',', array_fill(0, count(ESTADOS_PENDIENTES), '?'));
    $st = db()->prepare("SELECT COUNT(*) FROM servicios WHERE estado IN ($marcas)");
    $st->execute(array_keys(ESTADOS_PENDIENTES));
    return (int) $st->fetchColumn();
}

function vehiculo_resumen(array $s): string
{
    $partes = array_filter([
        etiqueta(TIPOS_VEHICULO, $s['tipo_vehiculo']),
        trim(($s['marca'] ?? '') . ' ' . ($s['modelo'] ?? '')),
    ]);
    return implode(' · ', $partes);
}
