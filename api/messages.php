<?php
require_once __DIR__ . '/bootstrap.php';

// Usuario autenticado para validar permisos en chat
$authUserId = require_login();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $peerId = (int) ($_GET['user_id'] ?? 0);
    $lastId = (int) ($_GET['last_id'] ?? 0);
    if ($peerId <= 0) {
        json_response(['ok' => false, 'message' => 'Destinatario invalido'], 422);
    }

    $checkPeer = $conn->prepare('SELECT id, nombre_usuario, nombre_completo FROM cuentas WHERE id = ? LIMIT 1');
    $checkPeer->bind_param('i', $peerId);
    $checkPeer->execute();
    $peer = $checkPeer->get_result()->fetch_assoc();
    $checkPeer->close();

    if (!$peer) {
        json_response(['ok' => false, 'message' => 'El usuario destino no existe'], 404);
    }

    $whereClause = 'WHERE ((remitente_id = ? AND destinatario_id = ?) OR (remitente_id = ? AND destinatario_id = ?))';
    $params = [$authUserId, $peerId, $peerId, $authUserId];
    $types = 'iiii';

    if ($lastId > 0) {
        $whereClause .= ' AND id > ?';
        $params[] = $lastId;
        $types .= 'i';
    }

    $stmt = $conn->prepare(
        'SELECT id, remitente_id, destinatario_id, contenido, enviado_en,
                TIMESTAMPDIFF(SECOND, enviado_en, NOW()) AS age_seconds
         FROM chat_mensajes ' . $whereClause . '
         ORDER BY enviado_en ASC, id ASC'
    );
    $stmt->bind_param($types, ...$params);
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
        'messages' => array_map(static function ($m) use ($authUserId) {
            return [
                'id' => (int) $m['id'],
                'sender_id' => (int) $m['remitente_id'],
                'receiver_id' => (int) $m['destinatario_id'],
                'content' => $m['contenido'],
                'sent_at' => $m['enviado_en'],
                // Solo el remitente puede editar dentro de los primeros 10 segundos
                'can_edit' => ((int) $m['remitente_id'] === $authUserId) && ((int) $m['age_seconds'] <= 10)
            ];
        }, $messages)
    ]);
}

if ($method === 'POST') {
    $action = $_POST['action'] ?? 'send';

    // Ruta de edicion de mensaje
    if ($action === 'edit') {
        $messageId = (int) ($_POST['message_id'] ?? 0);
        $newContent = trim($_POST['content'] ?? '');

        if ($messageId <= 0 || $newContent === '') {
            json_response(['ok' => false, 'message' => 'Mensaje y contenido son requeridos'], 422);
        }

        if (mb_strlen($newContent) > 1000) {
            json_response(['ok' => false, 'message' => 'El mensaje excede 1000 caracteres'], 422);
        }

        $checkMsg = $conn->prepare(
            'SELECT id
             FROM chat_mensajes
             WHERE id = ?
               AND remitente_id = ?
               AND TIMESTAMPDIFF(SECOND, enviado_en, NOW()) <= 10
             LIMIT 1'
        );
        $checkMsg->bind_param('ii', $messageId, $authUserId);
        $checkMsg->execute();
        $editable = $checkMsg->get_result()->fetch_assoc();
        $checkMsg->close();

        if (!$editable) {
            json_response(['ok' => false, 'message' => 'Solo puedes editar durante 10 segundos despues de enviar'], 403);
        }

        $update = $conn->prepare('UPDATE chat_mensajes SET contenido = ? WHERE id = ?');
        $update->bind_param('si', $newContent, $messageId);
        if (!$update->execute()) {
            $update->close();
            json_response(['ok' => false, 'message' => 'No se pudo editar el mensaje'], 500);
        }
        $update->close();

        json_response([
            'ok' => true,
            'message' => 'Mensaje editado',
            'data' => [
                'id' => $messageId,
                'content' => $newContent
            ]
        ]);
    }

    // Ruta de envio de mensaje
    $peerId = (int) ($_POST['user_id'] ?? 0);
    $content = trim($_POST['content'] ?? '');

    if ($peerId <= 0 || $content === '') {
        json_response(['ok' => false, 'message' => 'Usuario y mensaje son requeridos'], 422);
    }

    if (mb_strlen($content) > 1000) {
        json_response(['ok' => false, 'message' => 'El mensaje excede 1000 caracteres'], 422);
    }

    $checkPeer = $conn->prepare('SELECT id FROM cuentas WHERE id = ? LIMIT 1');
    $checkPeer->bind_param('i', $peerId);
    $checkPeer->execute();
    $peerExists = $checkPeer->get_result()->fetch_assoc();
    $checkPeer->close();

    if (!$peerExists) {
        json_response(['ok' => false, 'message' => 'No puedes enviar mensajes a cuentas inexistentes'], 404);
    }

    $insert = $conn->prepare('INSERT INTO chat_mensajes (remitente_id, destinatario_id, contenido) VALUES (?, ?, ?)');
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
