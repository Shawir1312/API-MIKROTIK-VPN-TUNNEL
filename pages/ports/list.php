<?php
if (!defined('IN_APP')) die('Direct access denied.');

$pageTitle = 'Port Forwarding (DST-NAT) — ' . APP_NAME;
$user = current_user();
$userId = (int)$user['id'];
$isAdmin = is_admin();

$where = $isAdmin ? "" : "WHERE t.user_id = {$userId}";
$ports = db_fetch_all("SELECT pf.*, t.client_name, t.tunnel_ip, s.public_ip, s.server_name FROM port_forwards pf 
                       JOIN tunnels t ON pf.tunnel_id = t.id 
                       JOIN servers s ON t.server_id = s.id 
                       {$where} 
                       ORDER BY pf.id DESC");

require_once BASE_PATH . '/include/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h3 class="fw-bold text-white mb-1">Daftar Port Forwarding (DST-NAT)</h3>
        <p class="text-secondary small mb-0">Pemetaan akses port IP Publik menuju port lokal router & perangkat LAN</p>
    </div>
    <div class="d-flex gap-2">
        <a href="process/port_action.php?action=sync_all" class="btn btn-outline-info rounded-3">
            <i class="fa-solid fa-arrows-rotate me-1"></i> Sinkron Firewall NAT
        </a>
        <a href="index.php?page=ports&action=add" class="btn btn-primary rounded-3">
            <i class="fa-solid fa-plus me-1"></i> Buka Port Baru
        </a>
    </div>
</div>

<div class="card-custom p-3">
    <div class="table-responsive">
        <table class="table table-custom align-middle">
            <thead>
                <tr>
                    <th>Label Service</th>
                    <th>Router Klien</th>
                    <th>Akses Publik (Host:Port)</th>
                    <th>Target Lokal (Tujuan)</th>
                    <th>Protokol</th>
                    <th>Status</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($ports)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-shuffle fs-1 opacity-25 d-block mb-3"></i>
                            Belum ada port forward yang dibuat. Klik tombol <strong>Buka Port Baru</strong> di atas.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($ports as $p): ?>
                        <tr>
                            <td>
                                <div class="fw-bold text-white fs-6"><?= h($p['label']) ?></div>
                                <?php if (!empty($p['notes'])): ?>
                                    <div class="small text-muted"><?= h($p['notes']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="fw-semibold text-white small"><?= h($p['client_name']) ?></div>
                                <div class="small text-muted font-monospace">Tunnel: <?= h($p['tunnel_ip']) ?></div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="badge-port me-2"><?= h($p['public_ip']) ?>:<?= $p['public_port'] ?></span>
                                    <span class="copy-btn text-muted" title="Salin Host:Port" onclick="copyToClipboard('<?= h($p['public_ip']) ?>:<?= $p['public_port'] ?>', this)">
                                        <i class="fa-regular fa-copy"></i>
                                    </span>
                                </div>
                            </td>
                            <td>
                                <span class="text-info font-monospace small"><?= h($p['target_ip']) ?>:<?= $p['target_port'] ?></span>
                            </td>
                            <td>
                                <span class="badge bg-secondary text-uppercase" style="font-size:0.7rem;"><?= h($p['protocol']) ?></span>
                            </td>
                            <td>
                                <?php if ($p['is_enabled']): ?>
                                    <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-50">Aktif</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Nonaktif</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <a href="process/port_action.php?action=toggle&id=<?= $p['id'] ?>" class="btn btn-outline-secondary btn-sm me-1" title="Aktif/Nonaktifkan">
                                    <i class="fa-solid <?= $p['is_enabled'] ? 'fa-pause' : 'fa-play' ?>"></i>
                                </a>
                                <a href="process/port_action.php?action=delete&id=<?= $p['id'] ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Yakin ingin menghapus port forward ini?')" title="Hapus Port">
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
