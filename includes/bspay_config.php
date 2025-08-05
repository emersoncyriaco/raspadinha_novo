<?php
class BSPayConfig {
    // Credenciais BSPay - PRODUÇÃO
    private static $client_id = 'stive22_2720678698341309';
    private static $client_secret = 'dd05180ef15e72898a61309fcbcef67ef3a67e8ade2c1c13e94f641893706791';
    private static $webhook_url = 'https://raspoupixbr.site/webhook_bspay_novo.php';
    
    public static function getClientId() {
        return self::$client_id;
    }
    
    public static function getClientSecret() {
        return self::$client_secret;
    }
    
    public static function getWebhookUrl() {
        return self::$webhook_url;
    }
    
    public static function setClientId($client_id) {
        self::$client_id = $client_id;
    }
    
    public static function setClientSecret($client_secret) {
        self::$client_secret = $client_secret;
    }
    
    public static function setWebhookUrl($webhook_url) {
        self::$webhook_url = $webhook_url;
    }
}
?>
