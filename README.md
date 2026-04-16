# Sistema de Mensajeria (Estilo Instagram)

## Requisitos
- XAMPP con Apache y MySQL activos
- PHP 8+

## Instalacion
1. Importa `database.sql` en phpMyAdmin.
2. Verifica que los datos de conexion en `db.php` coincidan con tu entorno.
3. Abre en navegador:
   - `http://localhost/mensajes.20/`

## Estructura principal
- `index.php`: interfaz principal.
- `assets/styles.css`: estilos tipo Instagram.
- `assets/app.js`: logica cliente (registro, login, mensajes).
- `db.php`: conexion MySQL.
- `api/*.php`: endpoints para autenticar y mensajeria.
- `database.sql`: tablas `usuarios` y `mensajes`.
