<?php
if (!defined('IN_APP')) die('Direct access denied.');

$id = (int)($_GET['id'] ?? 0);
$tunnel = db_fetch_one("SELECT t.*, s.server_name, s.public_ip, s.vpn_subnet FROM tunnels t JOIN servers s ON t.server_id = s.id WHERE t.id = ?", 'i', [$id]);

if (!$tunnel) {
    flash_set('danger', 'Data tunnel tidak ditemukan.');
    header("Location: index.php?page=tunnels");
    exit;
}

$server = [
    'public_ip'  => $tunnel['public_ip'],
    'vpn_subnet' => $tunnel['vpn_subnet']
];

if ($tunnel['protocol'] === 'wireguard') {
    $script = VpnManager::generateWireguardScript($tunnel, $server);
} elseif ($tunnel['protocol'] === 'sstp') {
    $script = VpnManager::generateSstpScript($tunnel, $server);
} else {
    $script = VpnManager::generateL2tpScript($tunnel, $server);
}

$pageTitle = 'Skrip RouterOS — ' . h($tunnel['client_name']);
require_once BASE_PATH . '/include/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="card-custom p-4">
            <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom border-secondary border-opacity-25 flex-wrap gap-2">
                <div>
                    <a href="index.php?page=tunnels" class="text-secondary text-decoration-none small d-inline-block mb-1">
                        <i class="fa-solid fa-arrow-left me-1"></i> Kembali ke Daftar Router
                    </a>
                    <h4 class="fw-bold text-white mb-0">Skrip Konfigurasi RouterOS (1-Click)</h4>
                    <span class="badge bg-primary text-uppercase mt-1"><?= h($tunnel['protocol']) ?></span>
                    <span class="text-secondary small ms-2">IP Tunnel: <strong class="text-info"><?= h($tunnel['tunnel_ip']) ?></strong></span>
                </div>
                <button type="button" class="btn btn-success shadow-sm" onclick="copyToClipboard(document.getElementById('scriptCode').value, this)">
                    <i class="fa-regular fa-copy me-1"></i> Salin Semua Skrip
                </button>
            </div>

            <div class="alert alert-info border-0 rounded-3 small mb-3">
                <i class="fa-solid fa-circle-info me-2 fs-6"></i>
                <strong>Cara Penggunaan:</strong> Buka aplikasi <strong>Winbox</strong> atau terminal SSH Mikrotik Anda, lalu tempelkan (paste) seluruh baris perintah di bawah ini ke dalam <strong>Terminal RouterOS</strong>.
            </div>

            <div class="position-relative mb-3">
                <textarea id="scriptCode" class="form-control font-monospace p-3 small" rows="14" style="background:#091024; color:#38bdf8; border-color:#233560; font-size:0.85rem;" readonly><?= htmlspecialchars($script) ?></textarea>
            </div>

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 pt-2">
                <div class="text-muted small">
                    <i class="fa-solid fa-shield-halved text-success me-1"></i> Koneksi terenkripsi langsung ke IP Publik <code><?= h($tunnel['public_ip']) ?></code>
                </div>
                <div>
                    <a href="index.php?page=ports&action=add&tunnel_id=<?= $tunnel['id'] ?>" class="btn btn-outline-info btn-sm">
                        <i class="fa-solid fa-shuffle me-1"></i> Lanjut: Buka Port DST-NAT Router Ini
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once BASE_PATH . '/include/footer.php'; ?>
