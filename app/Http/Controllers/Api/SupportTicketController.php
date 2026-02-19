<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SupportTicketController extends Controller
{
    /**
     * Récupérer tous les tickets de support de l'utilisateur
     */
    public function index()
    {
        $user = Auth::user();

        $tickets = SupportTicket::where('user_id', $user->id)
            ->latest()
            ->get();

        // Compter les tickets ouverts
        $openCount = SupportTicket::where('user_id', $user->id)
            ->open()
            ->count();

        return response()->json([
            'success' => true,
            'tickets' => $tickets,
            'open_count' => $openCount,
        ]);
    }

    /**
     * Créer un nouveau ticket de support
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
            'category' => 'required|string|in:technique,reservation,paiement,autre',
            'priority' => 'nullable|string|in:low,medium,high',
        ]);

        $ticket = SupportTicket::create([
            'user_id' => $user->id,
            'subject' => $validated['subject'],
            'message' => $validated['message'],
            'category' => $validated['category'],
            'priority' => $validated['priority'] ?? 'medium',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Ticket créé avec succès',
            'ticket' => $ticket,
        ], 201);
    }

    /**
     * Afficher un ticket spécifique
     */
    public function show(string $id)
    {
        $user = Auth::user();

        $ticket = SupportTicket::where('user_id', $user->id)
            ->where('id', $id)
            ->first();

        if (!$ticket) {
            return response()->json([
                'success' => false,
                'message' => 'Ticket non trouvé',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'ticket' => $ticket,
        ]);
    }

    /**
     * Fermer un ticket
     */
    public function close(string $id)
    {
        $user = Auth::user();

        $ticket = SupportTicket::where('user_id', $user->id)
            ->where('id', $id)
            ->first();

        if (!$ticket) {
            return response()->json([
                'success' => false,
                'message' => 'Ticket non trouvé',
            ], 404);
        }

        $ticket->update([
            'status' => 'closed',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Ticket fermé',
            'ticket' => $ticket,
        ]);
    }

    /**
     * Supprimer un ticket
     */
    public function destroy(string $id)
    {
        $user = Auth::user();

        $ticket = SupportTicket::where('user_id', $user->id)
            ->where('id', $id)
            ->first();

        if (!$ticket) {
            return response()->json([
                'success' => false,
                'message' => 'Ticket non trouvé',
            ], 404);
        }

        $ticket->delete();

        return response()->json([
            'success' => true,
            'message' => 'Ticket supprimé',
        ]);
    }
}
