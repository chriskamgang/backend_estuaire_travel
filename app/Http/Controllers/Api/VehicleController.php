<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class VehicleController extends Controller
{
    /**
     * Obtenir tous les véhicules du chauffeur
     */
    public function index(Request $request)
    {
        $driver = $request->user();

        $vehicles = Vehicle::where('user_id', $driver->id)
            ->orderBy('created_at', 'desc')
            ->get();

        // Ajouter les URLs complètes des photos
        $vehicles->each(function ($vehicle) {
            $vehicle->photo_url = $vehicle->photo ? asset('storage/' . $vehicle->photo) : null;
        });

        return response()->json([
            'success' => true,
            'data' => $vehicles,
        ]);
    }

    /**
     * Obtenir un véhicule spécifique
     */
    public function show(Request $request, $id)
    {
        $driver = $request->user();

        $vehicle = Vehicle::where('user_id', $driver->id)
            ->where('id', $id)
            ->first();

        if (!$vehicle) {
            return response()->json([
                'success' => false,
                'message' => 'Véhicule non trouvé',
            ], 404);
        }

        $vehicle->photo_url = $vehicle->photo ? asset('storage/' . $vehicle->photo) : null;

        return response()->json([
            'success' => true,
            'data' => $vehicle,
        ]);
    }

    /**
     * Ajouter un nouveau véhicule
     */
    public function store(Request $request)
    {
        $driver = $request->user();

        // Validation
        $validator = Validator::make($request->all(), [
            'brand' => 'required|string|max:100',
            'model' => 'required|string|max:100',
            'year' => 'required|integer|min:1900|max:' . (date('Y') + 1),
            'color' => 'required|string|max:50',
            'license_plate' => 'required|string|max:20|unique:vehicles,license_plate',
            'seats' => 'required|integer|min:1|max:50',
            'vehicle_type' => 'required|string|in:sedan,suv,van,minibus,bus',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:5120', // 5MB
        ], [
            'brand.required' => 'La marque est requise',
            'model.required' => 'Le modèle est requis',
            'year.required' => 'L\'année est requise',
            'year.min' => 'L\'année doit être supérieure à 1900',
            'year.max' => 'L\'année ne peut pas être dans le futur',
            'color.required' => 'La couleur est requise',
            'license_plate.required' => 'La plaque d\'immatriculation est requise',
            'license_plate.unique' => 'Cette plaque d\'immatriculation est déjà enregistrée',
            'seats.required' => 'Le nombre de places est requis',
            'seats.min' => 'Le véhicule doit avoir au moins 1 place',
            'vehicle_type.required' => 'Le type de véhicule est requis',
            'vehicle_type.in' => 'Type de véhicule invalide',
            'photo.image' => 'Le fichier doit être une image',
            'photo.mimes' => 'Format accepté: JPEG, PNG, JPG',
            'photo.max' => 'Taille maximale: 5MB',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation échouée',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Upload de la photo si fournie
            $photoPath = null;
            if ($request->hasFile('photo')) {
                $photoPath = $request->file('photo')->store('vehicles', 'public');
            }

            // Créer le véhicule
            $vehicle = Vehicle::create([
                'user_id' => $driver->id,
                'brand' => $request->brand,
                'model' => $request->model,
                'year' => $request->year,
                'color' => $request->color,
                'license_plate' => $request->license_plate,
                'seats' => $request->seats,
                'vehicle_type' => $request->vehicle_type,
                'photo' => $photoPath,
                'is_active' => true,
            ]);

            $vehicle->photo_url = $vehicle->photo ? asset('storage/' . $vehicle->photo) : null;

            return response()->json([
                'success' => true,
                'message' => 'Véhicule ajouté avec succès',
                'data' => $vehicle,
            ], 201);

        } catch (\Exception $e) {
            // Supprimer la photo si l'enregistrement échoue
            if (isset($photoPath)) {
                Storage::disk('public')->delete($photoPath);
            }

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'ajout du véhicule: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mettre à jour un véhicule
     */
    public function update(Request $request, $id)
    {
        $driver = $request->user();

        $vehicle = Vehicle::where('user_id', $driver->id)
            ->where('id', $id)
            ->first();

        if (!$vehicle) {
            return response()->json([
                'success' => false,
                'message' => 'Véhicule non trouvé',
            ], 404);
        }

        // Validation
        $validator = Validator::make($request->all(), [
            'brand' => 'sometimes|string|max:100',
            'model' => 'sometimes|string|max:100',
            'year' => 'sometimes|integer|min:1900|max:' . (date('Y') + 1),
            'color' => 'sometimes|string|max:50',
            'license_plate' => 'sometimes|string|max:20|unique:vehicles,license_plate,' . $id,
            'seats' => 'sometimes|integer|min:1|max:50',
            'vehicle_type' => 'sometimes|string|in:sedan,suv,van,minibus,bus',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation échouée',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $updateData = $request->only([
                'brand', 'model', 'year', 'color', 'license_plate',
                'seats', 'vehicle_type', 'is_active'
            ]);

            // Upload de la nouvelle photo si fournie
            if ($request->hasFile('photo')) {
                // Supprimer l'ancienne photo
                if ($vehicle->photo) {
                    Storage::disk('public')->delete($vehicle->photo);
                }
                $updateData['photo'] = $request->file('photo')->store('vehicles', 'public');
            }

            $vehicle->update($updateData);

            $vehicle->photo_url = $vehicle->photo ? asset('storage/' . $vehicle->photo) : null;

            return response()->json([
                'success' => true,
                'message' => 'Véhicule mis à jour avec succès',
                'data' => $vehicle,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Supprimer un véhicule
     */
    public function destroy(Request $request, $id)
    {
        $driver = $request->user();

        $vehicle = Vehicle::where('user_id', $driver->id)
            ->where('id', $id)
            ->first();

        if (!$vehicle) {
            return response()->json([
                'success' => false,
                'message' => 'Véhicule non trouvé',
            ], 404);
        }

        try {
            // Supprimer la photo
            if ($vehicle->photo) {
                Storage::disk('public')->delete($vehicle->photo);
            }

            $vehicle->delete();

            return response()->json([
                'success' => true,
                'message' => 'Véhicule supprimé avec succès',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Activer/désactiver un véhicule
     */
    public function toggleActive(Request $request, $id)
    {
        $driver = $request->user();

        $vehicle = Vehicle::where('user_id', $driver->id)
            ->where('id', $id)
            ->first();

        if (!$vehicle) {
            return response()->json([
                'success' => false,
                'message' => 'Véhicule non trouvé',
            ], 404);
        }

        $vehicle->is_active = !$vehicle->is_active;
        $vehicle->save();

        return response()->json([
            'success' => true,
            'message' => $vehicle->is_active ? 'Véhicule activé' : 'Véhicule désactivé',
            'data' => $vehicle,
        ]);
    }
}
