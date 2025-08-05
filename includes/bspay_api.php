<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

class BSPayAPI {
    private $client_id;
    private $client_secret;
    private $base_url = "https://api.pixupbr.com/v2";
    private $token = null;
    private $token_expires = null;

    public function __construct($client_id, $client_secret) {
        $this->client_id = $client_id;
        $this->client_secret = $client_secret;
    }

    /**
     * Obtém o token de acesso da API BSPay
     */
    public function obterToken() {
        // Verifica se o token ainda é válido
        if ($this->token && $this->token_expires && time() < $this->token_expires) {
            return $this->token;
        }

        $credentials = base64_encode($this->client_id . ':' . $this->client_secret);
        
        $ch = curl_init($this->base_url . "/oauth/token");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: Basic " . $credentials,
                "Content-Type: application/json",
                "Accept: application/json"
            ]
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception("Erro cURL: " . $error);
        }

        if ($httpCode !== 200) {
            throw new Exception("Erro HTTP: " . $httpCode . " - " . $response);
        }

        $data = json_decode($response, true);
        
        if (!$data || !isset($data['access_token'])) {
            throw new Exception("Resposta inválida da API: " . $response);
        }

        $this->token = $data['access_token'];
        // Define expiração para 1 hora (padrão da maioria das APIs)
        $this->token_expires = time() + 3600;

        return $this->token;
    }

    /**
     * Gera um QR Code PIX para pagamento
     */
    public function gerarQRCode($dados) {
        $token = $this->obterToken();

        $payload = [
            'amount' => $dados['amount'],
            'external_id' => $dados['external_id'],
            'payerQuestion' => $dados['payerQuestion'] ?? '',
            'payer' => [
                'name' => $dados['payer']['name'],
                'document' => $dados['payer']['document'],
                'email' => $dados['payer']['email']
            ],
            'postbackUrl' => $dados['postbackUrl']
        ];

        $ch = curl_init($this->base_url . "/pix/qrcode");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer " . $token,
                "Content-Type: application/json",
                "Accept: application/json"
            ],
            CURLOPT_POSTFIELDS => json_encode($payload)
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception("Erro cURL: " . $error);
        }

        if ($httpCode !== 200) {
            throw new Exception("Erro HTTP: " . $httpCode . " - " . $response);
        }

        $data = json_decode($response, true);
        
        if (!$data) {
            throw new Exception("Resposta inválida da API: " . $response);
        }

        return $data;
    }

    /**
     * Consulta o saldo da conta
     */
    public function consultarSaldo() {
        $token = $this->obterToken();

        $ch = curl_init($this->base_url . "/balance");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer " . $token,
                "Content-Type: application/json",
                "Accept: application/json"
            ]
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception("Erro cURL: " . $error);
        }

        if ($httpCode !== 200) {
            throw new Exception("Erro HTTP: " . $httpCode . " - " . $response);
        }

        $data = json_decode($response, true);
        
        if (!$data) {
            throw new Exception("Resposta inválida da API: " . $response);
        }

        return $data;
    }

    /**
     * Consulta uma transação específica
     */
    public function consultarTransacao($external_id) {
        $token = $this->obterToken();

        $payload = [
            'external_id' => $external_id
        ];

        $ch = curl_init($this->base_url . "/transaction/status");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer " . $token,
                "Content-Type: application/json",
                "Accept: application/json"
            ],
            CURLOPT_POSTFIELDS => json_encode($payload)
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception("Erro cURL: " . $error);
        }

        if ($httpCode !== 200) {
            throw new Exception("Erro HTTP: " . $httpCode . " - " . $response);
        }

        $data = json_decode($response, true);
        
        if (!$data) {
            throw new Exception("Resposta inválida da API: " . $response);
        }

        return $data;
    }

    /**
     * Fazer um pagamento
     */
    public function fazerPagamento($dados) {
        $token = $this->obterToken();

        $payload = [
            'amount' => $dados['amount'],
            'external_id' => $dados['external_id'],
            'recipient' => $dados['recipient'],
            'description' => $dados['description'] ?? ''
        ];

        $ch = curl_init($this->base_url . "/payment");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer " . $token,
                "Content-Type: application/json",
                "Accept: application/json"
            ],
            CURLOPT_POSTFIELDS => json_encode($payload)
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception("Erro cURL: " . $error);
        }

        if ($httpCode !== 200) {
            throw new Exception("Erro HTTP: " . $httpCode . " - " . $response);
        }

        $data = json_decode($response, true);
        
        if (!$data) {
            throw new Exception("Resposta inválida da API: " . $response);
        }

        return $data;
    }
}
?>

