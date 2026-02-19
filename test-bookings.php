<?php

$baseUrl = 'http://localhost:8001/api';

// 1. Login
echo "=== TEST 1: Login ===\n";
$ch = curl_init("$baseUrl/auth/login");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'phone' => '237690123456',
    'password' => 'password'
]));
$response = curl_exec($ch);
$loginData = json_decode($response, true);
$token = $loginData['data']['token'] ?? null;

if ($token) {
    echo "✅ Login réussi!\n\n";
} else {
    echo "❌ Échec login\n";
    echo "Response: $response\n";
    exit(1);
}

// 2. Liste des réservations
echo "=== TEST 2: Liste des réservations ===\n";
$ch = curl_init("$baseUrl/bookings");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    "Authorization: Bearer $token"
]);
$response = curl_exec($ch);
echo "Response: $response\n";
$bookingsData = json_decode($response, true);

if ($bookingsData['success'] ?? false) {
    $count = count($bookingsData['data']['data'] ?? []);
    echo "✅ Réservations récupérées: $count réservation(s)\n";
    if ($count > 0) {
        $booking = $bookingsData['data']['data'][0];
        echo "   Première réservation:\n";
        echo "   - Référence: {$booking['booking_reference']}\n";
        echo "   - Status: {$booking['status']}\n";
        echo "   - Date: {$booking['travel_date']}\n";
    }
}
echo "\n";

echo "=== Tests terminés ===\n";
