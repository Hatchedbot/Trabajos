<?php
/*
 * Conexión a la base de datos.
 * La base de datos y las tablas se crean automáticamente si no existen.
 */

function db(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $c = config()['db'];
    $opciones = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        if ($c['driver'] === 'sqlite') {
            $dir = dirname($c['sqlite_file']);
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
            $pdo = new PDO('sqlite:' . $c['sqlite_file'], null, null, $opciones);
            $pdo->exec('PRAGMA foreign_keys = ON');
        } else {
            $dsn = "mysql:host={$c['host']};port={$c['port']};charset=utf8mb4";
            $pdo = new PDO($dsn, $c['usuario'], $c['clave'], $opciones);
            $nombre = str_replace('`', '', $c['nombre']);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$nombre` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `$nombre`");
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo '<h2 style="font-family:sans-serif">No se pudo conectar a la base de datos</h2>';
        echo '<p style="font-family:sans-serif">Verifica que MySQL esté encendido en XAMPP y que los datos de <code>config.php</code> sean correctos.</p>';
        echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
        exit;
    }

    crear_tablas($pdo, $c['driver']);
    return $pdo;
}

function crear_tablas(PDO $pdo, string $driver): void
{
    if ($driver === 'sqlite') {
        $pdo->exec("CREATE TABLE IF NOT EXISTS servicios (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            cliente_nombre TEXT NOT NULL,
            cliente_documento TEXT NULL,
            cliente_telefono TEXT NOT NULL,
            cliente_email TEXT NULL,
            tipo_vehiculo TEXT NOT NULL,
            marca TEXT NULL,
            modelo TEXT NULL,
            placa_serial TEXT NULL,
            color TEXT NULL,
            kilometraje TEXT NULL,
            nivel_bateria TEXT NULL,
            motivo TEXT NOT NULL,
            descripcion_problema TEXT NOT NULL,
            accesorios TEXT NULL,
            prioridad INTEGER NOT NULL DEFAULT 3,
            estado TEXT NOT NULL DEFAULT 'recibido',
            tecnico TEXT NULL,
            fecha_ingreso TEXT NOT NULL,
            fecha_estimada TEXT NULL,
            fecha_terminado TEXT NULL,
            fecha_entrega TEXT NULL,
            diagnostico TEXT NULL,
            trabajo_realizado TEXT NULL,
            recomendaciones TEXT NULL,
            garantia_dias INTEGER NOT NULL DEFAULT 0
        )");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_servicios_estado ON servicios(estado)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_servicios_placa ON servicios(placa_serial)");
        $pdo->exec("CREATE TABLE IF NOT EXISTS servicio_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            servicio_id INTEGER NOT NULL REFERENCES servicios(id) ON DELETE CASCADE,
            tipo TEXT NOT NULL,
            descripcion TEXT NOT NULL,
            cantidad REAL NOT NULL DEFAULT 1,
            precio_unitario REAL NOT NULL DEFAULT 0
        )");
        return;
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS servicios (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        cliente_nombre VARCHAR(120) NOT NULL,
        cliente_documento VARCHAR(30) NULL,
        cliente_telefono VARCHAR(30) NOT NULL,
        cliente_email VARCHAR(120) NULL,
        tipo_vehiculo VARCHAR(20) NOT NULL,
        marca VARCHAR(60) NULL,
        modelo VARCHAR(60) NULL,
        placa_serial VARCHAR(60) NULL,
        color VARCHAR(30) NULL,
        kilometraje VARCHAR(20) NULL,
        nivel_bateria VARCHAR(20) NULL,
        motivo VARCHAR(30) NOT NULL,
        descripcion_problema TEXT NOT NULL,
        accesorios TEXT NULL,
        prioridad TINYINT NOT NULL DEFAULT 3,
        estado VARCHAR(30) NOT NULL DEFAULT 'recibido',
        tecnico VARCHAR(80) NULL,
        fecha_ingreso DATETIME NOT NULL,
        fecha_estimada DATE NULL,
        fecha_terminado DATETIME NULL,
        fecha_entrega DATETIME NULL,
        diagnostico TEXT NULL,
        trabajo_realizado TEXT NULL,
        recomendaciones TEXT NULL,
        garantia_dias INT NOT NULL DEFAULT 0,
        INDEX idx_servicios_estado (estado),
        INDEX idx_servicios_placa (placa_serial)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS servicio_items (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        servicio_id INT UNSIGNED NOT NULL,
        tipo VARCHAR(20) NOT NULL,
        descripcion VARCHAR(200) NOT NULL,
        cantidad DECIMAL(10,2) NOT NULL DEFAULT 1,
        precio_unitario DECIMAL(12,2) NOT NULL DEFAULT 0,
        CONSTRAINT fk_items_servicio FOREIGN KEY (servicio_id)
            REFERENCES servicios(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}
