<?php
/**
 * VPN Tunnel & Remote RouterOS Gateway
 * Process Controller: Port Action (DST-NAT Forwarding CRUD)
 */

define('IN_APP', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../include/auth.php';
require_once __DIR__ . '/../include/functions.php';
require_once __DIR__ . '/../include/NatManager.php';

auth_require_login();
$user = current_user();
$userId = (int)$user['id'];
$isAdmin = is_admin();

$action = post('action', get('action', ''));

switch ($action) {
    case 'create':
        $tunnelId   = (int)post('tunnel_id', 0);
        $label      = trim(post('label', ''));
        $publicPort = (int)post('public_port', 0);
        $targetIp   = trim(post('target_ip', ''));
        $targetPort = (int)post('target_port', 0);
        $protocol   = strtolower(trim(post('protocol', 'tcp')));
        $description= trim(post('description', ''));

        if (!$tunnelId || !$publicPort || !$targetIp || !$targetPort) {
            flash_set('danger', 'Semua bidang bertanda bintang (*) wajib diisi.');
            header("Location: ../index.php?page=ports&action=add");
            exit;
        }

        if (!in_array($protocol, ['tcp', 'udp', 'both'])) {
            $protocol = 'tcp';
        }

        // Validasi kepemilikan tunnel jika bukan admin
        $tunnel = db_fetch_one("SELECT t.*, s.public_ip, s.gateway_type, s.api_user, s.api_pass, s.api_port, s.wg_interface 
                                FROM tunnels t 
                                JOIN servers s ON t.server_id = s.id 
                                WHERE t.id = ?", 'i', [$tunnelId]);
        if (!$tunnel || (!$isAdmin && (int)$tunnel['user_id'] !== $userId)) {
            flash_set('danger', 'Router tunnel tidak ditemukan atau Anda tidak memiliki akses.');
            header("Location: ../index.php?page=ports");
            exit;
        }

        // Validasi keunikan public port
        $exists = db_fetch_one("SELECT id FROM ports WHERE public_port = ? AND (protocol = ? OR protocol = 'both' OR ? = 'both')", 
                                'iss', [$publicPort, $protocol, $protocol]);
        if ($exists) {
            flash_set('danger', "Port publik {$publicPort} ({$protocol}) sudah digunakan oleh mapping lain. Pilih port lain.");
            header("Location: ../index.php?page=ports&action=add&tunnel_id={$tunnelId}");
            exit;
        }

        $serverId = (int)$tunnel['server_id'];

        $portId = db_insert(
            "INSERT INTO ports (server_id, tunnel_id, label, public_port, target_ip, target_port, protocol, is_active, description) 
             VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?)",
            'iisisiss',
            [$serverId, $tunnelId, $label, $publicPort, $targetIp, $targetPort, $protocol, $description]
        );

        // Eksekusi firewall rule DST-NAT via NatManager
        $server = db_fetch_one("SELECT * FROM servers WHERE id = ?", 'i', [$serverId]);
        $portRow = db_fetch_one("SELECT * FROM ports WHERE id = ?", 'i', [$portId]);
        if ($server && $portRow) {
            NatManager::applyPortRule($server, $portRow);
        }

        flash_set('success', "Port Forwarding berhasil dibuka: {$tunnel['public_ip']}:{$publicPort} &rarr; {$targetIp}:{$targetPort} ({$protocol})");
        header("Location: ../index.php?page=ports");
        exit;

    case 'delete':
        $id = (int)post('id', get('id', 0));
        $port = db_fetch_one("SELECT p.*, t.user_id, s.id as server_id 
                              FROM ports p 
                              JOIN tunnels t ON p.tunnel_id = t.id 
                              JOIN servers s ON p.server_id = s.id 
                              WHERE p.id = ?", 'i', [$id]);

        if (!$port || (!$isAdmin && (int)$port['user_id'] !== $userId)) {
            flash_set('danger', 'Mapping port tidak ditemukan.');
            header("Location: ../index.php?page=ports");
            exit;
        }

        // Hapus rule dari gateway firewall
        $server = db_fetch_one("SELECT * FROM servers WHERE id = ?", 'i', [(int)$port['server_id']]);
        if ($server) {
            NatManager::removePortRule($server, $port);
        }

        db_execute("DELETE FROM ports WHERE id = ?", 'i', [$id]);
        flash_set('success', "Port Forwarding :{$port['public_port']} berhasil dihapus.");
        header("Location: ../index.php?page=ports");
        exit;

    case 'toggle':
        $id = (int)post('id', get('id', 0));
        $port = db_fetch_one("SELECT p.*, t.user_id, s.id as server_id 
                              FROM ports p 
                              JOIN tunnels t ON p.tunnel_id = t.id 
                              JOIN servers s ON p.server_id = s.id 
                              WHERE p.id = ?", 'i', [$id]);

        if (!$port || (!$isAdmin && (int)$port['user_id'] !== $userId)) {
            flash_set('danger', 'Akses ditolak.');
            header("Location: ../index.php?page=ports");
            exit;
        }

        $newState = ($port['is_active'] == 1) ? 0 : 1;
        db_execute("UPDATE ports SET is_active = ? WHERE id = ?", 'ii', [$newState, $id]);

        $server = db_fetch_one("SELECT * FROM servers WHERE id = ?", 'i', [(int)$port['server_id']]);
        $port['is_active'] = $newState;
        if ($server) {
            if ($newState == 1) {
                NatManager::applyPortRule($server, $port);
            } else {
                NatManager::removePortRule($server, $port);
            }
        }

        $statusMsg = $newState ? 'diaktifkan' : 'dinonaktifkan';
        flash_set('success', "Port Forwarding :{$port['public_port']} berhasil {$statusMsg}.");
        header("Location: ../index.php?page=ports");
        exit;

    case 'sync_all':
        auth_require_admin();
        $servers = db_fetch_all("SELECT * FROM servers WHERE is_active = 1");
        $count = 0;
        foreach ($servers as $srv) {
            $ports = db_fetch_all("SELECT * FROM ports WHERE server_id = ? AND is_active = 1", 'i', [(int)$srv['id']]);
            foreach ($ports as $prt) {
                NatManager::applyPortRule($srv, $prt);
                $count++;
            }
        }
        flash_set('success', "Berhasil menyinkronkan {$count} rule DST-NAT firewall ke semua Gateway Server.");
        header("Location: ../index.php?page=ports");
        exit;

    default:
        header("Location: ../index.php?page=ports");
        exit;
}

