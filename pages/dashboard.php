<?php
if (!defined('IN_APP')) die('Direct access denied.');

$pageTitle = 'Dashboard — ' . APP_NAME;
$user = current_user();
$userId = (int)$user['id'];
$isAdmin = is_admin();

// Query counts
$tunnelWhere = $isAdmin ? "" : "WHERE user_id = {$userId}";
$totalTunnels = (int)db_fetch_one("SELECT COUNT(*) as c FROM tunnels {$tunnelWhere}")['c'];
$onlineTunnels = (int)db_fetch_one("SELECT COUNT(*) as c FROM tunnels " . ($isAdmin ? "WHERE status = 'online'" : "WHERE user_id = {$userId} AND status = 'online'"))['c'];

$portWhere = $isAdmin ? "" : "WHERE t.user_id = {$userId}";
$totalPorts = (int)db_fetch_one("SELECT COUNT(*) as c FROM port_forwards pf JOIN tunnels t ON pf.tunnel_id = t.id {$portWhere}")['c'];

$servers = db_fetch_all("SELECT * FROM servers WHERE is_active = 1");

// Ambil 5 router terbaru
$recentTunnels = db_fetch_all("SELECT t.*, s.server_name, s.public_ip FROM tunnels t 
                               JOIN servers s ON t.server_id = s.id 
                               {$tunnelWhere} 
                               ORDER BY t.id DESC LIMIT 5");

// Ambil 10 port forward terbaru
$recentPorts = db_fetch_all("SELECT pf.*, t.client_name, t.tunnel_ip, s.public_ip FROM port_forwards pf 
                             JOIN tunnels t ON pf.tunnel_id = t.id 
                             JOIN servers s ON t.server_id = s.id 
                             {$portWhere} 
                             ORDER BY pf.id DESC LIMIT 10");

require_once BASE_PATH . '/include/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h3 class="fw-bold text-white mb-1">Dashboard Monitoring</h3>
        <p class="text-secondary small mb-0">Status Tunnel VPN Privat & Pemetaan Port Forwarding DST-NAT</p>
    </div>
    <div class="d-flex gap-2">
        <a href="index.php?page=tunnels&action=add" class="btn btn-primary btn-sm rounded-3">
            <i class="fa-solid fa-plus me-1"></i> Tambah Tunnel Router
        </a>
        <a href="index.php?page=ports&action=add" class="btn btn-outline-info btn-sm rounded-3">
            <i class="fa-solid fa-shuffle me-1"></i> Buka Port Baru (DST-NAT)
        </a>
    </div>
</div>

<!-- Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card-custom p-3 d-flex align-items-center">
            <div class="rounded-3 p-3 bg-primary bg-opacity-25 text-primary me-3 fs-3">
                <i class="fa-solid fa-diagram-project"></i>
            </div>
            <div>
                <div class="text-muted small fw-semibold text-uppercase">Total Router Klien</div>
                <div class="fs-4 fw-bold text-white"><?= number_format($totalTunnels) ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card-custom p-3 d-flex align-items-center">
            <div class="rounded-3 p-3 bg-success bg-opacity-25 text-success me-3 fs-3">
                <i class="fa-solid fa-signal"></i>
            </div>
            <div>
                <div class="text-muted small fw-semibold text-uppercase">Router Online</div>
                <div class="fs-4 fw-bold text-success"><?= number_format($onlineTunnels) ?> <span class="fs-6 text-muted fw-normal">/ <?= $totalTunnels ?></span></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card-custom p-3 d-flex align-items-center">
            <div class="rounded-3 p-3 bg-info bg-opacity-25 text-info me-3 fs-3">
                <i class="fa-solid fa-shuffle"></i>
            </div>
            <div>
                <div class="text-muted small fw-semibold text-uppercase">Port DST-NAT Aktif</div>
                <div class="fs-4 fw-bold text-info"><?= number_format($totalPorts) ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card-custom p-3 d-flex align-items-center">
            <div class="rounded-3 p-3 bg-warning bg-opacity-25 text-warning me-3 fs-3">
                <i class="fa-solid fa-server"></i>
            </div>
            <div>
                <div class="text-muted small fw-semibold text-uppercase">Gateway Server</div>
                <div class="fs-4 fw-bold text-white"><?= count($servers) ?> <span class="fs-6 text-muted fw-normal">Node</span></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Tabel Port Forwarding Terbaru -->
    <div class="col-lg-8">
        <div class="card-custom p-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold text-white mb-0">
                    <i class="fa-solid fa-shuffle text-info me-2"></i> Port Forwarding DST-NAT Aktif
                </h5>
                <a href="index.php?page=ports" class="btn btn-outline-secondary btn-sm text-secondary">Lihat Semua</a>
            </div>
            <div class="table-responsive">
                <table class="table table-custom align-middle">
                    <thead>
                        <tr>
                            <th>Label & Router</th>
                            <th>Akses Publik (Winbox/Web)</th>
                            <th>Tujuan Lokal (Target)</th>
                            <th>Protokol</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentPorts)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted small">Belum ada port forward yang dibuat. Klik tombol "Buka Port Baru" di atas.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentPorts as $p): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-white"><?= h($p['label']) ?></div>
                                        <div class="small text-muted"><i class="fa-solid fa-router me-1"></i> <?= h($p['client_name']) ?></div>
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
                                        <span class="small text-info font-monospace"><?= h($p['target_ip']) ?>:<?= $p['target_port'] ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary text-uppercase" style="font-size:0.7rem;"><?= h($p['protocol']) ?></span>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($p['is_enabled']): ?>
                                            <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-50">Aktif</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Mati</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Router Tunnels & Quick Actions -->
    <div class="col-lg-4">
        <div class="card-custom p-3 mb-4">
            <h5 class="fw-bold text-white mb-3">
                <i class="fa-solid fa-router text-primary me-2"></i> Router Klien Terbaru
            </h5>
            <div class="list-group list-group-flush bg-transparent">
                <?php if (empty($recentTunnels)): ?>
                    <div class="text-center py-3 text-muted small">Belum ada router yang terhubung.</div>
                <?php else: ?>
                    <?php foreach ($recentTunnels as $t): ?>
                        <div class="list-group-item bg-transparent border-secondary border-opacity-25 px-0 py-2 d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-bold text-white small"><?= h($t['client_name']) ?></div>
                                <div class="text-muted font-monospace" style="font-size: 0.75rem;">
                                    IP: <?= h($t['tunnel_ip']) ?> | <?= strtoupper($t['protocol']) ?>
                                </div>
                            </div>
                            <div class="text-end">
                                <span class="badge <?= $t['status'] === 'online' ? 'badge-online' : 'badge-offline' ?> mb-1">
                                    <i class="fa-solid fa-circle me-1" style="font-size: 0.5rem;"></i> <?= strtoupper($t['status']) ?>
                                </span>
                                <div>
                                    <a href="index.php?page=tunnels&action=script&id=<?= $t['id'] ?>" class="text-info text-decoration-none small" style="font-size: 0.75rem;">
                                        <i class="fa-solid fa-terminal me-1"></i> Skrip
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="card-custom p-3" style="background: linear-gradient(145deg, #16223f 0%, #0d172e 100%);">
            <h6 class="fw-bold text-white mb-2"><i class="fa-solid fa-circle-info text-info me-2"></i> Petunjuk Cepat Remote</h6>
            <p class="text-secondary small mb-3">
                Untuk meremote Mikrotik via Winbox, cukup salin <code>IP_Publik:Port</code> di kolom DST-NAT dan tempelkan di kolom <em>Connect To</em> aplikasi Winbox Anda.
            </p>
            <div class="d-grid">
                <a href="index.php?page=tunnels&action=add" class="btn btn-outline-primary btn-sm">
                    <i class="fa-solid fa-plus me-1"></i> Pasang Router Baru Sekarang
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once BASE_PATH . '/include/footer.php'; ?>

