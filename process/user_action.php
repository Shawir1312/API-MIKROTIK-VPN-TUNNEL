<?php
/**
 * VPN Tunnel & Remote RouterOS Gateway
 * Process Controller: User Action (CRUD)
 */

define('IN_APP', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../include/auth.php';
require_once __DIR__ . '/../include/functions.php';

auth_require_admin();

$action = post('action', get('action', ''));

switch ($action) {
    case 'create':
        $name     = trim(post('name', ''));
        $username = strtolower(preg_replace('/[^a-zA-Z0-9_-]/', '', trim(post('username', ''))));
        $password = trim(post('password', ''));
        $email    = trim(post('email', ''));
        $role     = trim(post('role', 'user'));

        if (!$name || !$username || !$password) {
            flash_set('danger', 'Nama, Username, dan Password wajib diisi.');
            header("Location: ../index.php?page=users");
            exit;
        }

        $exists = db_fetch_one("SELECT id FROM users WHERE username = ?", 's', [$username]);
        if ($exists) {
            flash_set('danger', "Username '{$username}' sudah terdaftar. Gunakan username lain.");
            header("Location: ../index.php?page=users");
            exit;
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        db_insert(
            "INSERT INTO users (name, username, password, email, role, is_active) VALUES (?, ?, ?, ?, ?, 1)",
            'sssss',
            [$name, $username, $hash, $email, $role]
        );

        flash_set('success', "Pengguna '{$name}' (@{$username}) berhasil dibuat!");
        header("Location: ../index.php?page=users");
        exit;

    case 'delete':
        $id = (int)post('id', get('id', 0));
        if ($id <= 1) {
            flash_set('danger', 'Akun Administrator Utama tidak boleh dihapus.');
            header("Location: ../index.php?page=users");
            exit;
        }

        db_execute("DELETE FROM ports WHERE tunnel_id IN (SELECT id FROM tunnels WHERE user_id = ?)", 'i', [$id]);
        db_execute("DELETE FROM tunnels WHERE user_id = ?", 'i', [$id]);
        db_execute("DELETE FROM users WHERE id = ?", 'i', [$id]);

        flash_set('success', "Pengguna berhasil dihapus.");
        header("Location: ../index.php?page=users");
        exit;

    default:
        header("Location: ../index.php?page=users");
        exit;
}

