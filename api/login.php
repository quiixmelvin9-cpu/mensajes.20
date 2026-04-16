<?php
require_once __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'message' => 'Metodo no permitido'], 405);
}

$identity = trim($_POST['identity'] ?? '');
$password = $_POST['password'] ?? '';

if ($identity === '' || $password === '') {
    json_response(['ok' => false, 'message' => 'Completa usuario/correo y contrasena'], 422);
}

$stmt = $conn->prepare('SELECT id, nombre_usuario, nombre_completo, password_hash FROM usuarios WHERE nombre_usuario = ? OR email = ? LIMIT 1');
$stmt->bind_param('ss', $identity, $identity);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user || !password_verify($password, $user['password_hash'])) {
    json_response(['ok' => false, 'message' => 'Credenciales invalidas'], 401);
}

$_SESSION['user_id'] = (int) $user['id'];
$_SESSION['username'] = $user['nombre_usuario'];

json_response([
    'ok' => true,
    'message' => 'Sesion iniciada',
    'user' => [
        'id' => (int) $user['id'],
        'username' => $user['nombre_usuario'],
        'fullname' => $user['nombre_completo']
    ]
]);
