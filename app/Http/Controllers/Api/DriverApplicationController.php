<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DriverApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class DriverApplicationController extends Controller
{
    /**
     * Obtenir la demande de l'utilisateur connecté
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $application = DriverApplication::where('user_id', $user->id)
            ->with(['reviewer:id,name,email'])
            ->latest()
            ->first();

        if (!$application) {
            return response()->json([
                'success' => true,
                'application' => null,
                'message' => 'Aucune demande trouvée',
            ]);
        }

        // Ajouter les URLs complètes des documents
        $application->id_card_front_url = $application->getDocumentUrl('id_card_front');
        $application->id_card_back_url = $application->getDocumentUrl('id_card_back');
        $application->driver_license_front_url = $application->getDocumentUrl('driver_license_front');
        $application->driver_license_back_url = $application->getDocumentUrl('driver_license_back');

        return response()->json([
            'success' => true,
            'application' => $application,
        ]);
    }

    /**
     * Soumettre une nouvelle demande
     */
    public function store(Request $request)
    {
        $user = $request->user();

        // Vérifier si l'utilisateur a déjà une demande en attente ou approuvée
        $existingApplication = DriverApplication::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'approved'])
            ->first();

        if ($existingApplication) {
            return response()->json([
                'success' => false,
                'message' => $existingApplication->status === 'approved'
                    ? 'Vous êtes déjà chauffeur approuvé'
                    : 'Vous avez déjà une demande en cours',
            ], 422);
        }

        // Validation
        $validator = Validator::make($request->all(), [
            'id_card_front' => 'required|image|mimes:jpeg,png,jpg|max:5120', // 5MB
            'id_card_back' => 'required|image|mimes:jpeg,png,jpg|max:5120',
            'driver_license_front' => 'required|image|mimes:jpeg,png,jpg|max:5120',
            'driver_license_back' => 'required|image|mimes:jpeg,png,jpg|max:5120',
            'vehicle_photo' => 'required|image|mimes:jpeg,png,jpg|max:5120',
            'license_number' => 'nullable|string|max:50',
            'license_expiry_date' => 'nullable|date|after:today',
            'additional_info' => 'nullable|string|max:1000',
        ], [
            'id_card_front.required' => 'La carte d\'identité (recto) est requise',
            'id_card_back.required' => 'La carte d\'identité (verso) est requise',
            'driver_license_front.required' => 'Le permis de conduire (recto) est requis',
            'driver_license_back.required' => 'Le permis de conduire (verso) est requis',
            'vehicle_photo.required' => 'La photo du véhicule est requise',
            '*.image' => 'Le fichier doit être une image',
            '*.mimes' => 'Format accepté: JPEG, PNG, JPG',
            '*.max' => 'Taille maximale: 5MB',
            'license_expiry_date.after' => 'Le permis doit être valide',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation échouée',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Upload des documents
            $idCardFront = $request->file('id_card_front')->store('driver_documents', 'public');
            $idCardBack = $request->file('id_card_back')->store('driver_documents', 'public');
            $licenseFront = $request->file('driver_license_front')->store('driver_documents', 'public');
            $licenseBack = $request->file('driver_license_back')->store('driver_documents', 'public');
            $vehiclePhoto = $request->file('vehicle_photo')->store('driver_documents', 'public');

            // Créer la demande
            $application = DriverApplication::create([
                'user_id' => $user->id,
                'id_card_front' => $idCardFront,
                'id_card_back' => $idCardBack,
                'driver_license_front' => $licenseFront,
                'driver_license_back' => $licenseBack,
                'vehicle_photo' => $vehiclePhoto,
                'license_number' => $request->license_number,
                'license_expiry_date' => $request->license_expiry_date,
                'additional_info' => $request->additional_info,
                'status' => 'pending',
                'submitted_at' => now(),
            ]);

            // Ajouter les URLs
            $application->id_card_front_url = $application->getDocumentUrl('id_card_front');
            $application->id_card_back_url = $application->getDocumentUrl('id_card_back');
            $application->driver_license_front_url = $application->getDocumentUrl('driver_license_front');
            $application->driver_license_back_url = $application->getDocumentUrl('driver_license_back');
            $application->vehicle_photo_url = $application->getDocumentUrl('vehicle_photo');

            return response()->json([
                'success' => true,
                'message' => 'Demande soumise avec succès. En attente d\'approbation.',
                'application' => $application,
            ], 201);

        } catch (\Exception $e) {
            // Nettoyer les fichiers uploadés en cas d'erreur
            if (isset($idCardFront)) Storage::disk('public')->delete($idCardFront);
            if (isset($idCardBack)) Storage::disk('public')->delete($idCardBack);
            if (isset($licenseFront)) Storage::disk('public')->delete($licenseFront);
            if (isset($licenseBack)) Storage::disk('public')->delete($licenseBack);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la soumission: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mettre à jour une demande rejetée
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();

        $application = DriverApplication::where('user_id', $user->id)
            ->where('id', $id)
            ->first();

        if (!$application) {
            return response()->json([
                'success' => false,
                'message' => 'Demande non trouvée',
            ], 404);
        }

        // Seules les demandes rejetées peuvent être modifiées
        if ($application->status !== 'rejected') {
            return response()->json([
                'success' => false,
                'message' => 'Seules les demandes rejetées peuvent être modifiées',
            ], 422);
        }

        // Validation (tous les champs optionnels pour mise à jour partielle)
        $validator = Validator::make($request->all(), [
            'id_card_front' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
            'id_card_back' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
            'driver_license_front' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
            'driver_license_back' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
            'license_number' => 'nullable|string|max:50',
            'license_expiry_date' => 'nullable|date|after:today',
            'additional_info' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation échouée',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $updateData = [];

            // Upload et remplacement des documents si fournis
            if ($request->hasFile('id_card_front')) {
                Storage::disk('public')->delete($application->id_card_front);
                $updateData['id_card_front'] = $request->file('id_card_front')->store('driver_documents', 'public');
            }

            if ($request->hasFile('id_card_back')) {
                Storage::disk('public')->delete($application->id_card_back);
                $updateData['id_card_back'] = $request->file('id_card_back')->store('driver_documents', 'public');
            }

            if ($request->hasFile('driver_license_front')) {
                Storage::disk('public')->delete($application->driver_license_front);
                $updateData['driver_license_front'] = $request->file('driver_license_front')->store('driver_documents', 'public');
            }

            if ($request->hasFile('driver_license_back')) {
                Storage::disk('public')->delete($application->driver_license_back);
                $updateData['driver_license_back'] = $request->file('driver_license_back')->store('driver_documents', 'public');
            }

            // Mettre à jour les autres champs
            if ($request->has('license_number')) {
                $updateData['license_number'] = $request->license_number;
            }

            if ($request->has('license_expiry_date')) {
                $updateData['license_expiry_date'] = $request->license_expiry_date;
            }

            if ($request->has('additional_info')) {
                $updateData['additional_info'] = $request->additional_info;
            }

            // Remettre en attente et réinitialiser les infos de révision
            $updateData['status'] = 'pending';
            $updateData['submitted_at'] = now();
            $updateData['reviewed_at'] = null;
            $updateData['reviewed_by'] = null;
            $updateData['rejection_reason'] = null;

            $application->update($updateData);

            // Recharger avec les URLs
            $application->refresh();
            $application->id_card_front_url = $application->getDocumentUrl('id_card_front');
            $application->id_card_back_url = $application->getDocumentUrl('id_card_back');
            $application->driver_license_front_url = $application->getDocumentUrl('driver_license_front');
            $application->driver_license_back_url = $application->getDocumentUrl('driver_license_back');

            return response()->json([
                'success' => true,
                'message' => 'Demande mise à jour avec succès',
                'application' => $application,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Supprimer une demande (seulement si rejetée)
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        $application = DriverApplication::where('user_id', $user->id)
            ->where('id', $id)
            ->first();

        if (!$application) {
            return response()->json([
                'success' => false,
                'message' => 'Demande non trouvée',
            ], 404);
        }

        // Seules les demandes rejetées peuvent être supprimées
        if ($application->status !== 'rejected') {
            return response()->json([
                'success' => false,
                'message' => 'Seules les demandes rejetées peuvent être supprimées',
            ], 422);
        }

        try {
            // Supprimer les documents
            Storage::disk('public')->delete([
                $application->id_card_front,
                $application->id_card_back,
                $application->driver_license_front,
                $application->driver_license_back,
            ]);

            $application->delete();

            return response()->json([
                'success' => true,
                'message' => 'Demande supprimée avec succès',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression: ' . $e->getMessage(),
            ], 500);
        }
    }
}
