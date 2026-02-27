<?php

/**
 * Script de test pour l'intégration Nexah SMS
 *
 * Usage: php test_otp.php
 */

// Charger l'autoloader Laravel
require __DIR__.'/vendor/autoload.php';

// Charger l'application Laravel
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\SmsService;

echo "=== Test Nexah SMS Integration ===\n\n";

// Créer une instance du service
$smsService = new SmsService();

// Test 1: Vérifier le solde de crédits
echo "1. Vérification du solde de crédits SMS...\n";
$creditsResult = $smsService->checkCredits();

if ($creditsResult['success']) {
    echo "✓ Connexion réussie à l'API Nexah\n";
    echo "Crédits disponibles: " . json_encode($creditsResult['credits']) . "\n\n";
} else {
    echo "✗ Échec de la connexion à l'API Nexah\n";
    echo "Erreur: " . $creditsResult['message'] . "\n\n";
    exit(1);
}

// Test 2: Générer un code OTP
echo "2. Génération d'un code OTP...\n";
$otp = SmsService::generateOtp(6);
echo "✓ Code OTP généré: {$otp}\n\n";

// Test 3: Tester le formatage des numéros
echo "3. Test du formatage des numéros de téléphone...\n";
$testNumbers = [
    '670000000',
    '0670000000',
    '237670000000',
    '+237670000000',
];

foreach ($testNumbers as $number) {
    // Utiliser reflection pour accéder à la méthode privée
    $reflection = new ReflectionClass($smsService);
    $method = $reflection->getMethod('cleanPhoneNumber');
    $method->setAccessible(true);
    $cleaned = $method->invoke($smsService, $number);
    echo "  {$number} → {$cleaned}\n";
}
echo "\n";

// Test 4: Envoyer un SMS de test (optionnel - décommentez pour tester)
/*
echo "4. Envoi d'un SMS de test...\n";
echo "Entrez un numéro de téléphone pour recevoir un SMS de test: ";
$phoneNumber = trim(fgets(STDIN));

if (!empty($phoneNumber)) {
    $testOtp = SmsService::generateOtp(6);
    $result = $smsService->sendOtp($phoneNumber, $testOtp);

    if ($result['success']) {
        echo "✓ SMS envoyé avec succès!\n";
        echo "Code OTP envoyé: {$testOtp}\n";
    } else {
        echo "✗ Échec de l'envoi du SMS\n";
        echo "Erreur: " . $result['message'] . "\n";
    }
}
*/

echo "=== Tests terminés ===\n";
