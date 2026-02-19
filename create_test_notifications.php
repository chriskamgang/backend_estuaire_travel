<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Notification;

// Récupérer le premier utilisateur
$user = User::first();

if (!$user) {
    echo "❌ Aucun utilisateur trouvé. Créez d'abord un compte.\n";
    exit(1);
}

echo "✅ Création de notifications pour {$user->name} (ID: {$user->id})\n\n";

// Notification 1: Confirmation de réservation
Notification::create([
    'user_id' => $user->id,
    'type' => 'booking_confirmed',
    'title' => 'Réservation confirmée',
    'message' => 'Votre réservation pour Yaoundé → Douala a été confirmée. Bon voyage!',
    'data' => json_encode(['booking_id' => 1]),
    'read' => false,
]);
echo "✅ Notification 1 créée: Réservation confirmée\n";

// Notification 2: Nouvelle offre
Notification::create([
    'user_id' => $user->id,
    'type' => 'new_offer',
    'title' => 'Offre spéciale!',
    'message' => 'Profitez de -20% sur tous les trajets ce week-end!',
    'data' => json_encode(['promo_code' => 'WEEKEND20']),
    'read' => false,
]);
echo "✅ Notification 2 créée: Offre spéciale\n";

// Notification 3: Rappel de voyage
Notification::create([
    'user_id' => $user->id,
    'type' => 'trip_reminder',
    'title' => 'Rappel: Voyage demain',
    'message' => 'N\'oubliez pas votre voyage Yaoundé → Douala demain à 08:00. Bon voyage!',
    'data' => json_encode(['booking_id' => 1, 'departure_time' => '08:00']),
    'read' => false,
]);
echo "✅ Notification 3 créée: Rappel de voyage\n";

// Notification 4: Réservation annulée (déjà lue)
Notification::create([
    'user_id' => $user->id,
    'type' => 'booking_cancelled',
    'title' => 'Réservation annulée',
    'message' => 'Votre réservation pour Douala → Yaoundé a été annulée. Vous avez été remboursé.',
    'data' => json_encode(['booking_id' => 2]),
    'read' => true,
    'read_at' => now(),
]);
echo "✅ Notification 4 créée: Réservation annulée (lue)\n";

$unreadCount = Notification::where('user_id', $user->id)->where('read', false)->count();
echo "\n🎉 Terminé! {$unreadCount} notifications non lues créées.\n";
