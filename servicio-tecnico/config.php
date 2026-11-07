<?php
/*
 * CONFIGURACIÓN DEL SISTEMA
 * Cambia aquí los datos de tu taller y de la base de datos.
 */
return [
    // ---- Base de datos ----
    // Con XAMPP normalmente el usuario es "root" y la contraseña va vacía.
    // La base de datos y las tablas se crean solas la primera vez.
    'db' => [
        'driver'      => getenv('ST_DB_DRIVER') ?: 'mysql',   // 'mysql' o 'sqlite'
        // (Con Docker estos valores llegan desde docker-compose.yml)
        'host'        => getenv('ST_DB_HOST') ?: '127.0.0.1',
        'port'        => (int) (getenv('ST_DB_PORT') ?: 3306),
        'nombre'      => getenv('ST_DB_NOMBRE') ?: 'servicio_tecnico',
        'usuario'     => getenv('ST_DB_USUARIO') ?: 'root',
        'clave'       => getenv('ST_DB_CLAVE') !== false ? getenv('ST_DB_CLAVE') : '',
        // Solo se usa si driver = 'sqlite' (no necesita XAMPP/MySQL)
        'sqlite_file' => getenv('ST_SQLITE_FILE') ?: __DIR__ . '/datos/servicio_tecnico.sqlite',
    ],

    // ---- Datos del taller (salen en la orden impresa) ----
    'taller' => [
        'nombre'    => 'Taller de Movilidad Eléctrica',
        'nit'       => 'NIT 000.000.000-0',
        'direccion' => 'Calle 00 # 00-00, Ciudad',
        'telefono'  => '300 000 0000',
        'email'     => 'contacto@mitaller.com',
        // Texto al pie de la orden
        'nota_orden' => 'Gracias por confiar en nosotros. Conserve esta orden para hacer válida la garantía.',
    ],

    // ---- Formato ----
    'moneda'       => '$',
    'decimales'    => 0,              // 0 para pesos colombianos
    'iva'          => 0,              // Porcentaje de IVA (ej: 19). 0 = no se cobra IVA
    'zona_horaria' => 'America/Bogota',
];
