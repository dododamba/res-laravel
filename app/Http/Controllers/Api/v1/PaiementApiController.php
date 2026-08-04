<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\PaiementTaxe;
use App\Services\PaymentProcessingService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class PaiementApiController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected PaymentProcessingService $paymentService
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $query = PaiementTaxe::query()
                ->with(['taxeOperateur.taxe', 'taxeOperateur.operateur', 'agent.personne'])
                ->latest('date_paiement');

            if ($request->filled('q')) {
                $search = $request->input('q');
                $query->where(function ($q) use ($search) {
                    $q->where('numero_recu', 'like', "%{$search}%")
                      ->orWhere('reference', 'like', "%{$search}%")
                      ->orWhereHas('taxeOperateur.operateur', function ($opQ) use ($search) {
                          $opQ->where('nom_entreprise', 'like', "%{$search}%")
                              ->orWhere('nom_commercial', 'like', "%{$search}%");
                      });
                });
            }

            if ($request->filled('annee')) {
                $query->whereYear('date_paiement', $request->input('annee'));
            }

            if ($request->filled('mode_paiement')) {
                $query->where('mode_paiement', $request->input('mode_paiement'));
            }

            $paiements = $query->paginate($request->input('per_page', 20));

            return $this->buildResponse(
                success: true,
                message: "Liste des paiements récupérée.",
                data: $paiements
            );
        } catch (Exception $e) {
            return $this->buildResponse(
                success: false,
                message: "Erreur lors de la récupération des paiements.",
                errors: ['exception' => $e->getMessage()],
                statusCode: 500
            );
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'taxe_operateur_id' => 'required|exists:taxe_operateurs,id',
                'montant' => 'required|numeric|min:1',
                'mode_paiement' => 'required|string',
                'date_paiement' => 'nullable|date',
                'reference' => 'nullable|string|max:255',
                'numero_recu' => 'nullable|string|max:100',
                'signature_agent' => 'nullable|string',
                'signature_client' => 'nullable|string',
                'observation' => 'nullable|string',
            ]);

            // Rattachement de l'agent connecté
            if (auth()->check() && auth()->user()->agent) {
                $validated['agent_id'] = auth()->user()->agent->id;
                $validated['user_id'] = auth()->id();
            }

            $paiement = $this->paymentService->recordPayment($validated);

            // Gestion de la photo du reçu papier
            if ($request->hasFile('justificatif')) {
                $paiement->addMediaFromRequest('justificatif')
                         ->toMediaCollection('justificatifs_paiement');
            }

            $paiement->load(['taxeOperateur.taxe', 'taxeOperateur.operateur']);

            return $this->buildResponse(
                success: true,
                message: "Encaissement enregistré avec succès.",
                data: [
                    'id' => $paiement->id,
                    'numero_recu' => $paiement->numero_recu,
                    'montant' => $paiement->montant,
                    'reste_a_payer' => $paiement->taxeOperateur?->reste_a_payer,
                    'statut_taxe' => $paiement->taxeOperateur?->statut?->label(),
                ],
                statusCode: 201
            );
        } catch (Exception $e) {
            return $this->buildResponse(
                success: false,
                message: "Erreur lors de l'enregistrement de l'encaissement.",
                errors: ['exception' => $e->getMessage()],
                statusCode: 422
            );
        }
    }
}
