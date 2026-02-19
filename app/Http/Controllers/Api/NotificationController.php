<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Services\ExpoPushNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    /**
     * Récupérer toutes les notifications de l'utilisateur
     */
    public function index()
    {
        $user = Auth::user();

        $notifications = Notification::where('user_id', $user->id)
            ->latest()
            ->get();

        // Compter les notifications non lues
        $unreadCount = Notification::where('user_id', $user->id)
            ->unread()
            ->count();

        return response()->json([
            'success' => true,
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * Obtenir le nombre de notifications non lues
     */
    public function unreadCount()
    {
        $user = Auth::user();

        $count = Notification::where('user_id', $user->id)
            ->unread()
            ->count();

        return response()->json([
            'success' => true,
            'unread_count' => $count,
        ]);
    }

    /**
     * Marquer une notification comme lue
     */
    public function markAsRead(string $id)
    {
        $user = Auth::user();

        $notification = Notification::where('user_id', $user->id)
            ->where('id', $id)
            ->first();

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification non trouvée',
            ], 404);
        }

        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Notification marquée comme lue',
            'notification' => $notification,
        ]);
    }

    /**
     * Marquer toutes les notifications comme lues
     */
    public function markAllAsRead()
    {
        $user = Auth::user();

        Notification::where('user_id', $user->id)
            ->unread()
            ->update([
                'read' => true,
                'read_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Toutes les notifications ont été marquées comme lues',
        ]);
    }

    /**
     * Supprimer une notification
     */
    public function destroy(string $id)
    {
        $user = Auth::user();

        $notification = Notification::where('user_id', $user->id)
            ->where('id', $id)
            ->first();

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification non trouvée',
            ], 404);
        }

        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notification supprimée',
        ]);
    }

    /**
     * Supprimer toutes les notifications lues
     */
    public function deleteAllRead()
    {
        $user = Auth::user();

        $deletedCount = Notification::where('user_id', $user->id)
            ->where('read', true)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => "$deletedCount notification(s) supprimée(s)",
            'deleted_count' => $deletedCount,
        ]);
    }

    /**
     * Créer et envoyer une notification (pour tests ou usage admin)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'type' => 'required|string',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'data' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation échouée',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $pushService = new ExpoPushNotificationService();

            $result = $pushService->createAndSendNotification(
                $request->user_id,
                $request->type,
                $request->title,
                $request->message,
                $request->data ?? []
            );

            return response()->json([
                'success' => true,
                'message' => 'Notification créée et envoyée',
                'notification' => $result['notification'],
                'push_sent' => $result['push_result']['sent_count'] > 0,
                'push_result' => $result['push_result'],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la notification',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
