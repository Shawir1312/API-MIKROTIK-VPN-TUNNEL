<?php
/**
 * S.NET VPN Remote & Tunnel Gateway
 * Page: Users Management (Admin Only)
 */

if (!defined('IN_APP')) die('Direct access denied.');
auth_require_admin();

$pageTitle = 'Kelola Pengguna — ' . APP_NAME;
$users = db_fetch_all("SELECT u.*, 
                       (SELECT COUNT(*) FROM tunnels WHERE user_id = u.id) as total_tunnels 
                       FROM users u 
                       ORDER BY u.id ASC");

require_once BASE_PATH . '/include/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-white mb-1"><i class="fa-solid fa-users text-primary me-2"></i>Manajemen Pengguna</h4>
        <p class="text-secondary small mb-0">Kelola akun administrator dan klien pengelola router tunnel</p>
    </div>
    <div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAddUser">
            <i class="fa-solid fa-user-plus me-1"></i> Tambah Pengguna Baru
        </button>
    </div>
</div>

<div class="card-custom p-0 overflow-hidden mb-4">
    <div class="table-responsive">
        <table class="table table-dark table-hover mb-0 align-middle">
            <thead class="border-secondary border-opacity-25 small text-uppercase text-secondary">
                <tr>
                    <th class="ps-4">Pengguna</th>
                    <th>Email</th>
                    <th>Peran (Role)</th>
                    <th>Total Router Tunnel</th>
                    <th>Status</th>
                    <th>Dibuat Pada</th>
                    <th class="text-end pe-4">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td class="ps-4">
                            <div class="fw-bold text-white"><?= h($u['name']) ?></div>
                            <small class="text-secondary font-monospace">@<?= h($u['username']) ?></small>
                        </td>
                        <td><?= h($u['email'] ?: '—') ?></td>
                        <td>
                            <?php if ($u['role'] === 'admin'): ?>
                                <span class="badge bg-danger"><i class="fa-solid fa-shield-halved me-1"></i> Super Admin</span>
                            <?php else: ?>
                                <span class="badge bg-secondary"><i class="fa-solid fa-user me-1"></i> Client / Operator</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-primary bg-opacity-25 text-info"><?= (int)$u['total_tunnels'] ?> Router</span>
                        </td>
                        <td>
                            <?php if ($u['is_active']): ?>
                                <span class="badge bg-success">Aktif</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Nonaktif</span>
                            <?php endif; ?>
                        </td>
                        <td class="small text-secondary"><?= h($u['created_at']) ?></td>
                        <td class="text-end pe-4">
                            <?php if ($u['id'] != 1): ?>
                                <a href="process/user_action.php?action=delete&id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus user ini? Seluruh router tunnel miliknya akan ikut terhapus!')">
                                    <i class="fa-solid fa-trash"></i>
                                </a>
                            <?php else: ?>
                                <span class="badge bg-dark border border-secondary text-secondary">Utama</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL ADD USER -->
<div class="modal fade" id="modalAddUser" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark border-secondary">
            <div class="modal-header border-secondary">
                <h5 class="modal-title text-white"><i class="fa-solid fa-user-plus me-2 text-primary"></i>Tambah Pengguna Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="process/user_action.php">
                <input type="hidden" name="action" value="create">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap *</label>
                        <input type="text" name="name" class="form-control" required placeholder="Contoh: Teknisi Cabang">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Username *</label>
                        <input type="text" name="username" class="form-control font-monospace" required placeholder="Contoh: teknisi1">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password *</label>
                        <input type="password" name="password" class="form-control" required minlength="6">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email (Opsional)</label>
                        <input type="email" name="email" class="form-control" placeholder="nama@domain.com">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role Akses *</label>
                        <select name="role" class="form-select">
                            <option value="user">User / Klien (Hanya melihat tunnel &amp; port miliknya)</option>
                            <option value="admin">Super Admin (Akses penuh seluruh router, gateway &amp; pengguna)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save me-1"></i> Simpan Pengguna</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once BASE_PATH . '/include/footer.php'; ?>
