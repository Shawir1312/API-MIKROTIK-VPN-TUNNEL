<?php
if (!defined('IN_APP')) die('Direct access denied.');

$pageTitle = 'Router Tunnels — ' . APP_NAME;
$user = current_user();
$userId = (int)$user['id'];
$isAdmin = is_admin();

$where = $isAdmin ? "" : "WHERE t.user_id = {$userId}";
$tunnels = db_fetch_all("SELECT t.*, s.server_name, s.public_ip, u.username as owner FROM tunnels t 
                         JOIN servers s ON t.server_id = s.id 
                         JOIN users u ON t.user_id = u.id 
                         {$where} 
                         ORDER BY t.id DESC");

require_once BASE_PATH . '/include/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h3 class="fw-bold text-white mb-1">Daftar Router Tunnels</h3>
        <p class="text-secondary small mb-0">Kelola koneksi tunnel VPN router Mikrotik cabang & pelanggan</p>
    </div>
    <a href="index.php?page=tunnels&action=add" class="btn btn-primary rounded-3">
        <i class="fa-solid fa-plus me-1"></i> Tambah Router Tunnel
    </a>
</div>

<div class="card-custom p-3">
    <div class="table-responsive">
        <table class="table table-custom align-middle">
            <thead>
                <tr>
                    <th>Nama Router & Klien</th>
                    <th>Protokol</th>
                    <th>IP Tunnel Lokal</th>
                    <th>Gateway Server</th>
                    <th>Status Koneksi</th>
                    <th>Latency / Ping</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tunnels)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-diagram-project fs-1 opacity-25 d-block mb-3"></i>
                            Belum ada router tunnel yang terdaftar. Klik tombol <strong>Tambah Router Tunnel</strong> untuk memulai.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($tunnels as $t): ?>
                        <tr>
                            <td>
                                <div class="fw-bold text-white fs-6"><?= h($t['client_name']) ?></div>
                                <div class="small text-muted">User VPN: <code><?= h($t['vpn_username']) ?></code></div>
                            </td>
                            <td>
                                <span class="badge bg-dark border border-secondary text-uppercase" style="font-size:0.75rem;">
                                    <?= h($t['protocol']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="text-info font-monospace fw-semibold"><?= h($t['tunnel_ip']) ?></span>
                            </td>
                            <td>
                                <div class="small fw-semibold text-white"><?= h($t['server_name']) ?></div>
                                <div class="small text-muted font-monospace"><?= h($t['public_ip']) ?></div>
                            </td>
                            <td>
                                <span class="badge <?= $t['status'] === 'online' ? 'badge-online' : 'badge-offline' ?> px-2 py-1">
                                    <i class="fa-solid fa-circle me-1" style="font-size: 0.5rem;"></i> <?= strtoupper($t['status']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($t['last_latency'] !== null): ?>
                                    <span class="small <?= $t['last_latency'] < 50 ? 'text-success' : ($t['last_latency'] < 100 ? 'text-warning' : 'text-danger') ?>">
                                        <i class="fa-solid fa-bolt me-1"></i> <?= $t['last_latency'] ?> ms
                                    </span>
                                <?php else: ?>
                                    <span class="small text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <a href="index.php?page=tunnels&action=script&id=<?= $t['id'] ?>" class="btn btn-outline-info btn-sm me-1" title="Lihat Skrip Konfigurasi">
                                    <i class="fa-solid fa-terminal me-1"></i> Skrip
                                </a>
                                <a href="index.php?page=ports&action=add&tunnel_id=<?= $t['id'] ?>" class="btn btn-outline-primary btn-sm me-1" title="Buka Port DST-NAT">
                                    <i class="fa-solid fa-shuffle"></i>
                                </a>
                                <a href="process/tunnel_action.php?action=delete&id=<?= $t['id'] ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Yakin ingin menghapus tunnel ini? Semua port forward terkait akan terhapus.')" title="Hapus Tunnel">
                                    <i class="fa-solid fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once BASE_PATH . '/include/footer.php'; ?>

