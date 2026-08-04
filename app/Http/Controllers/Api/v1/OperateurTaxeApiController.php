<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Operateur;
use App\Services\TaxAssignmentService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Exception;

class OperateurTaxeApiController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected TaxAssignmentService $assignmentService
    ) {}

    public function getOperateurTaxes(string $id): JsonResponse
    {
        try {
            $operateur = Operateur::where('id', $id)->orWhere('uuid', $id)->firstOrFail();

            // Si l'opérateur n'a pas encore de taxes affectées, on effectue l'affectation automatique
            if ($operateur->taxesAffectees()->count() === 0) {
                $this->assignmentService->autoAssignTaxesForOperateur($operateur);
            }

            $taxes = $operateur->taxesAffectees()
                ->with(['taxe', 'paiements', 'exonerations'])
                ->get();

            return $this->buildResponse(
                success: true,
                message: "Taxes de l'opérateur récupérées.",
                data: [
                    'operateur_id' => $operateur->id,
                    'nom_entreprise' => $operateur->nom_entreprise ?? $operateur->nom_commercial,
                    'total_du' => $operateur->total_du,
                    'total_paye' => $operateur->total_paye,
                    'reste_a_payer' => $operateur->reste_a_payer,
                    'taux_recouvrement' => $operateur->taux_recouvrement,
                    'taxes' => $taxes->map(function ($taxeOp) {
                        return [
                            'id' => $taxeOp->id,
                            'taxe_code' => $taxeOp->taxe?->code,
                            'taxe_nom' => $taxeOp->taxe?->nom,
                            'categorie' => $taxeOp->taxe?->categorie,
                            'montant_attendu' => $taxeOp->montant_attendu,
                            'montant_paye' => $taxeOp->montant_paye,
                            'reste_a_payer' => $taxeOp->reste_a_payer,
                            'date_limite' => $taxeOp->date_limite?->format('Y-m-d'),
                            'statut' => $taxeOp->statut?->label(),
                            'jours_retard' => $taxeOp->jours_retard,
                            'est_solde' => $taxeOp->est_solde,
                        ];
                    }),
                ]
            );
        } catch (Exception $e) {
            return $this->buildResponse(
                success: false,
                message: "Erreur lors de la récupération des taxes de l'opérateur.",
                errors: ['exception' => $e->getMessage()],
                statusCode: 404
            );
        }
    }
}
