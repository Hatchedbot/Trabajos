# Sistema de Servicio Técnico — Vehículos eléctricos

Sistema web para un taller de **carros eléctricos, motos eléctricas y scooters/patinetas**.
Hecho en **PHP + MySQL**, funciona con **XAMPP** sin instalar nada más.

## Secciones

| Sección | Para qué sirve |
|---|---|
| **Registrar servicio** | Registrar el caso de un cliente que llega: datos del cliente, del vehículo, motivo (mantenimiento, reparación, revisión, garantía), problema reportado, accesorios con los que se recibe, prioridad y fecha estimada de entrega. |
| **Pendientes** | Lista de los servicios sin terminar. Se puede ordenar **por prioridad** (Urgente → Alta → Normal → Baja, y dentro de cada una por orden de llegada), **por orden de llegada**, más recientes o por fecha de entrega. Filtros por tipo de vehículo, estado y prioridad, buscador, y cambio rápido de estado/prioridad. Marca en rojo los atrasados. Abajo muestra los terminados que faltan por entregar. |
| **Detalle del servicio** | Diagnóstico, trabajo realizado, repuestos y mano de obra con sus precios (calcula el total), técnico y garantía. Botón **"Finalizar servicio y generar orden"**. Muestra también los servicios anteriores del mismo vehículo (por placa/serie). |
| **Historial** | Todos los servicios terminados, entregados o cancelados. Buscar por número de orden, cliente, cédula, placa/serie o trabajo realizado; filtrar por tipo, estado y rango de fechas. |
| **Orden de servicio** | Hoja lista para **imprimir** (o guardar como PDF) con los datos del taller, cliente, vehículo, diagnóstico, todo lo que se le hizo, repuestos, mano de obra, total, garantía y espacio para firmas. |

## Cómo instalarlo con Docker (recomendado)

Solo necesitas **Docker Desktop** (https://www.docker.com/products/docker-desktop/).

1. Abre Docker Desktop y espera a que diga que está corriendo.
2. Abre una terminal (PowerShell) **dentro de la carpeta `servicio-tecnico`**.
3. Ejecuta:
   ```
   docker compose up -d
   ```
   La primera vez tarda unos minutos porque descarga las imágenes.
4. Abre **http://localhost:8080**

Extra: phpMyAdmin queda en **http://localhost:8081** para ver la base de datos.

| Acción | Comando |
|---|---|
| Encender | `docker compose up -d` |
| Apagar (los datos se conservan) | `docker compose down` |
| Ver errores | `docker compose logs app` |
| ⚠ Borrar TODO, incluidos los datos | `docker compose down -v` |

Los datos se guardan en un volumen de Docker (`datos_mysql`), así que no se pierden al apagar
o reiniciar el PC. Los contenedores se vuelven a encender solos cuando arranca Docker Desktop.

## Cómo instalarlo con XAMPP (alternativa)

1. Copia la carpeta `servicio-tecnico` dentro de `C:\xampp\htdocs\`.
2. Abre el panel de XAMPP y enciende **Apache** y **MySQL**.
3. Entra en el navegador a: **http://localhost/servicio-tecnico/**

Listo. La base de datos `servicio_tecnico` y sus tablas se crean solas la primera vez.
(Si prefieres crearla a mano, importa `database.sql` en phpMyAdmin.)

## Configuración

Edita `config.php` para poner:

- El **nombre, NIT, dirección, teléfono y correo del taller** (salen en la orden impresa).
- El usuario/contraseña de MySQL si no son los de XAMPP por defecto (`root` sin contraseña).
- El porcentaje de **IVA** (por defecto 0) y el símbolo de moneda.

## Flujo de trabajo

1. Llega un cliente → **Registrar servicio** → se le asigna un número de orden (`OS-00001`).
2. El servicio aparece en **Pendientes**; ahí se ve qué atender primero y se le cambia el estado
   (Recibido → En diagnóstico → En reparación → Esperando repuestos).
3. En el detalle se anotan el diagnóstico, los repuestos y la mano de obra.
4. Al terminar → **Finalizar servicio y generar orden** → se abre la orden para imprimir.
5. Cuando el cliente recoge el vehículo → **Marcar como entregado**.
6. Todo queda en el **Historial**.

## Requisitos

- PHP 8.1 o superior (XAMPP 8.2 sirve) con PDO MySQL.
- MySQL o MariaDB.

> Alternativa sin MySQL: en `config.php` cambia `'driver' => 'mysql'` por `'sqlite'`
> y los datos se guardan en el archivo `datos/servicio_tecnico.sqlite`.

## Archivos

```
servicio-tecnico/
├── config.php          Configuración (taller, base de datos, IVA)
├── index.php           Redirige a Pendientes
├── registrar.php       Registrar / editar servicio
├── pendientes.php      Lista de pendientes con prioridad
├── servicio.php        Detalle, trabajo, repuestos, finalizar
├── historial.php       Historial de servicios
├── orden.php           Orden imprimible
├── database.sql        Script SQL (opcional)
├── Dockerfile          Imagen PHP + Apache
├── docker-compose.yml  App + MariaDB + phpMyAdmin
├── includes/           Conexión, funciones, encabezado y pie
└── assets/estilos.css  Estilos (incluye estilos de impresión)
```
