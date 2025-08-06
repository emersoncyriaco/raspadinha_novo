<?php
class BSPayConfig {
    // Credenciais BSPay - PRODUÇÃO
    private static $client_id = 'emersoncyriaco_2901109771229168';
    private static $client_secret = '0fb8f1d16c74dce41a4121114b6bc82cbbd8e3e7d2a712f03176e67ffa7aa1e9';
    private static $webhook_url = 'https://www.spacevegas.site/webhook_bspay_novo.php';
    
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
