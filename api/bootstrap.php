<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db.php';

function json_response($data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function require_login(): int
{
    if (!isset($_SESSION['user_id'])) {
        json_response(['ok' => false, 'message' => 'No autorizado'], 401);
    }

    return (int) $_SESSION['user_id'];
}
