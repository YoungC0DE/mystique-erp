<?php

/**
 * Servidor mock de callback de etapa (desenvolvimento / demo).
 * Atualiza demo_pedidos.status e retorna JSON.
 *
 * Uso: php -S 0.0.0.0:9090 callback-server.php
 * URL no módulo (Docker): http://demo-callback:9090/
 */

declare(strict_types=1);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET' && ($_SERVER['REQUEST_URI'] ?? '/') === '/health') {
    header('Content-Type: application/json');
    echo json_encode(['ok' => true, 'service' => 'mystique-demo-callback']);
    exit;
}

if ($method !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$raw = file_get_contents('php://input') ?: '{}';
$payload = json_decode($raw, true);

if (! is_array($payload) || ! isset($payload['record_id'], $payload['status'])) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid payload']);
    exit;
}

$host = getenv('DEMO_DB_HOST') ?: 'mysql';
$port = (int) (getenv('DEMO_DB_PORT') ?: 3306);
$db = getenv('DEMO_DB_DATABASE') ?: 'mystique';
$user = getenv('DEMO_DB_USERNAME') ?: 'mystique';
$pass = getenv('DEMO_DB_PASSWORD') ?: 'secret';

try {
    $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    $stmt = $pdo->prepare('UPDATE demo_pedidos SET status = :status WHERE id = :id');
    $stmt->execute([
        'status' => (string) $payload['status'],
        'id' => (int) $payload['record_id'],
    ]);

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Record not found']);
        exit;
    }

    header('Content-Type: application/json');
    echo json_encode([
        'ok' => true,
        'record_id' => (int) $payload['record_id'],
        'status' => $payload['status'],
        'module_slug' => $payload['module_slug'] ?? null,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database error']);
}
