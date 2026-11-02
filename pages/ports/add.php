<?php
if (!defined('IN_APP')) die('Direct access denied.');

$pageTitle = 'Buka Port Forwarding (DST-NAT) — ' . APP_NAME;
$user = current_user();
$userId = (int)$user['id'];
$isAdmin = is_admin();

$where = $isAdmin ? "" : "WHERE t.user_id = {$userId}";
$tunnels = db_fetch_all("SELECT t.*, s.public_ip, s.min_port, s.max_port FROM tunnels t 
                         JOIN servers s ON t.server_id = s.id 
                         {$where} 
                         ORDER BY t.id DESC");

if (empty($tunnels)) {
    flash_set('danger', 'Anda belum memiliki Router Tunnel yang terdaftar. Tambahkan router terlebih dahulu.');
    header("Location: index.php?page=tunnels&action=add");
    exit;
}

$selectedTunnelId = (int)($_GET['tunnel_id'] ?? $tunnels[0]['id']);
$selectedTunnel = null;
foreach ($tunnels as $t) {
    if ((int)$t['id'] === $selectedTunnelId) {
        $selectedTunnel = $t;
        break;
    }
}
if (!$selectedTunnel) $selectedTunnel = $tunnels[0];

$autoPublicPort = get_next_available_public_port((int)($selectedTunnel['min_port'] ?? 10000), (int)($selectedTunnel['max_port'] ?? 50000));

require_once BASE_PATH . '/include/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card-custom p-4">
            <div class="d-flex align-items-center mb-4 pb-3 border-bottom border-secondary border-opacity-25">
                <a href="index.php?page=ports" class="btn btn-outline-secondary btn-sm me-3">
                    <i class="fa-solid fa-arrow-left"></i>
                </a>
                <div>
                    <h4 class="fw-bold text-white mb-0">Buka Port Forwarding (DST-NAT) Baru</h4>
                    <p class="text-secondary small mb-0">Teruskan port IP Publik menuju port lokal Mikrotik atau perangkat LAN</p>
                </div>
            </div>

            <!-- PRESET QUICK SELECTOR -->
            <div class="mb-4 p-3 rounded-3" style="background:#0d172e; border:1px solid #1e2c4f;">
                <label class="form-label small text-info fw-bold mb-2">
                    <i class="fa-solid fa-bolt me-1"></i> Preset Port Populer (Klik untuk Mengisi Otomatis):
                </label>
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="applyPreset('Winbox Remote', 8291, 'tcp')">
                        <i class="fa-solid fa-desktop me-1"></i> Winbox (8291)
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-info" onclick="applyPreset('WebFig HTTP', 80, 'tcp')">
                        <i class="fa-solid fa-globe me-1"></i> WebFig (80)
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="applyPreset('WebFig HTTPS', 443, 'tcp')">
                        <i class="fa-solid fa-lock me-1"></i> HTTPS (443)
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-warning" onclick="applyPreset('RouterOS API', 8728, 'tcp')">
                        <i class="fa-solid fa-code me-1"></i> API (8728)
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-light" onclick="applyPreset('SSH Terminal', 22, 'tcp')">
                        <i class="fa-solid fa-terminal me-1"></i> SSH (22)
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-success" onclick="applyPreset('CCTV / Web LAN', 80, 'tcp', true)">
                        <i class="fa-solid fa-video me-1"></i> CCTV / ONT LAN
                    </button>
                </div>
            </div>

            <form method="POST" action="process/port_action.php">
                <input type="hidden" name="action" value="create">

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Router Tunnel Tujuan *</label>
                        <select name="tunnel_id" id="tunnelSelect" class="form-select" onchange="updateTargetIp()">
                            <?php foreach ($tunnels as $t): ?>
                                <option value="<?= $t['id'] ?>" data-ip="<?= h($t['tunnel_ip']) ?>" data-public="<?= h($t['public_ip']) ?>" <?= ((int)$t['id'] === (int)$selectedTunnel['id']) ? 'selected' : '' ?>>
                                    <?= h($t['client_name']) ?> (<?= h($t['tunnel_ip']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Label / Nama Layanan *</label>
                        <input type="text" name="label" id="inputLabel" class="form-control" placeholder="Contoh: Remote Winbox" value="Winbox Remote" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Port Akses Publik *</label>
                        <div class="input-group">
                            <span class="input-group-text bg-dark border-secondary border-opacity-50 text-muted font-monospace" id="publicIpPrefix">
                                <?= h($selectedTunnel['public_ip']) ?>:
                            </span>
                            <input type="number" name="public_port" id="inputPublicPort" class="form-control font-monospace" value="<?= $autoPublicPort ?>" min="1024" max="65535" required>
                        </div>
                        <div class="form-text text-secondary small">Port di IP Publik yang akan Anda ketik di Winbox/Browser.</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Protokol</label>
                        <select name="protocol" id="inputProtocol" class="form-select">
                            <option value="tcp" selected>TCP (Winbox, WebFig, SSH, API)</option>
                            <option value="udp">UDP (DNS, WireGuard)</option>
                            <option value="both">Both (TCP & UDP)</option>
                        </select>
                    </div>

                    <div class="col-md-8">
                        <label class="form-label">IP Tujuan Lokal (Target IP) *</label>
                        <input type="text" name="target_ip" id="inputTargetIp" class="form-control font-monospace" value="<?= h($selectedTunnel['tunnel_ip']) ?>" required>
                        <div class="form-text text-secondary small">Gunakan IP Tunnel untuk router sendiri, atau masukkan IP LAN klien (misal 192.168.1.50) untuk remote CCTV/ONT.</div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Port Tujuan Lokal *</label>
                        <input type="number" name="target_port" id="inputTargetPort" class="form-control font-monospace" value="8291" min="1" max="65535" required>
                        <div class="form-text text-secondary small">Port internal (misal 8291 untuk Winbox).</div>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Catatan Tambahan (Opsional)</label>
                        <input type="text" name="notes" class="form-control" placeholder="Catatan kegunaan port forward ini">
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 pt-3 border-top border-secondary border-opacity-25">
                    <a href="index.php?page=ports" class="btn btn-outline-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="fa-solid fa-check me-1"></i> Buka Port DST-NAT Sekarang
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function updateTargetIp() {
    const select = document.getElementById('tunnelSelect');
    const selectedOption = select.options[select.selectedIndex];
    const tunnelIp = selectedOption.getAttribute('data-ip');
    const publicIp = selectedOption.getAttribute('data-public');

    document.getElementById('inputTargetIp').value = tunnelIp;
    document.getElementById('publicIpPrefix').textContent = publicIp + ':';
}

function applyPreset(label, port, proto, isLan = false) {
    document.getElementById('inputLabel').value = label;
    document.getElementById('inputTargetPort').value = port;
    document.getElementById('inputProtocol').value = proto;

    if (isLan) {
        document.getElementById('inputTargetIp').value = '192.168.1.1';
        document.getElementById('inputTargetIp').focus();
    } else {
        updateTargetIp();
    }
}
</script>

<?php require_once BASE_PATH . '/include/footer.php'; ?>
