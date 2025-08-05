<?php
$ch = curl_init("https://www.google.com");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$res = curl_exec($ch);
$erro = curl_error($ch);
curl_close($ch);

if ($res) {
  echo "✅ cURL funcionando";
} else {
  echo "❌ Erro no cURL: $erro";
}
