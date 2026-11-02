<?php
/**
 * VPN Tunnel & Remote RouterOS Gateway
 * Page: Router Live Monitoring (RouterOS API via Tunnel IP)
 */

if (!defined('IN_APP')) die('Direct access denied.');

$pageTitle = 'Monitoring Router — ' . APP_NAME;
$user = current_user();
$userId = (int)$user['id'];
$isAdmin = is_admin();

$where = $isAdmin ? "" : "WHERE t.user_id = {$userId}";
$tunnels = db_fetch_all("SELECT t.*, s.public_ip FROM tunnels t JOIN servers s ON t.server_id = s.id {$where} ORDER BY t.id DESC");

$selectedId = (int)($_GET['id'] ?? ($tunnels[0]['id'] ?? 0));
$selected = null;
foreach ($tunnels as $t) {
    if ((int)$t['id'] === $selectedId) {
        $selected = $t;
        break;
    }
}

require_once BASE_PATH . '/include/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-white mb-1"><i class="fa-solid fa-chart-line text-primary me-2"></i>Live Monitoring Mikrotik via API</h4>
        <p class="text-secondary small mb-0">Inspeksi kesehatan CPU, memory, uptime, dan koneksi langsung menembus IP tunnel lokal</p>
    </div>
    <div class="d-flex gap-2">
        <select class="form-select bg-dark border-secondary text-white" onchange="window.location.href='index.php?page=monitoring&id=' + this.value">
            <?php foreach ($tunnels as $t): ?>
                <option value="<?= $t['id'] ?>" <?= ($t['id'] == $selectedId) ? 'selected' : '' ?>>
                    <?= h($t['client_name']) ?> (<?= h($t['tunnel_ip']) ?>)
                </option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-outline-info" onclick="refreshMetrics()">
            <i class="fa-solid fa-rotate me-1"></i> Refresh
        </button>
    </div>
</div>

<?php if (!$selected): ?>
    <div class="card-custom p-5 text-center text-secondary">
        <i class="fa-solid fa-router display-5 d-block mb-3 opacity-50"></i>
        <h5>Belum ada router tunnel untuk dimonitor.</h5>
        <a href="index.php?page=tunnels&action=add" class="btn btn-primary mt-2">Daftarkan Router Pertama</a>
    </div>
<?php else: ?>
    <!-- STAT CARDS -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card-custom p-3 d-flex align-items-center">
                <div class="p-3 rounded-3 bg-primary bg-opacity-10 text-primary me-3 fs-3">
                    <i class="fa-solid fa-microchip"></i>
                </div>
                <div>
                    <span class="text-secondary small d-block">CPU Load</span>
                    <h3 class="fw-bold text-white mb-0" id="statCpu">—</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-custom p-3 d-flex align-items-center">
                <div class="p-3 rounded-3 bg-success bg-opacity-10 text-success me-3 fs-3">
                    <i class="fa-solid fa-memory"></i>
                </div>
                <div>
                    <span class="text-secondary small d-block">Free Memory</span>
                    <h3 class="fw-bold text-white mb-0" id="statMemory">—</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-custom p-3 d-flex align-items-center">
                <div class="p-3 rounded-3 bg-warning bg-opacity-10 text-warning me-3 fs-3">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </div>
                <div>
                    <span class="text-secondary small d-block">Uptime</span>
                    <h5 class="fw-bold text-white mb-0" id="statUptime">—</h5>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-custom p-3 d-flex align-items-center">
                <div class="p-3 rounded-3 bg-info bg-opacity-10 text-info me-3 fs-3">
                    <i class="fa-solid fa-signal"></i>
                </div>
                <div>
                    <span class="text-secondary small d-block">Tunnel Latency</span>
                    <h3 class="fw-bold text-white mb-0" id="statLatency">—</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- DETAIL ROUTER & API FORM -->
    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card-custom p-4 mb-4">
                <h5 class="fw-bold text-white mb-3"><i class="fa-solid fa-circle-info text-info me-2"></i>Informasi Tunnel Router</h5>
                <table class="table table-dark table-sm mb-0">
                    <tr>
                        <td class="text-secondary">Nama Router:</td>
                        <td class="text-white fw-bold"><?= h($selected['client_name']) ?></td>
                    </tr>
                    <tr>
                        <td class="text-secondary">IP Tunnel:</td>
                        <td><span class="badge bg-primary bg-opacity-25 text-info font-monospace"><?= h($selected['tunnel_ip']) ?></span></td>
                    </tr>
                    <tr>
                        <td class="text-secondary">Protokol VPN:</td>
                        <td><span class="badge bg-info text-dark text-uppercase"><?= h($selected['vpn_type']) ?></span></td>
                    </tr>
                    <tr>
                        <td class="text-secondary">Public Gateway:</td>
                        <td class="text-white font-monospace"><?= h($selected['public_ip']) ?></td>
                    </tr>
                    <tr>
                        <td class="text-secondary">Status Tunnel:</td>
                        <td>
                            <?php if ($selected['status'] === 'active'): ?>
                                <span class="badge bg-success">Terhubung / Aktif</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark">Offline</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- KREDENSIAL API MIKROTIK -->
            <div class="card-custom p-4">
                <h5 class="fw-bold text-white mb-3"><i class="fa-solid fa-key text-warning me-2"></i>Kredensial API MikroTik</h5>
                <form id="formApiAuth" onsubmit="connectApi(event)">
                    <div class="mb-3">
                        <label class="form-label small">Port API MikroTik</label>
                        <input type="number" id="apiPort" class="form-control font-monospace" value="8728">
                        <small class="text-secondary">Default API: 8728 (atau 8729 jika SSL API-SSL)</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Username API</label>
                        <input type="text" id="apiUser" class="form-control" value="admin">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Password API</label>
                        <input type="password" id="apiPass" class="form-control">
                    </div>
                    <button type="submit" id="btnConnectApi" class="btn btn-primary w-100">
                        <i class="fa-solid fa-plug me-1"></i> Uji &amp; Hubungkan API Sekarang
                    </button>
                </form>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card-custom p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-white mb-0"><i class="fa-solid fa-terminal text-success me-2"></i>RouterOS System Resource</h5>
                    <span id="apiStatusBadge" class="badge bg-secondary">Standby</span>
                </div>
                <div id="apiResultBox" class="p-3 rounded-3 font-monospace small" style="background:#090f1d; border:1px solid #1a2744; min-height: 280px; max-height: 420px; overflow-y:auto; color:#a5b4fc;">
                    Tekan tombol "Hubungkan API" untuk membaca spesifikasi mikrotik melalui socket API langsung.
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
function refreshMetrics() {
    connectApi();
}

