<?php
// Recupera datos de sesion y lista de contactos
require_once __DIR__ . '/bootstrap.php';

$userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;

if ($userId <= 0) {
    json_response(['ok' => false, 'authenticated' => false]);
}

$userStmt = $conn->prepare('SELECT id, nombre_usuario, nombre_completo, foto_perfil FROM cuentas WHERE id = ? LIMIT 1');
$userStmt->bind_param('i', $userId);
$userStmt->execute();
$currentUser = $userStmt->get_result()->fetch_assoc();
$userStmt->close();

$listStmt = $conn->prepare('SELECT id, nombre_usuario, nombre_completo, foto_perfil FROM cuentas WHERE id <> ? ORDER BY nombre_usuario ASC');
$listStmt->bind_param('i', $userId);
$listStmt->execute();
$users = $listStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$listStmt->close();

json_response([
    'ok' => true,
    'authenticated' => true,
    'user' => [
        'id' => (int) $currentUser['id'],
        'username' => $currentUser['nombre_usuario'],
        'fullname' => $currentUser['nombre_completo'],
        'photo_url' => public_photo_url($currentUser['foto_perfil'] ?? null)
    ],
    'users' => array_map(static function ($u) {
        return [
            'id' => (int) $u['id'],
            'username' => $u['nombre_usuario'],
            'fullname' => $u['nombre_completo'],
            'photo_url' => public_photo_url($u['foto_perfil'] ?? null)
        ];
    }, $users)
]);

