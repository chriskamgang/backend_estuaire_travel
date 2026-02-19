<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BusTripController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\RideshareTripController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\SupportTicketController;
use App\Http\Controllers\Api\PushTokenController;
use App\Http\Controllers\Api\DriverApplicationController;
use App\Http\Controllers\Api\DriverDashboardController;
use App\Http\Controllers\Api\VehicleController;
use App\Http\Controllers\Api\DriverTripController;
use App\Http\Controllers\Api\PassengerRideshareController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\WalletWebhookController;
use App\Http\Controllers\Api\PayPalController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public Auth Routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    Route::post('/send-phone-verification', [AuthController::class, 'sendPhoneVerification']);
    Route::post('/verify-phone', [AuthController::class, 'verifyPhone']);
});

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });

    // User Profile
    Route::prefix('user')->group(function () {
        Route::get('/profile', [UserController::class, 'profile']);
        Route::put('/profile', [UserController::class, 'updateProfile']);
        Route::post('/avatar', [UserController::class, 'uploadAvatar']);
        Route::get('/bookings', [UserController::class, 'myBookings']);
        Route::get('/loyalty', [UserController::class, 'loyaltyInfo']);
    });

    // Profile Management (New Routes)
    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class, 'show']);
        Route::post('/update', [ProfileController::class, 'update']); // POST pour supporter multipart/form-data
        Route::put('/update', [ProfileController::class, 'update']); // PUT pour JSON simple
        Route::post('/change-password', [ProfileController::class, 'changePassword']);
        Route::post('/update-language', [ProfileController::class, 'updateLanguage']);
        Route::post('/update-notification-preferences', [ProfileController::class, 'updateNotificationPreferences']);
        Route::delete('/delete-account', [ProfileController::class, 'deleteAccount']);
    });

    // Bus Trips
    Route::prefix('bus-trips')->group(function () {
        Route::get('/', [BusTripController::class, 'index']);
        Route::get('/search', [BusTripController::class, 'search']);
        Route::get('/{id}', [BusTripController::class, 'show']);
        Route::get('/{id}/booked-seats', [BusTripController::class, 'getBookedSeats']);
    });

    // Bookings
    Route::prefix('bookings')->group(function () {
        Route::get('/', [BookingController::class, 'index']);
        Route::post('/', [BookingController::class, 'store']);
        Route::get('/{id}', [BookingController::class, 'show']);
        Route::put('/{id}/cancel', [BookingController::class, 'cancel']);
    });

    // Rideshare Trips
    Route::prefix('rideshares')->group(function () {
        Route::get('/', [RideshareTripController::class, 'index']);
        Route::get('/search', [RideshareTripController::class, 'search']);
        Route::post('/', [RideshareTripController::class, 'store'])->middleware('can:create_rideshares');
        Route::get('/{id}', [RideshareTripController::class, 'show']);
        Route::put('/{id}', [RideshareTripController::class, 'update'])->middleware('can:edit_rideshares');
        Route::delete('/{id}', [RideshareTripController::class, 'destroy'])->middleware('can:delete_rideshares');
        Route::get('/my-trips', [RideshareTripController::class, 'myTrips']);
    });

    // Favorites
    Route::prefix('favorites')->group(function () {
        Route::get('/', [FavoriteController::class, 'index']);
        Route::post('/', [FavoriteController::class, 'store']);
        Route::delete('/{id}', [FavoriteController::class, 'destroy']);
        Route::post('/check', [FavoriteController::class, 'check']);
    });

    // Notifications
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::post('/', [NotificationController::class, 'store']); // Créer et envoyer une notification
        Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
        Route::post('/{id}/mark-as-read', [NotificationController::class, 'markAsRead']);
        Route::post('/mark-all-as-read', [NotificationController::class, 'markAllAsRead']);
        Route::delete('/{id}', [NotificationController::class, 'destroy']);
        Route::delete('/read/all', [NotificationController::class, 'deleteAllRead']);
    });

    // Support Tickets
    Route::prefix('support')->group(function () {
        Route::get('/', [SupportTicketController::class, 'index']);
        Route::post('/', [SupportTicketController::class, 'store']);
        Route::get('/{id}', [SupportTicketController::class, 'show']);
        Route::post('/{id}/close', [SupportTicketController::class, 'close']);
        Route::delete('/{id}', [SupportTicketController::class, 'destroy']);
    });

    // Push Tokens
    Route::prefix('push-tokens')->group(function () {
        Route::get('/', [PushTokenController::class, 'index']);
        Route::post('/', [PushTokenController::class, 'store']);
        Route::delete('/', [PushTokenController::class, 'destroy']);
    });

    // ========== COVOITURAGE CÔTÉ CLIENT ==========

    // Recherche et découverte de trajets covoiturage
    Route::prefix('rideshare')->group(function () {
        // Routes statiques en premier (avant /{id} qui capture tout)
        Route::get('/search',  [RideshareTripController::class, 'search']);    // Rechercher un trajet
        Route::get('/today',   [RideshareTripController::class, 'today']);     // Trajets dispo aujourd'hui
        Route::get('/popular', [RideshareTripController::class, 'popular']);   // Trajets populaires
        Route::get('/nearby',  [RideshareTripController::class, 'nearby']);    // Proches de moi (lat/lng)

        // Réservations passager (routes statiques — avant /{id})
        Route::post('/book',                         [PassengerRideshareController::class, 'book']);          // Réserver
        Route::get('/my-bookings',                   [PassengerRideshareController::class, 'myBookings']);    // Mes réservations
        Route::get('/upcoming',                      [PassengerRideshareController::class, 'upcoming']);      // À venir
        Route::get('/my-bookings/{id}',              [PassengerRideshareController::class, 'showBooking']);   // Détails réservation
        Route::post('/my-bookings/{id}/cancel',      [PassengerRideshareController::class, 'cancelBooking']); // Annuler

        // Route dynamique en dernier (sinon elle capture les routes statiques)
        Route::get('/{id}',    [RideshareTripController::class, 'show']);      // Détails d'un trajet
    });

    // Driver Applications
    Route::prefix('driver-applications')->group(function () {
        Route::get('/', [DriverApplicationController::class, 'index']); // Obtenir la demande de l'utilisateur
        Route::post('/', [DriverApplicationController::class, 'store']); // Soumettre une demande
        Route::put('/{id}', [DriverApplicationController::class, 'update']); // Modifier une demande rejetée
        Route::delete('/{id}', [DriverApplicationController::class, 'destroy']); // Supprimer une demande rejetée
    });

    // Driver Routes (chauffeur approuvé requis)
    Route::middleware('driver')->group(function () {

        // Driver Dashboard
        Route::prefix('driver')->group(function () {
            Route::get('/dashboard/stats', [DriverDashboardController::class, 'getStats']);
            Route::get('/trips/active', [DriverDashboardController::class, 'getActiveTrips']);
            Route::get('/trips/history', [DriverDashboardController::class, 'getTripHistory']);
            Route::get('/bookings/pending', [DriverDashboardController::class, 'getPendingBookings']);
            Route::post('/location', [DriverDashboardController::class, 'updateLocation']);
            Route::post('/online-status', [DriverDashboardController::class, 'toggleOnlineStatus']);
        });

        // Vehicles (Mes véhicules)
        Route::prefix('vehicles')->group(function () {
            Route::get('/', [VehicleController::class, 'index']);
            Route::post('/', [VehicleController::class, 'store']);
            Route::get('/{id}', [VehicleController::class, 'show']);
            Route::put('/{id}', [VehicleController::class, 'update']);
            Route::post('/{id}', [VehicleController::class, 'update']); // POST pour multipart
            Route::delete('/{id}', [VehicleController::class, 'destroy']);
            Route::post('/{id}/toggle-active', [VehicleController::class, 'toggleActive']);
        });

        // Driver Trips (Proposer un trajet, Mes trajets)
        Route::prefix('driver/trips')->group(function () {
            Route::post('/', [DriverTripController::class, 'createTrip']);
            Route::get('/', [DriverTripController::class, 'myTrips']);
            Route::put('/{id}', [DriverTripController::class, 'updateTrip']);
            Route::post('/{id}/cancel', [DriverTripController::class, 'cancelTrip']);
            Route::post('/{id}/start', [DriverTripController::class, 'startTrip']);
            Route::post('/{id}/complete', [DriverTripController::class, 'completeTrip']);
        });

        // Driver Booking Requests
        Route::prefix('driver/booking-requests')->group(function () {
            Route::get('/', [DriverTripController::class, 'getBookingRequests']);
            Route::post('/{id}/accept', [DriverTripController::class, 'acceptBooking']);
            Route::post('/{id}/reject', [DriverTripController::class, 'rejectBooking']);
            Route::post('/check-in', [DriverTripController::class, 'checkInBooking']);
        });
    }); // fin middleware role:driver

    // ========== WALLET ==========
    Route::prefix('wallet')->group(function () {
        Route::get('/',                    [WalletController::class, 'show']);          // GET solde
        Route::get('/transactions',        [WalletController::class, 'transactions']);  // GET historique
        Route::post('/recharge',           [WalletController::class, 'recharge']);      // POST initier recharge Mobile Money
        Route::post('/recharge/check-status', [WalletController::class, 'checkRecharge']); // POST vérifier statut recharge
        Route::post('/transfer',           [WalletController::class, 'transfer']);      // POST transfert unique

        // ===== PAYPAL =====
        Route::post('/paypal/create-order',  [PayPalController::class, 'createOrder']);  // Créer commande PayPal
        Route::post('/paypal/check-status',  [PayPalController::class, 'checkStatus']);  // Polling statut PayPal
    });
}); // fin auth:sanctum

// ========== WEBHOOK FREEMOPAY (public — pas d'auth) ==========
Route::post('/webhook/freemopay', [WalletWebhookController::class, 'handle'])
    ->name('webhook.freemopay');

// ========== PAYPAL CALLBACKS (public — appelés par la WebView après paiement) ==========
Route::get('/wallet/paypal/success', [PayPalController::class, 'captureOrder'])->name('paypal.success');
Route::get('/wallet/paypal/cancel',  [PayPalController::class, 'cancelOrder'])->name('paypal.cancel');
