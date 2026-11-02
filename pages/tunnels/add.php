<?php
if (!defined('IN_APP')) die('Direct access denied.');

$pageTitle = 'Tambah Router Tunnel — ' . APP_NAME;
$servers = db_fetch_all("SELECT * FROM servers WHERE is_active = 1");

if (empty($servers)) {
    flash_set('danger', 'Belum ada Server Gateway yang aktif. Silakan tambahkan server terlebih dahulu.');
    header("Location: index.php?page=servers");
    exit;
}

$defaultServer = $servers[0];
$nextIp = get_next_tunnel_ip((int)$defaultServer['id'], $defaultServer['vpn_subnet']);
$autoUser = 'mk_' . substr(bin2hex(random_bytes(3)), 0, 6);
$autoPass = bin2hex(random_bytes(4));

require_once BASE_PATH . '/include/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card-custom p-4">
            <div class="d-flex align-items-center mb-4 pb-3 border-bottom border-secondary border-opacity-25">
                <a href="index.php?page=tunnels" class="btn btn-outline-secondary btn-sm me-3">
                    <i class="fa-solid fa-arrow-left"></i>
                </a>
                <div>
                    <h4 class="fw-bold text-white mb-0">Tambah Router Tunnel Baru</h4>
                    <p class="text-secondary small mb-0">Hubungkan Mikrotik cabang atau pelanggan ke VPN Server</p>
                </div>
            </div>

            <form method="POST" action="process/tunnel_action.php">
                <input type="hidden" name="action" value="create">

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Nama Router / Identitas *</label>
                        <input type="text" name="client_name" class="form-control" placeholder="Contoh: RB450Gx4 Cabang LUSUO" required autofocus>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Gateway Server *</label>
                        <select name="server_id" class="form-select" id="serverSelect">
                            <?php foreach ($servers as $s): ?>
                                <option value="<?= $s['id'] ?>" data-subnet="<?= h($s['vpn_subnet']) ?>">
                                    <?= h($s['server_name']) ?> (<?= h($s['public_ip']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Protokol Tunnel *</label>
                        <select name="protocol" class="form-select" id="protoSelect">
                            <option value="wireguard" selected>WireGuard (Rekomendasi - RouterOS v7 Kencang & Hemat RAM)</option>
                            <option value="sstp">SSTP (Kompatibel RouterOS v6 & v7 via Port 443 TCP)</option>
                            <option value="l2tp">L2TP / IPSec (Standar RouterOS v6 & v7)</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">IP Tunnel Lokal (Otomatis) *</label>
                        <input type="text" name="tunnel_ip" id="tunnel_ip" class="form-control font-monospace" value="<?= h($nextIp) ?>" required>
                        <div class="form-text text-secondary small">Alamat IP privat dalam jaringan tunnel VPN.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Username VPN *</label>
                        <input type="text" name="vpn_username" class="form-control" value="<?= h($autoUser) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Password VPN *</label>
                        <input type="text" name="vpn_password" class="form-control" value="<?= h($autoPass) ?>" required>
                    </div>
                </div>

                <div class="p-3 rounded-3 mb-4" style="background:#0d172e; border:1px solid #1e2c4f;">
                    <div class="fw-bold small text-info mb-2">
                        <i class="fa-solid fa-key me-1"></i> Kredensial API Mikrotik (Opsional untuk Monitoring Real-time)
                    </div>
                    <div class="row g-2">
                        <div class="col-md-5">
                            <input type="text" name="api_user" class="form-control form-control-sm" placeholder="User API (default: admin)" value="admin">
                        </div>
                        <div class="col-md-5">
                            <input type="password" name="api_password" class="form-control form-control-sm" placeholder="Password API Router">
                        </div>
                        <div class="col-md-2">
                            <input type="number" name="api_port" class="form-control form-control-sm" placeholder="Port" value="8728">
                        </div>
                    </div>
                    <div class="form-text text-secondary small mt-1">Digunakan untuk mengambil telemetri CPU, Uptime, Ping, dan model router langsung dari web panel.</div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="index.php?page=tunnels" class="btn btn-outline-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="fa-solid fa-check me-1"></i> Simpan & Buat Skrip RouterOS
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once BASE_PATH . '/include/footer.php'; ?>

