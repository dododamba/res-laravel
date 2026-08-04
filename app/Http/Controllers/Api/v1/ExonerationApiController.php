<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Exoneration;
use App\Models\TaxeOperateur;
use App\Services\TaxCalculationService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Exception;

class ExonerationApiController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected TaxCalculationService $calculationService
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $query = Exoneration::with(['taxeOperateur.taxe', 'taxeOperateur.operateur'])->latest('date_exoneration');
            $exonerations = $query->paginate($request->input('per_page', 20));

            return $this->buildResponse(true, "Liste des exonérations.", $exonerations);
        } catch (Exception $e) {
            return $this->buildResponse(false, "Erreur lors de la récupération des exonérations.", [], ['exception' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'taxe_operateur_id' => 'required|exists:taxe_operateurs,id',
                'motif' => 'required|string',
                'autorite' => 'required|string|max:255',
                'date_exoneration' => 'nullable|date',
                'montant_exonere' => 'required|numeric|min:1',
            ]);

            $taxeOp = TaxeOperateur::findOrFail($validated['taxe_operateur_id']);
            $uuid = (string) Str::uuid();

            $exoneration = Exoneration::create([
                'id' => $uuid,
                'uuid' => $uuid,
                'taxe_operateur_id' => $taxeOp->id,
                'motif' => $validated['motif'],
                'autorite' => $validated['autorite'],
                'date_exoneration' => $validated['date_exoneration'] ?? now(),
                'montant_exonere' => $validated['montant_exonere'],
                'user_id' => auth()->id(),
                'agent_id' => auth()->user()?->agent?->id,
            ]);

            $this->calculationService->updateTaxeOperateurStatus($taxeOp);

            return $this->buildResponse(true, "Exonération accordée avec succès.", $exoneration, 201);
        } catch (Exception $e) {
            return $this->buildResponse(false, "Erreur lors de la création de l'exonération.", [], ['exception' => $e->getMessage()], 422);
        }
    }
}
