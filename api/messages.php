<?php
require_once __DIR__ . '/bootstrap.php';

$authUserId = require_login();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $peerId = (int) ($_GET['user_id'] ?? 0);
    if ($peerId <= 0) {
        json_response(['ok' => false, 'message' => 'Destinatario invalido'], 422);
    }

    $checkPeer = $conn->prepare('SELECT id, nombre_usuario, nombre_completo FROM usuarios WHERE id = ? LIMIT 1');
    $checkPeer->bind_param('i', $peerId);
    $checkPeer->execute();
    $peer = $checkPeer->get_result()->fetch_assoc();
    $checkPeer->close();

    if (!$peer) {
        json_response(['ok' => false, 'message' => 'El usuario destino no existe'], 404);
    }

    $stmt = $conn->prepare(
        'SELECT id, remitente_id, destinatario_id, contenido, enviado_en
         FROM mensajes
         WHERE (remitente_id = ? AND destinatario_id = ?) OR (remitente_id = ? AND destinatario_id = ?)
         ORDER BY enviado_en ASC, id ASC'
    );
    $stmt->bind_param('iiii', $authUserId, $peerId, $peerId, $authUserId);
    $stmt->execute();
    $messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    json_response([
        'ok' => true,
        'peer' => [
            'id' => (int) $peer['id'],
            'username' => $peer['nombre_usuario'],
            'fullname' => $peer['nombre_completo']
        ],
        'messages' => array_map(static function ($m) {
            return [
                'id' => (int) $m['id'],
                'sender_id' => (int) $m['remitente_id'],
                'receiver_id' => (int) $m['destinatario_id'],
                'content' => $m['contenido'],
                'sent_at' => $m['enviado_en']
            ];
        }, $messages)
    ]);
}

if ($method === 'POST') {
    $peerId = (int) ($_POST['user_id'] ?? 0);
    $content = trim($_POST['content'] ?? '');

    if ($peerId <= 0 || $content === '') {
        json_response(['ok' => false, 'message' => 'Usuario y mensaje son requeridos'], 422);
    }

    if (mb_strlen($content) > 1000) {
        json_response(['ok' => false, 'message' => 'El mensaje excede 1000 caracteres'], 422);
    }

    $checkPeer = $conn->prepare('SELECT id FROM usuarios WHERE id = ? LIMIT 1');
    $checkPeer->bind_param('i', $peerId);
    $checkPeer->execute();
    $peerExists = $checkPeer->get_result()->fetch_assoc();
    $checkPeer->close();

    if (!$peerExists) {
        json_response(['ok' => false, 'message' => 'No puedes enviar mensajes a cuentas inexistentes'], 404);
    }

    $insert = $conn->prepare('INSERT INTO mensajes (remitente_id, destinatario_id, contenido) VALUES (?, ?, ?)');
    $insert->bind_param('iis', $authUserId, $peerId, $content);

    if (!$insert->execute()) {
        $insert->close();
        json_response(['ok' => false, 'message' => 'No se pudo enviar el mensaje'], 500);
    }

    $messageId = $insert->insert_id;
    $insert->close();

    json_response([
        'ok' => true,
        'message' => 'Mensaje enviado',
        'data' => [
            'id' => $messageId,
            'sender_id' => $authUserId,
            'receiver_id' => $peerId,
            'content' => $content
        ]
    ]);
}

json_response(['ok' => false, 'message' => 'Metodo no permitido'], 405);
