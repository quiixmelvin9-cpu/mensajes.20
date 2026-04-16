<?php
require_once __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'message' => 'Metodo no permitido'], 405);
}

$username = trim($_POST['username'] ?? '');
$fullname = trim($_POST['fullname'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($username === '' || $fullname === '' || $password === '') {
    json_response(['ok' => false, 'message' => 'Usuario, nombre y contrasena son obligatorios'], 422);
}

if (strlen($username) < 3 || strlen($username) > 30) {
    json_response(['ok' => false, 'message' => 'El usuario debe tener entre 3 y 30 caracteres'], 422);
}

if (strlen($password) < 6) {
    json_response(['ok' => false, 'message' => 'La contrasena debe tener al menos 6 caracteres'], 422);
}

$check = $conn->prepare('SELECT id FROM usuarios WHERE nombre_usuario = ? OR email = ? LIMIT 1');
$check->bind_param('ss', $username, $email);
$check->execute();
$exists = $check->get_result()->fetch_assoc();
$check->close();

if ($exists) {
    json_response(['ok' => false, 'message' => 'El usuario o correo ya existe'], 409);
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);
$insert = $conn->prepare('INSERT INTO usuarios (nombre_usuario, nombre_completo, email, password_hash) VALUES (?, ?, ?, ?)');
$insert->bind_param('ssss', $username, $fullname, $email, $passwordHash);

if (!$insert->execute()) {
    $insert->close();
    json_response(['ok' => false, 'message' => 'No se pudo registrar el usuario'], 500);
}

$newId = $insert->insert_id;
$insert->close();

$_SESSION['user_id'] = $newId;
$_SESSION['username'] = $username;

json_response([
    'ok' => true,
    'message' => 'Cuenta creada con exito',
    'user' => [
        'id' => $newId,
        'username' => $username,
        'fullname' => $fullname
    ]
]);
