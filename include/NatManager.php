<?php
/**
 * VPN Tunnel & Remote RouterOS Gateway — NatManager
 * Pure PHP DST-NAT Port Forwarding Engine (Linux IPTables & Mikrotik CHR)
 */

if (!defined('IN_APP')) die('Direct access denied.');

class NatManager {

    /**
     * Terapkan rule DST-NAT di Linux VPS via iptables
     */
    public static function applyLinuxNatRule(string $publicIp, int $publicPort, string $targetIp, int $targetPort, string $protocol = 'tcp'): bool {
        $protos = ($protocol === 'both') ? ['tcp', 'udp'] : [$protocol];

        foreach ($protos as $proto) {
            // 1. DNAT Prerouting
            safe_shell_exec("sudo -n iptables -t nat -C PREROUTING -p {$proto} -d {$publicIp} --dport {$publicPort} -j DNAT --to-destination {$targetIp}:{$targetPort} 2>/dev/null || sudo -n iptables -t nat -A PREROUTING -p {$proto} -d {$publicIp} --dport {$publicPort} -j DNAT --to-destination {$targetIp}:{$targetPort}");

            // 2. Forward Filter
            safe_shell_exec("sudo -n iptables -C FORWARD -p {$proto} -d {$targetIp} --dport {$targetPort} -m state --state NEW,ESTABLISHED,RELATED -j ACCEPT 2>/dev/null || sudo -n iptables -A FORWARD -p {$proto} -d {$targetIp} --dport {$targetPort} -m state --state NEW,ESTABLISHED,RELATED -j ACCEPT");

            // 3. Masquerade Postrouting
            safe_shell_exec("sudo -n iptables -t nat -C POSTROUTING -p {$proto} -d {$targetIp} --dport {$targetPort} -j MASQUERADE 2>/dev/null || sudo -n iptables -t nat -A POSTROUTING -p {$proto} -d {$targetIp} --dport {$targetPort} -j MASQUERADE");
        }
        return true;
    }

    /**
     * Hapus rule DST-NAT di Linux VPS
     */
    public static function removeLinuxNatRule(string $publicIp, int $publicPort, string $targetIp, int $targetPort, string $protocol = 'tcp'): bool {
        $protos = ($protocol === 'both') ? ['tcp', 'udp'] : [$protocol];

        foreach ($protos as $proto) {
            safe_shell_exec("sudo -n iptables -t nat -D PREROUTING -p {$proto} -d {$publicIp} --dport {$publicPort} -j DNAT --to-destination {$targetIp}:{$targetPort} 2>/dev/null");
            safe_shell_exec("sudo -n iptables -D FORWARD -p {$proto} -d {$targetIp} --dport {$targetPort} -m state --state NEW,ESTABLISHED,RELATED -j ACCEPT 2>/dev/null");
            safe_shell_exec("sudo -n iptables -t nat -D POSTROUTING -p {$proto} -d {$targetIp} --dport {$targetPort} -j MASQUERADE 2>/dev/null");
        }
        return true;
    }

    /**
     * Terapkan rule DST-NAT di Mikrotik CHR via RouterOS API
     */
    public static function applyChrNatRule(array $server, int $publicPort, string $targetIp, int $targetPort, string $protocol = 'tcp', string $comment = ''): bool {
        $api = new RouterosAPI();
        if (!$api->connect($server['chr_api_host'] ?? $server['public_ip'], $server['chr_api_user'], $server['chr_api_password'], $server['chr_api_port'] ?? 8728)) {
            return false;
        }

        $protos = ($protocol === 'both') ? ['tcp', 'udp'] : [$protocol];
        foreach ($protos as $proto) {
            $api->comm('/ip/firewall/nat/add', [
                'chain'         => 'dstnat',
                'action'        => 'dst-nat',
                'to-addresses'  => $targetIp,
                'to-ports'      => (string)$targetPort,
                'protocol'      => $proto,
                'dst-port'      => (string)$publicPort,
                'comment'       => "VPN-NAT: " . $comment
            ]);
        }
        $api->disconnect();
        return true;
    }

    /**
     * Sinkronkan semua port forward aktif ke firewall
     */
    public static function syncAllActiveRules(): int {
        $rules = db_fetch_all("SELECT pf.*, t.tunnel_ip, s.public_ip, s.engine_type FROM port_forwards pf 
                                JOIN tunnels t ON pf.tunnel_id = t.id 
                                JOIN servers s ON t.server_id = s.id 
                                WHERE pf.is_enabled = 1 AND s.is_active = 1");
        $count = 0;
        foreach ($rules as $r) {
            if ($r['engine_type'] === 'linux_iptables') {
                self::applyLinuxNatRule($r['public_ip'], (int)$r['public_port'], $r['target_ip'], (int)$r['target_port'], $r['protocol']);
                $count++;
            }
        }
        return $count;
    }

    /**
     * Uji ketersediaan port secara live via socket connection
     */
    public static function checkPortStatus(string $host, int $port, int $timeout = 2): bool {
        $fp = @fsockopen($host, $port, $errno, $errstr, $timeout);
        if ($fp) {
            fclose($fp);
            return true;
        }
        return false;
    }
}

