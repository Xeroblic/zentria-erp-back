<?php

echo "=== Prueba de actualización de empresa ===\n";

// Simular datos de actualización (los que estás enviando desde el frontend)
$companyData = [
    'company_name' => 'EcoTech SPA',
    'company_rut' => '76795560-9',
    'business_activity' => 'Soluciones tecnológicas y servicios informáticos'
];

// URL del endpoint
$url = 'http://127.0.0.1:8000/api/companies/1';

// Obtener un token válido primero
echo "Obteniendo token de autenticación...\n";

// Login para obtener token
$loginData = [
    'email' => 'rbarrientos@tikinet.cl', // Usuario válido de la BD
    'password' => 'password123' // Cambia por la contraseña correcta
];

$loginCh = curl_init();
curl_setopt($loginCh, CURLOPT_URL, 'http://127.0.0.1:8000/api/login');
curl_setopt($loginCh, CURLOPT_POST, true);
curl_setopt($loginCh, CURLOPT_POSTFIELDS, json_encode($loginData));
curl_setopt($loginCh, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($loginCh, CURLOPT_RETURNTRANSFER, true);
curl_setopt($loginCh, CURLOPT_SSL_VERIFYPEER, false);

$loginResponse = curl_exec($loginCh);
$loginHttpCode = curl_getinfo($loginCh, CURLINFO_HTTP_CODE);
curl_close($loginCh);

echo "Login HTTP Code: $loginHttpCode\n";

if ($loginHttpCode !== 200) {
    echo "No se pudo obtener token. Respuesta: $loginResponse\n";
    exit(1);
}

$loginData = json_decode($loginResponse, true);
$token = $loginData['access_token'] ?? null;

if (!$token) {
    echo "Token no encontrado en la respuesta de login\n";
    exit(1);
}

echo "Token obtenido exitosamente\n";

// Headers para la petición de actualización
$headers = [
    'Content-Type: application/json',
    'Accept: application/json',
    'Authorization: Bearer ' . $token
];

// Configurar cURL para PATCH
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($companyData));
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

echo "Enviando PATCH a: $url\n";
echo "Datos: " . json_encode($companyData, JSON_PRETTY_PRINT) . "\n";

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "Código HTTP: $httpCode\n";
if ($error) {
    echo "Error cURL: $error\n";
}
echo "Respuesta: $response\n";
