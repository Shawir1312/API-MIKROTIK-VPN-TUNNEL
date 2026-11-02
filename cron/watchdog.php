<?php
/**
 * VPN Tunnel & Remote RouterOS Gateway
 * Background CLI Cron Watchdog: Ping Router Tunnels & Health Check
 * 
 * Pasang di crontab Linux / aaPanel:
 * * * * * * php /path/to/cron/watchdog.php > /dev/null 2>&1
 */

if (php_sapi_name() !== 'cli') {
    die("Akses watchdog hanya diperbolehkan melalui CLI Terminal / Cron.\n");
}

define('IN_APP', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../include/functions.php';

echo "=====================================================\n";
echo " MIKROTIK VPN TUNNEL — Router Watchdog & Health Check\n";
echo " Waktu: " . date('Y-m-d H:i:s') . "\n";
echo "=====================================================\n";

$tunnels = db_fetch_all("SELECT * FROM tunnels");
$onlineCount = 0;
$offlineCount = 0;

foreach ($tunnels as $t) {
    $ip = $t['tunnel_ip'];
    $isOnline = false;

    // Uji soket TCP ke port Winbox 8291 atau API 8728
    $portsToTest = [8291, 8728, 80, 22];
    foreach ($portsToTest as $p) {
        $fp = @fsockopen($ip, $p, $errno, $errstr, 1);
        if ($fp) {
            $isOnline = true;
            fclose($fp);
            break;
        }
    }

    $newStatus = $isOnline ? 'active' : 'offline';
    $lastSeenSql = $isOnline ? ", last_seen = NOW()" : "";

    db_execute("UPDATE tunnels SET status = ? {$lastSeenSql} WHERE id = ?", 'si', [$newStatus, $t['id']]);

    if ($isOnline) {
        $onlineCount++;
        echo " [+] {$t['client_name']} ({$ip}): ONLINE\n";
    } else {
        $offlineCount++;
        echo " [-] {$t['client_name']} ({$ip}): OFFLINE\n";
    }
}

// Periksa tunnel yang kadaluarsa (expired)
$expired = db_fetch_all("SELECT id, client_name FROM tunnels WHERE expired_at IS NOT NULL AND expired_at < NOW() AND status != 'expired'");
foreach ($expired as $exp) {
    db_execute("UPDATE tunnels SET status = 'expired' WHERE id = ?", 'i', [$exp['id']]);
    db_execute("UPDATE ports SET is_active = 0 WHERE tunnel_id = ?", 'i', [$exp['id']]);
    echo " [!] Tunnel #{$exp['id']} ({$exp['client_name']}) telah kadaluarsa. Port dinonaktifkan.\n";
}

echo "-----------------------------------------------------\n";
echo " Selesai: {$onlineCount} Online, {$offlineCount} Offline.\n\n";

