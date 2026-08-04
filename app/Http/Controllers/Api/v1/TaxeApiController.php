<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Taxe;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Exception;

class TaxeApiController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        try {
            $query = Taxe::query()->latest('ordre');

            if ($request->filled('q')) {
                $search = $request->input('q');
                $query->where(function ($q) use ($search) {
                    $q->where('nom', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%")
                      ->orWhere('categorie', 'like', "%{$search}%");
                });
            }

            if ($request->filled('categorie')) {
                $query->where('categorie', $request->input('categorie'));
            }

            if ($request->has('actif')) {
                $query->where('actif', $request->boolean('actif'));
            }

            $taxes = $query->paginate($request->input('per_page', 20));

            return $this->buildResponse(
                success: true,
                message: "Liste des taxes récupérée avec succès.",
                data: $taxes
            );
        } catch (Exception $e) {
            return $this->buildResponse(
                success: false,
                message: "Erreur lors de la récupération des taxes.",
                errors: ['exception' => $e->getMessage()],
                statusCode: 500
            );
        }
    }

    public function show(string $id): JsonResponse
    {
        try {
            $taxe = Taxe::where('id', $id)->orWhere('uuid', $id)->firstOrFail();

            return $this->buildResponse(
                success: true,
                message: "Détails de la taxe récupérés.",
                data: $taxe
            );
        } catch (Exception $e) {
            return $this->buildResponse(
                success: false,
                message: "Taxe introuvable.",
                errors: ['exception' => $e->getMessage()],
                statusCode: 404
            );
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'nom' => 'required|string|max:255',
                'code' => 'required|string|max:50|unique:taxes,code',
                'categorie' => 'required|string|max:100',
                'montant' => 'required|numeric|min:0',
                'mode_calcul' => 'required|string',
                'periodicite' => 'required|string',
                'description' => 'nullable|string',
                'pourcentage' => 'nullable|numeric|min:0|max:100',
                'surface' => 'nullable|numeric|min:0',
                'volume' => 'nullable|numeric|min:0',
            ]);

            $uuid = (string) Str::uuid();

            $taxe = Taxe::create(array_merge($validated, [
                'id' => $uuid,
                'uuid' => $uuid,
                'code' => strtoupper($validated['code']),
                'actif' => true,
                'date_debut' => now(),
            ]));

            return $this->buildResponse(
                success: true,
                message: "Taxe créée avec succès.",
                data: $taxe,
                statusCode: 201
            );
        } catch (Exception $e) {
            return $this->buildResponse(
                success: false,
                message: "Erreur lors de la création de la taxe.",
                errors: ['exception' => $e->getMessage()],
                statusCode: 422
            );
        }
    }

    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $taxe = Taxe::where('id', $id)->orWhere('uuid', $id)->firstOrFail();

            $validated = $request->validate([
                'nom' => 'sometimes|string|max:255',
                'code' => 'sometimes|string|max:50|unique:taxes,code,' . $taxe->id,
                'categorie' => 'sometimes|string|max:100',
                'montant' => 'sometimes|numeric|min:0',
                'mode_calcul' => 'sometimes|string',
                'periodicite' => 'sometimes|string',
                'description' => 'nullable|string',
                'pourcentage' => 'nullable|numeric|min:0|max:100',
                'surface' => 'nullable|numeric|min:0',
                'volume' => 'nullable|numeric|min:0',
                'actif' => 'sometimes|boolean',
            ]);

            $taxe->update($validated);

            return $this->buildResponse(
                success: true,
                message: "Taxe mise à jour avec succès.",
                data: $taxe
            );
        } catch (Exception $e) {
            return $this->buildResponse(
                success: false,
                message: "Erreur de mise à jour de la taxe.",
                errors: ['exception' => $e->getMessage()],
                statusCode: 422
            );
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $taxe = Taxe::where('id', $id)->orWhere('uuid', $id)->firstOrFail();
            $taxe->delete();

            return $this->buildResponse(
                success: true,
                message: "Taxe supprimée avec succès."
            );
        } catch (Exception $e) {
            return $this->buildResponse(
                success: false,
                message: "Erreur lors de la suppression.",
                errors: ['exception' => $e->getMessage()],
                statusCode: 500
            );
        }
    }
}