function connectApi(e) {
    if (e) e.preventDefault();
    const btn = document.getElementById('btnConnectApi');
    const badge = document.getElementById('apiStatusBadge');
    const box = document.getElementById('apiResultBox');
    if (!btn) return;

    btn.disabled = true;
    badge.className = 'badge bg-warning text-dark';
    badge.innerText = 'Menghubungkan...';
    box.innerHTML = '<span class="text-info"><i class="fa-solid fa-spinner fa-spin me-2"></i>Membuka koneksi socket TCP ke <?= h($selected['tunnel_ip'] ?? '') ?>...</span>';

    const fd = new FormData();
    fd.append('tunnel_id', '<?= (int)($selected['id'] ?? 0) ?>');
    fd.append('api_port', document.getElementById('apiPort').value);
    fd.append('api_user', document.getElementById('apiUser').value);
    fd.append('api_pass', document.getElementById('apiPass').value);

    fetch('ajax/check_router_api.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            btn.disabled = false;
            if (res.success) {
                badge.className = 'badge bg-success';
                badge.innerText = 'Connected';
                document.getElementById('statCpu').innerText = (res.resource['cpu-load'] || '0') + '%';
                document.getElementById('statMemory').innerText = Math.round((res.resource['free-memory'] || 0) / 1024 / 1024) + ' MB';
                document.getElementById('statUptime').innerText = res.resource['uptime'] || 'Online';
                document.getElementById('statLatency').innerText = res.latency + ' ms';

                let html = '<div class="text-success mb-2 fw-bold">✓ KONEKSI API ROUTEROS BERHASIL!</div>';
                html += '<table class="table table-dark table-sm table-borderless">';
                for (const [k, v] of Object.entries(res.resource)) {
                    html += `<tr><td class="text-secondary" style="width:180px;">${k}:</td><td class="text-white">${v}</td></tr>`;
                }
                html += '</table>';
                box.innerHTML = html;
            } else {
                badge.className = 'badge bg-danger';
                badge.innerText = 'Failed';
                box.innerHTML = `<div class="text-danger fw-bold"><i class="fa-solid fa-triangle-exclamation me-2"></i>Gagal Terhubung:</div><div class="text-white mt-1">${res.message}</div>`;
            }
        })
        .catch(err => {
            btn.disabled = false;
            badge.className = 'badge bg-danger';
            badge.innerText = 'Error';
            box.innerHTML = `<div class="text-danger">Kesalahan Jaringan: ${err.message}</div>`;
        });
}
</script>

<?php require_once BASE_PATH . '/include/footer.php'; ?>

