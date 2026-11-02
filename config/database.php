<?php
/**
 * VPN Tunnel & Remote RouterOS Gateway — Database Connection
 * 100% Full Pure PHP
 */

if (!defined('IN_APP') && !defined('IN_INSTALLER')) {
    define('IN_APP', true);
}

// Redirect ke installer HANYA jika aplikasi benar-benar belum diinstall
$baseDir = dirname(__DIR__);
$installedLock = $baseDir . '/config/.installed';
$dbLocal = $baseDir . '/config/db_local.php';

// Jika db_local.php ada tapi .installed belum terbuat (misal karena permission saat install), auto-touch .installed
if (!file_exists($installedLock) && file_exists($dbLocal)) {
    @file_put_contents($installedLock, date('Y-m-d H:i:s'));
}

if (!file_exists($installedLock) && !file_exists($dbLocal) && !defined('IN_INSTALLER')) {
    header("Location: install.php");
    exit;
}

// Load local database configuration jika ada
if (file_exists(__DIR__ . '/db_local.php')) {
    require_once __DIR__ . '/db_local.php';
}

if (!defined('DB_HOST'))    define('DB_HOST',    getenv('DB_HOST')    ?: '127.0.0.1');
if (!defined('DB_USER'))    define('DB_USER',    getenv('DB_USER')    ?: 'root');
if (!defined('DB_PASS'))    define('DB_PASS',    getenv('DB_PASS')    ?: '');
if (!defined('DB_NAME'))    define('DB_NAME',    getenv('DB_NAME')    ?: 'vpn_tunnel');
if (!defined('DB_PORT'))    define('DB_PORT',    (int)(getenv('DB_PORT') ?: 3306));
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');

// Singleton MySQLi connection
function db(): mysqli {
    static $conn = null;
    if ($conn === null) {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        try {
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
            $conn->set_charset(DB_CHARSET);
            $tzOffset = date('P');
            @$conn->query("SET time_zone = '{$tzOffset}'");
        } catch (mysqli_sql_exception $e) {
            if (php_sapi_name() === 'cli') {
                die("Koneksi Database Gagal: " . $e->getMessage() . "\n");
            }
            die("<div style='font-family:sans-serif;padding:2rem;background:#0f172a;color:#ef4444;'>
                    <h3>Gagal Terhubung ke Database MySQL</h3>
                    <p>{$e->getMessage()}</p>
                    <p style='color:#94a3b8;'>Periksa kredensial database di file <code>config/db_local.php</code></p>
                 </div>");
        }
    }
    return $conn;
}

function db_query(string $sql, string $types = '', array $params = []) {
    $stmt = db()->prepare($sql);
    if ($types && $params) {
        $bind_params = [];
        $bind_params[] = $types;
        foreach ($params as $key => $value) {
            $bind_params[] = &$params[$key];
        }
        call_user_func_array([$stmt, 'bind_param'], $bind_params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result === false) {
        return true;
    }
    return $result;
}

function db_fetch_all(string $sql, string $types = '', array $params = []): array {
    $result = db_query($sql, $types, $params);
    if ($result instanceof mysqli_result) {
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    return [];
}

function db_fetch_one(string $sql, string $types = '', array $params = []): ?array {
    $result = db_query($sql, $types, $params);
    if ($result instanceof mysqli_result) {
        $row = $result->fetch_assoc();
        return $row ?: null;
    }
    return null;
}

function db_execute(string $sql, string $types = '', array $params = []): int {
    $stmt = db()->prepare($sql);
    if ($types && $params) {
        $bind_params = [];
        $bind_params[] = $types;
        foreach ($params as $key => $value) {
            $bind_params[] = &$params[$key];
        }
        call_user_func_array([$stmt, 'bind_param'], $bind_params);
    }
    $stmt->execute();
    return $stmt->affected_rows;
}

function db_last_id(): int {
    return (int) db()->insert_id;
}

