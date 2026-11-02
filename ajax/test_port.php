<?php
/**
 * VPN Tunnel & Remote RouterOS Gateway
 * AJAX: Test Forwarded Port Reachability (TCP Socket Test)
 */

define('IN_APP', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../include/auth.php';
require_once __DIR__ . '/../include/functions.php';
require_once __DIR__ . '/../include/NatManager.php';

auth_require_login();

header('Content-Type: application/json; charset=utf-8');

$portId = (int)post('port_id', 0);
if (!$portId) {
    echo json_encode(['success' => false, 'message' => 'Port ID tidak valid']);
    exit;
}

$port = db_fetch_one("SELECT p.*, s.public_ip FROM ports p JOIN servers s ON p.server_id = s.id WHERE p.id = ?", 'i', [$portId]);
if (!$port) {
    echo json_encode(['success' => false, 'message' => 'Mapping port tidak ditemukan']);
    exit;
}

$res = NatManager::testPortReachability($port['public_ip'], (int)$port['public_port'], 3);

echo json_encode($res);

