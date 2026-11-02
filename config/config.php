<?php
/**
 * VPN Tunnel & Remote RouterOS Gateway — Global App Config
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('IN_APP', true);
define('BASE_PATH', dirname(__DIR__));

require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/include/auth.php';
require_once BASE_PATH . '/include/functions.php';
require_once BASE_PATH . '/include/RouterosAPI.php';
require_once BASE_PATH . '/include/VpnManager.php';
require_once BASE_PATH . '/include/NatManager.php';

// Load settings dari DB
function get_app_setting(string $key, string $default = ''): string {
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        try {
            $rows = db_fetch_all("SELECT `key`, `value` FROM settings");
            foreach ($rows as $r) {
                $cache[$r['key']] = $r['value'];
            }
        } catch (Throwable $e) {}
    }
    return $cache[$key] ?? $default;
}

define('APP_NAME', get_app_setting('app_name', 'VPN Tunnel & Remote RouterOS'));
define('APP_VERSION', '1.0.0');

