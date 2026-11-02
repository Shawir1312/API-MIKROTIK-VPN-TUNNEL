<?php
/**
 * VPN Tunnel & Remote RouterOS Gateway
 * Page: Gateway Servers Management (Admin Only)
 */

if (!defined('IN_APP')) die('Direct access denied.');
auth_require_admin();

$pageTitle = 'Server Gateway VPN — ' . APP_NAME;
$servers = db_fetch_all("SELECT s.*, 
                         (SELECT COUNT(*) FROM tunnels WHERE server_id = s.id) as total_tunnels,
                         (SELECT COUNT(*) FROM ports WHERE server_id = s.id) as total_ports
                         FROM servers s 
                         ORDER BY s.id ASC");

require_once BASE_PATH . '/include/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-white mb-1"><i class="fa-solid fa-server text-primary me-2"></i>Server Gateway VPN &amp; IP Publik</h4>
        <p class="text-secondary small mb-0">Kelola node VPS Linux iptables atau Mikrotik CHR sebagai gateway tunnel dan port forwarding publik</p>
    </div>
    <div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAddServer">
            <i class="fa-solid fa-plus me-1"></i> Tambah Gateway Server
        </button>
    </div>
</div>

<div class="card-custom p-0 overflow-hidden mb-4">
    <div class="table-responsive">
        <table class="table table-dark table-hover mb-0 align-middle">
            <thead class="border-secondary border-opacity-25 small text-uppercase text-secondary">
                <tr>
                    <th class="ps-4">Nama Server</th>
                    <th>IP Publik</th>
                    <th>Tipe Gateway</th>
                    <th>Subnet Tunnel</th>
                    <th>Rentang Port</th>
                    <th>Statistik</th>
                    <th>Status</th>
                    <th class="text-end pe-4">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($servers)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-secondary">
                            <i class="fa-solid fa-server display-6 d-block mb-2 opacity-50"></i>
                            Belum ada server gateway yang terdaftar.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($servers as $srv): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold text-white"><?= h($srv['name']) ?></div>
                                <small class="text-secondary">WG Port: <?= h($srv['wg_port']) ?> (<?= h($srv['wg_interface']) ?>)</small>
                            </td>
                            <td>
                                <span class="badge bg-primary bg-opacity-25 text-info font-monospace fs-6 px-2 py-1">
                                    <?= h($srv['public_ip']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($srv['gateway_type'] === 'linux_iptables'): ?>
                                    <span class="badge bg-secondary"><i class="fa-brands fa-linux me-1"></i> Linux (iptables)</span>
                                <?php else: ?>
                                    <span class="badge bg-info text-dark"><i class="fa-solid fa-network-wired me-1"></i> Mikrotik CHR API</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="font-monospace text-light"><?= h($srv['tunnel_subnet']) ?></span>
                            </td>
                            <td>
                                <span class="badge bg-dark border border-secondary text-secondary font-monospace">
                                    <?= h($srv['min_port']) ?> &ndash; <?= h($srv['max_port']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-info bg-opacity-10 text-info me-1"><?= (int)$srv['total_tunnels'] ?> Tunnel</span>
                                <span class="badge bg-success bg-opacity-10 text-success"><?= (int)$srv['total_ports'] ?> Port NAT</span>
                            </td>
                            <td>
                                <?php if ($srv['is_active']): ?>
                                    <span class="badge bg-success"><i class="fa-solid fa-check me-1"></i> Aktif</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Nonaktif</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4">
                                <button type="button" class="btn btn-sm btn-outline-info me-1" onclick="testGateway(<?= (int)$srv['id'] ?>)" title="Tes Koneksi Gateway">
                                    <i class="fa-solid fa-bolt"></i>
                                </button>
                                <a href="process/server_action.php?action=delete&id=<?= $srv['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus gateway server ini? Peringatan: Seluruh tunnel & port yang tertaut akan ikut terhapus!')" title="Hapus">
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

<!-- MODAL ADD SERVER -->
<div class="modal fade" id="modalAddServer" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content bg-dark border-secondary">
            <div class="modal-header border-secondary">
                <h5 class="modal-title text-white"><i class="fa-solid fa-server me-2 text-primary"></i>Tambah Gateway Server VPN Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="process/server_action.php">
                <input type="hidden" name="action" value="create">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nama Server *</label>
                            <input type="text" name="name" class="form-control" required placeholder="Contoh: VPS Gateway Singapore">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">IP Publik VPS *</label>
                            <input type="text" name="public_ip" class="form-control font-monospace" required placeholder="Contoh: 103.180.200.5">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tipe Gateway *</label>
                            <select name="gateway_type" id="gwTypeSelect" class="form-select" onchange="toggleGwFields()">
                                <option value="linux_iptables">Linux VPS (iptables + WireGuard Native)</option>
                                <option value="mikrotik_chr">Mikrotik CHR (RouterOS API)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Subnet Alokasi Tunnel VPN *</label>
                            <input type="text" name="tunnel_subnet" class="form-control font-monospace" required value="10.88.0.0/24">
                            <small class="text-secondary">Contoh: 10.88.0.0/24 atau 10.99.0.0/24</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Rentang Port Forwarding Publik *</label>
                            <div class="input-group">
                                <input type="number" name="min_port" class="form-control font-monospace" value="10000" min="1024" max="65535" required>
                                <span class="input-group-text bg-dark border-secondary text-secondary">s/d</span>
                                <input type="number" name="max_port" class="form-control font-monospace" value="50000" min="1024" max="65535" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">WireGuard Server Port &amp; Interface</label>
                            <div class="input-group">
                                <span class="input-group-text bg-dark border-secondary text-secondary">Port:</span>
                                <input type="number" name="wg_port" class="form-control font-monospace" value="51820" required>
                                <span class="input-group-text bg-dark border-secondary text-secondary">Dev:</span>
                                <input type="text" name="wg_interface" class="form-control font-monospace" value="wg0" required>
                            </div>
                        </div>

                        <!-- Mikrotik CHR specific options -->
                        <div id="chrFields" class="col-12" style="display:none;">
                            <div class="p-3 rounded-3" style="background:#0b1528; border:1px solid #1a2a4a;">
                                <h6 class="text-info fw-bold mb-3"><i class="fa-solid fa-network-wired me-1"></i> Pengaturan RouterOS API Mikrotik CHR</h6>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">API Port</label>
                                        <input type="number" name="api_port" class="form-control font-monospace" value="8728">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Username API</label>
                                        <input type="text" name="api_user" class="form-control" placeholder="admin">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Password API</label>
                                        <input type="password" name="api_pass" class="form-control">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save me-1"></i> Simpan Gateway</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleGwFields() {
    const val = document.getElementById('gwTypeSelect').value;
    document.getElementById('chrFields').style.display = (val === 'mikrotik_chr') ? 'block' : 'none';
}
function testGateway(id) {
    alert('Menguji reachability gateway server #' + id + '...');
}
</script>

<?php require_once BASE_PATH . '/include/footer.php'; ?>

