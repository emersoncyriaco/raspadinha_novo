<?php
ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);
error_reporting(E_ALL);

function obterTokenBSPay() {
    $client_id = "profelar2025_3007001185578910";
    $client_secret = "dddb10361aa6d93746c63c9c177b15ca875bbe7e4c30cb270e92c0e344c39d0a";

    $data = [
        "client_id" => $client_id,
        "client_secret" => $client_secret
    ];

    $ch = curl_init("https://api.pixupbr.com/v2/oauth/token");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
        CURLOPT_POSTFIELDS => json_encode($data)
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        error_log("Erro cURL ao obter token: " . $error);
        return false;
    }

    $responseData = json_decode($response, true);

    if ($httpCode !== 200 || !isset($responseData["token"])) {
        error_log("Erro na resposta da API BSPay (HTTP {$httpCode}): " . $response);
        return false;
    }

    return $responseData["token"];
}
?>

