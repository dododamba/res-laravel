<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Recouvrement;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Exception;

class RecouvrementApiController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        try {
            $query = Recouvrement::with(['taxeOperateur.taxe', 'taxeOperateur.operateur', 'agent.personne'])->latest('date_relance');
            $recouvrements = $query->paginate($request->input('per_page', 20));

            return $this->buildResponse(true, "Liste des recouvrements.", $recouvrements);
        } catch (Exception $e) {
            return $this->buildResponse(false, "Erreur lors de la récupération des relances.", [], ['exception' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'taxe_operateur_id' => 'required|exists:taxe_operateurs,id',
                'commentaires' => 'required|string',
                'statut' => 'required|string',
                'date_relance' => 'nullable|date',
                'prochaine_relance' => 'nullable|date',
            ]);

            $uuid = (string) Str::uuid();
            $recouvrement = Recouvrement::create(array_merge($validated, [
                'id' => $uuid,
                'uuid' => $uuid,
                'date_relance' => $validated['date_relance'] ?? now(),
                'user_id' => auth()->id(),
                'agent_id' => auth()->user()?->agent?->id,
            ]));

            return $this->buildResponse(true, "Relance de recouvrement créée.", $recouvrement, 201);
        } catch (Exception $e) {
            return $this->buildResponse(false, "Erreur de création de la relance.", [], ['exception' => $e->getMessage()], 422);
        }
    }
}
