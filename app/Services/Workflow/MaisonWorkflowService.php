<?php

namespace App\Services\Workflow;

use App\Models\Maison;
use App\Enums\MaisonStatut;
use App\Models\Agent;
use Illuminate\Support\Facades\DB;
use LogicException;

class MaisonWorkflowService
{
    /**
     * Détermine si une transition vers un statut cible est autorisée.
     */
    public function canTransition(Maison $maison, MaisonStatut $targetStatut): bool
    {
        $current = $maison->statut;

        switch ($targetStatut) {
            case MaisonStatut::SOUMIS:
                return $current === MaisonStatut::BROUILLON;

            case MaisonStatut::CONTROLE:
                return $current === MaisonStatut::SOUMIS;

            case MaisonStatut::VALIDE:
                // Seule une habitation Contrôlée peut être validée
                if ($current !== MaisonStatut::CONTROLE) {
                    return false;
                }
                // Coordonnées GPS strictement requises pour la validation
                if (is_null($maison->gps_latitude) || is_null($maison->gps_longitude)) {
                    return false;
                }
                return true;

            case MaisonStatut::REJETE:
                // Une habitation Soumise ou Contrôlée peut être rejetée
                return in_array($current, [MaisonStatut::SOUMIS, MaisonStatut::CONTROLE]);

            default:
                return false;
        }
    }

    /**
     * Applique la transition d'état métier et historise la modification dans une transaction SQL.
     */
    public function transitionTo(Maison $maison, MaisonStatut $targetStatut, string $operator = 'system', string $motif = ''): void
    {
        if (!$this->canTransition($maison, $targetStatut)) {
            throw new LogicException(sprintf(
                "La transition de '%s' vers '%s' n'est pas autorisée.",
                $maison->statut->label(),
                $targetStatut->label()
            ));
        }

        DB::transaction(function () use ($maison, $targetStatut, $operator, $motif) {
            $oldStatut = $maison->statut;
            $maison->statut = $targetStatut;

            // Règle métier : Assigner un validateur simulé si l'état passe à VALIDE
            if ($targetStatut === MaisonStatut::VALIDE) {
                $validateur = Agent::where('matricule', 'AGT-2026-0003')->first();
                $maison->validateur_id = $validateur ? $validateur->id : null;
            }

            $maison->save();

            // Création de l'historique de transition
            $maison->historiques()->create([
                'action' => $targetStatut->value,
                'details' => [
                    'message' => "Changement de statut de l'habitation : {$targetStatut->label()}",
                    'ancien_statut' => $oldStatut->label(),
                    'nouveau_statut' => $targetStatut->label(),
                    'motif' => $motif,
                    'operateur' => $operator
                ],
                'user_identifier' => auth()->user()?->email ?? $operator
            ]);
        });
    }
}
