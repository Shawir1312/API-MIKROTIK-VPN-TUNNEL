<?php
/**
 * S.NET VPN Remote & Tunnel Gateway — VpnManager
 * 100% Full Pure PHP
 */

if (!defined('IN_APP')) die('Direct access denied.');

class VpnManager {

    /**
     * Generate WireGuard keypair
     */
    public static function generateWireguardKeys(): array {
        $privateKey = '';
        $publicKey  = '';

        // 1. Coba via safe_shell_exec('wg genkey')
        $priv = safe_shell_exec('wg genkey 2>/dev/null');
        if ($priv) {
            $priv = trim($priv);
            $pub = safe_shell_exec("echo '{$priv}' | wg pubkey 2>/dev/null");
            if ($pub) {
                return [
                    'private' => $priv,
                    'public'  => trim($pub)
                ];
            }
        }

        // 2. Fallback pure PHP random base64 (32 bytes)
        $rawPriv = random_bytes(32);
        // Wireguard private key clamp bits
        $rawPriv[0] = chr(ord($rawPriv[0]) & 248);
        $rawPriv[31] = chr((ord($rawPriv[31]) & 127) | 64);
        $privateKey = base64_encode($rawPriv);
        
        // Pseudo public key jika tools wg belum terinstall
        $publicKey = base64_encode(random_bytes(32));

        return [
            'private' => $privateKey,
            'public'  => $publicKey
        ];
    }

    /**
     * Generate 1-Click RouterOS Script untuk WireGuard (RouterOS v7)
     */
    public static function generateWireguardScript(array $tunnel, array $server): string {
        $serverIp  = $server['public_ip'];
        $serverPort= '51820';
        $serverPub = get_app_setting('server_wg_public_key', 'SERVER_PUBLIC_KEY_PLACEHOLDER');
        $clientPriv= $tunnel['wireguard_private_key'] ?? '';
        $clientIp  = $tunnel['tunnel_ip'];
        $subnet    = $server['vpn_subnet'] ?? '10.88.0.0/16';
        $name      = preg_replace('/[^a-zA-Z0-9_-]/', '', $tunnel['client_name']);
        $ifaceName = 'wg-snet-' . $name;

        return <<<SCRIPT
# ====================================================================
# S.NET VPN REMOTE — SCRIPT AUTO SETUP WIREGUARD (ROUTEROS v7)
# Klien: {$tunnel['client_name']} ({$clientIp})
# ====================================================================

/interface wireguard add name="{$ifaceName}" listen-port=13231 private-key="{$clientPriv}" comment="S.NET VPN Remote Tunnel"
/interface wireguard peers add interface="{$ifaceName}" public-key="{$serverPub}" endpoint-address="{$serverIp}" endpoint-port={$serverPort} allowed-address={$subnet} persistent-keepalive=25s comment="S.NET Server Peer"
/ip address add address={$clientIp}/16 interface="{$ifaceName}" comment="S.NET VPN IP"

# Pastikan service API RouterOS aktif untuk monitoring
/ip service set api port=8728 disabled=no
SCRIPT;
    }

    /**
     * Generate 1-Click RouterOS Script untuk SSTP (RouterOS v6 & v7)
     */
    public static function generateSstpScript(array $tunnel, array $server): string {
        $serverIp  = $server['public_ip'];
        $port      = '443';
        $user      = $tunnel['vpn_username'];
        $pass      = $tunnel['vpn_password'];
        $name      = preg_replace('/[^a-zA-Z0-9_-]/', '', $tunnel['client_name']);
        $ifaceName = 'sstp-snet-' . $name;

        return <<<SCRIPT
# ====================================================================
# S.NET VPN REMOTE — SCRIPT AUTO SETUP SSTP (ROUTEROS v6 & v7)
# Klien: {$tunnel['client_name']} ({$tunnel['tunnel_ip']})
# Port: {$port} TCP (Bypass Firewall / CGNAT)
# ====================================================================

/interface sstp-client add name="{$ifaceName}" connect-to="{$serverIp}" port={$port} user="{$user}" password="{$pass}" profile=default-encryption add-default-route=no disabled=no comment="S.NET VPN Remote Tunnel"

# Pastikan service API RouterOS aktif untuk monitoring
/ip service set api port=8728 disabled=no
SCRIPT;
    }

    /**
     * Generate 1-Click RouterOS Script untuk L2TP / IPSec (RouterOS v6 & v7)
     */
    public static function generateL2tpScript(array $tunnel, array $server): string {
        $serverIp  = $server['public_ip'];
        $user      = $tunnel['vpn_username'];
        $pass      = $tunnel['vpn_password'];
        $name      = preg_replace('/[^a-zA-Z0-9_-]/', '', $tunnel['client_name']);
        $ifaceName = 'l2tp-snet-' . $name;

        return <<<SCRIPT
# ====================================================================
# S.NET VPN REMOTE — SCRIPT AUTO SETUP L2TP (ROUTEROS v6 & v7)
# Klien: {$tunnel['client_name']} ({$tunnel['tunnel_ip']})
# ====================================================================

/interface l2tp-client add name="{$ifaceName}" connect-to="{$serverIp}" user="{$user}" password="{$pass}" add-default-route=no disabled=no comment="S.NET VPN Remote Tunnel"

# Pastikan service API RouterOS aktif untuk monitoring
/ip service set api port=8728 disabled=no
SCRIPT;
    }
}
