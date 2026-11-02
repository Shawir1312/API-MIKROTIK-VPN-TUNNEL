<?php
/**
 * VPN Tunnel & Remote RouterOS — Authentication & Session Manager
 */

if (!defined('IN_APP')) die('Direct access denied.');

function current_user(): ?array {
    return $_SESSION['vpn_user'] ?? null;
}

function auth_check(): bool {
    return !empty($_SESSION['vpn_user']['id']);
}

function is_admin(): bool {
    return (current_user()['role'] ?? '') === 'admin';
}

function auth_require_login(): void {
    if (!auth_check()) {
        header("Location: login.php");
        exit;
    }
}

function auth_require_admin(): void {
    auth_require_login();
    if (!is_admin()) {
        die("<div style='font-family:sans-serif;padding:2rem;background:#0f172a;color:#ef4444;'>
                <h3>Akses Ditolak</h3>
                <p>Halaman ini memerlukan hak akses Administrator.</p>
                <a href='index.php' style='color:#38bdf8;'>Kembali ke Dashboard</a>
             </div>");
    }
}

