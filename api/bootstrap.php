<?php
// Arranque comun para endpoints API
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db.php';

// Devuelve respuesta JSON estandarizada
function json_response($data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Exige sesion iniciada
function require_login(): int
{
    if (!isset($_SESSION['user_id'])) {
        json_response(['ok' => false, 'message' => 'No autorizado'], 401);
    }

    return (int) $_SESSION['user_id'];
}

// Normaliza URL publica de foto
function public_photo_url(?string $path): ?string
{
    if (!$path) {
        return null;
    }

    return $path;
}

// Guarda foto de perfil con validaciones basicas
function store_profile_photo(array $file, string $username): string
{
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        json_response(['ok' => false, 'message' => 'Archivo de imagen invalido'], 422);
    }

    if (($file['size'] ?? 0) > 2 * 1024 * 1024) {
        json_response(['ok' => false, 'message' => 'La foto no debe superar 2MB'], 422);
    }

    $mime = mime_content_type($file['tmp_name']) ?: '';
    if ($mime === 'application/octet-stream') {
        $imgInfo = @getimagesize($file['tmp_name']);
        if ($imgInfo && isset($imgInfo['mime'])) {
            $mime = $imgInfo['mime'];
        }
    }
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp'
    ];

    if (!isset($allowed[$mime])) {
        json_response(['ok' => false, 'message' => 'Formato no permitido. Usa JPG, PNG o WEBP'], 422);
    }

    $safeUser = preg_replace('/[^a-zA-Z0-9_-]/', '', $username);
    $filename = $safeUser . '_' . bin2hex(random_bytes(6)) . '.' . $allowed[$mime];
    $targetDir = __DIR__ . '/../uploads/profiles';
    if (!is_dir($targetDir) && !mkdir($targetDir, 0777, true) && !is_dir($targetDir)) {
        json_response(['ok' => false, 'message' => 'No se pudo preparar carpeta de imagenes'], 500);
    }

    $targetPath = $targetDir . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        json_response(['ok' => false, 'message' => 'No se pudo guardar la foto de perfil'], 500);
    }

    return 'uploads/profiles/' . $filename;
}
