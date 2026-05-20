<?php
$url = 'https://api.themoviedb.org/3/configuration?api_key=821ea9d15d241b2899c1b0a33cdf107a';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
$error = curl_error($ch);
curl_close($ch);

if ($error) echo "❌ Error curl: " . $error;
elseif ($response) echo "✅ OK: " . substr($response, 0, 100);
else echo "❌ Sin respuesta";
?>