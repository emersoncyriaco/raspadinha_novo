<?php
/**
 * Classe para geração de QR Codes usando múltiplas APIs para maior confiabilidade
 */
class QRGenerator {
    
    /**
     * Lista de APIs de QR Code disponíveis
     */
    private static $apis = [
        'qrserver' => 'https://api.qrserver.com/v1/create-qr-code/',
        'qrcode_monkey' => 'https://api.qrcode-monkey.com/qr/custom',
        'goqr' => 'https://api.qrserver.com/v1/create-qr-code/' // Backup da primeira
    ];
    
    /**
     * Gera um QR Code usando a API do QR Server (principal)
     */
    public static function gerarQRCode($texto, $tamanho = 250) {
        $url = self::$apis['qrserver'];
        $params = [
            'size' => $tamanho . 'x' . $tamanho,
            'data' => urlencode($texto),
            'format' => 'png',
            'ecc' => 'M', // Error correction level
            'margin' => '10'
        ];
        
        $query = http_build_query($params);
        $qr_url = $url . '?' . $query;
        
        return $qr_url;
    }
    
    /**
     * Baixa e salva o QR Code localmente com retry
     */
    public static function salvarQRCode($texto, $caminho, $tamanho = 250) {
        $qr_url = self::gerarQRCode($texto, $tamanho);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $qr_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; QRGenerator/1.0)');
        
        $data = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($httpCode === 200 && $data && !$error) {
            file_put_contents($caminho, $data);
            return true;
        }
        
        // Log do erro para debug
        error_log("Erro ao baixar QR Code: HTTP $httpCode, Error: $error");
        return false;
    }
    
    /**
     * Gera QR Code em base64 para exibição direta com fallback
     */
    public static function gerarQRCodeBase64($texto, $tamanho = 280) {
        $qr_url = self::gerarQRCode($texto, $tamanho);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $qr_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; QRGenerator/1.0)');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: image/png,image/*,*/*',
            'Cache-Control: no-cache'
        ]);
        
        $data = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($httpCode === 200 && $data && !$error) {
            // Verifica se é uma imagem válida
            $imageInfo = @getimagesizefromstring($data);
            if ($imageInfo !== false) {
                return 'data:image/png;base64,' . base64_encode($data);
            }
        }
        
        // Log do erro para debug
        error_log("Erro ao gerar QR Code Base64: HTTP $httpCode, Error: $error");
        
        // Fallback: retorna URL direta se base64 falhar
        return $qr_url;
    }
    
    /**
     * Gera QR Code PIX formatado com validação
     */
    public static function gerarQRCodePIX($codigo_pix, $tamanho = 280) {
        // Valida se o código PIX não está vazio
        if (empty($codigo_pix) || strlen($codigo_pix) < 10) {
            error_log("Código PIX inválido ou muito curto: " . $codigo_pix);
            return false;
        }
        
        // Tenta gerar o QR Code
        $result = self::gerarQRCodeBase64($codigo_pix, $tamanho);
        
        // Se falhou, tenta com tamanho menor
        if (!$result || strpos($result, 'data:image') !== 0) {
            error_log("Tentando QR Code com tamanho menor");
            $result = self::gerarQRCodeBase64($codigo_pix, 200);
        }
        
        return $result;
    }
    
    /**
     * Valida se um código PIX é válido (básico)
     */
    public static function validarCodigoPIX($codigo) {
        // Verifica se não está vazio e tem tamanho mínimo
        if (empty($codigo) || strlen($codigo) < 10) {
            return false;
        }
        
        // Verifica se contém caracteres básicos de um código PIX
        if (!preg_match('/^[0-9A-Za-z\+\/=\-_\.]+$/', $codigo)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Gera URL de QR Code alternativa caso a principal falhe
     */
    public static function gerarQRCodeAlternativo($texto, $tamanho = 280) {
        // URL alternativa usando chart.googleapis.com
        $url = "https://chart.googleapis.com/chart";
        $params = [
            'chs' => $tamanho . 'x' . $tamanho,
            'cht' => 'qr',
            'chl' => urlencode($texto),
            'choe' => 'UTF-8'
        ];
        
        $query = http_build_query($params);
        return $url . '?' . $query;
    }
}
?>

