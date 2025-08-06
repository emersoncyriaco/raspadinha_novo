<?php
class BSPayConfig {
    // Credenciais BSPay - PRODUÇÃO
    private static $client_id = 'emersoncyriaco_4877065027429403';
    private static $client_secret = 'f9d9b8ff96d4c12d1e5c0fa829807e7d6a6ac32a86db8d19257a6ade53b3e67e';
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
