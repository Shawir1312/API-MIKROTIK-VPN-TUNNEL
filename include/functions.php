<?php
/**
 * VPN Tunnel & Remote RouterOS — Global Utility Functions
 */

if (!defined('IN_APP')) die('Direct access denied.');

function h(?string $str): string {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

function random_string(int $length = 16): string {
    return bin2hex(random_bytes((int)ceil($length / 2)));
}

function format_bytes($bytes, int $precision = 2): string {
    if ($bytes <= 0) return '0 B';
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $pow   = min((int)floor(log($bytes, 1024)), count($units) - 1);
    $val   = $bytes / (1024 ** $pow);
    return round($val, $precision) . ' ' . $units[$pow];
}

function flash_set(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function flash_get(): ?array {
    if (isset($_SESSION['flash'])) {
        $msg = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $msg;
    }
    return null;
}

function render_flash(): void {
    $f = flash_get();
    if ($f) {
        $icon = $f['type'] === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation';
        echo "<div class='alert alert-{$f['type']} alert-dismissible fade show border-0 rounded-3 shadow-sm mb-4' role='alert'>
                <i class='fa-solid {$icon} me-2'></i> " . htmlspecialchars($f['message']) . "
                <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
              </div>";
    }
}

/**
 * Alokasikan IP statis berikutnya dari subnet (misal 10.88.0.0/16)
 */
function get_next_tunnel_ip(int $serverId, string $subnet = '10.88.0.0/16'): string {
    // Ambil semua IP tunnel yang sudah terpakai di server ini
    $usedIps = array_column(db_fetch_all("SELECT tunnel_ip FROM tunnels WHERE server_id = ?", 'i', [$serverId]), 'tunnel_ip');
    
    // Pecah subnet dasar (misal 10.88)
    $parts = explode('.', explode('/', $subnet)[0]);
    $prefix = $parts[0] . '.' . $parts[1] . '.';

    for ($octet3 = 1; $octet3 < 255; $octet3++) {
        for ($octet4 = 2; $octet4 < 254; $octet4++) {
            $candidate = $prefix . $octet3 . '.' . $octet4;
            if (!in_array($candidate, $usedIps, true)) {
                return $candidate;
            }
        }
    }
    return '10.88.254.254';
}

/**
 * Alokasikan port publik unik yang belum terpakai di range tertentu
 */
function get_next_available_public_port(int $min = 10000, int $max = 50000): int {
    $usedPorts = array_column(db_fetch_all("SELECT public_port FROM port_forwards"), 'public_port');
    for ($p = $min; $p <= $max; $p++) {
        if (!in_array($p, $usedPorts)) {
            return $p;
        }
    }
    return rand(50001, 65000);
}

/**
 * Helper eksekusi shell yang aman
 */
function safe_shell_exec(string $cmd): ?string {
    if (!function_exists('shell_exec')) return null;
    $disabled = (string)ini_get('disable_functions');
    if ($disabled !== '') {
        $list = array_map('trim', explode(',', $disabled));
        if (in_array('shell_exec', $list, true)) return null;
    }
    try {
        $out = @shell_exec($cmd);
        return ($out !== false && $out !== null) ? (string)$out : null;
    } catch (\Throwable $e) {
        return null;
    }
}

