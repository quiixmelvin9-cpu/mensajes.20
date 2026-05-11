<?php
// Actualizacion de foto de perfil para usuario autenticado
require_once __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'message' => 'Metodo no permitido'], 405);
}

$userId = require_login();
if (empty($_FILES['photo']) || ($_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
    json_response(['ok' => false, 'message' => 'Selecciona una imagen'], 422);
}

$userStmt = $conn->prepare('SELECT nombre_usuario, foto_perfil FROM cuentas WHERE id = ? LIMIT 1');
$userStmt->bind_param('i', $userId);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();
$userStmt->close();

if (!$user) {
    json_response(['ok' => false, 'message' => 'Usuario no encontrado'], 404);
}

$newPhotoPath = store_profile_photo($_FILES['photo'], $user['nombre_usuario']);

$update = $conn->prepare('UPDATE cuentas SET foto_perfil = ? WHERE id = ?');
$update->bind_param('si', $newPhotoPath, $userId);
if (!$update->execute()) {
    $update->close();
    json_response(['ok' => false, 'message' => 'No se pudo actualizar la foto de perfil'], 500);
}
$update->close();

if (!empty($user['foto_perfil'])) {
    $oldAbs = __DIR__ . '/../' . $user['foto_perfil'];
    if (is_file($oldAbs)) {
        @unlink($oldAbs);
    }
}

json_response([
    'ok' => true,
    'message' => 'Foto de perfil actualizada',
    'photo_url' => public_photo_url($newPhotoPath)
]);

