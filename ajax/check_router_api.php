<?php
/**
 * VPN Tunnel & Remote RouterOS Gateway
 * AJAX: Check RouterOS API Connection over VPN Tunnel
 */

define('IN_APP', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../include/auth.php';
require_once __DIR__ . '/../include/functions.php';
require_once __DIR__ . '/../include/RouterosAPI.php';

auth_require_login();

header('Content-Type: application/json; charset=utf-8');

$tunnelId = (int)post('tunnel_id', 0);
$apiPort  = (int)post('api_port', 8728);
$apiUser  = trim(post('api_user', 'admin'));
$apiPass  = trim(post('api_pass', ''));

if (!$tunnelId) {
    echo json_encode(['success' => false, 'message' => 'Tunnel ID tidak valid']);
    exit;
}

$user = current_user();
$tunnel = db_fetch_one("SELECT * FROM tunnels WHERE id = ?", 'i', [$tunnelId]);
if (!$tunnel || (!is_admin() && (int)$tunnel['user_id'] !== (int)$user['id'])) {
    echo json_encode(['success' => false, 'message' => 'Akses ditolak atau tunnel tidak ditemukan']);
    exit;
}

$tunnelIp = $tunnel['tunnel_ip'];

$start = microtime(true);
$api = new RouterosAPI();
$api->timeout = 5;
$api->attempts = 1;

if ($api->connect($tunnelIp, $apiUser, $apiPass, $apiPort)) {
    $latency = round((microtime(true) - $start) * 1000, 1);
    $resource = $api->comm('/system/resource/print');
    $api->disconnect();

    $res = $resource[0] ?? [];
    echo json_encode([
        'success'  => true,
        'message'  => 'Berhasil terhubung ke MikroTik RouterOS API',
        'latency'  => $latency,
        'resource' => $res
    ]);
} else {
    $latency = round((microtime(true) - $start) * 1000, 1);
    echo json_encode([
        'success' => false,
        'message' => "Gagal terhubung ke {$tunnelIp}:{$apiPort}. Pastikan service /ip service api di MikroTik aktif dan tunnel terhubung.",
        'latency' => $latency
    ]);
}

