<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Maison;
use App\Models\Recensement;
use App\Models\Operateur;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SearchApiController extends Controller
{
    use ApiResponse;

    /**
     * Endpoint de recherche globale multi-critères (/api/v1/search)
     */
    public function search(Request $request): JsonResponse
    {
        $q = trim($request->input('q', ''));
        $type = strtolower($request->input('type', 'all'));
        $quartierId = $request->input('quartier_id');
        $carreId = $request->input('carre_id');
        $statut = $request->input('statut');
        $page = (int) $request->input('page', 1);
        $perPage = (int) $request->input('per_page', 15);

        $results = [];

        // 1. RECHERCHE HABITATS (MAISONS)
        if (in_array($type, ['all', 'habitat', 'maison'])) {
            $maisonQuery = Maison::with(['carre.quartier']);

            if (!empty($q)) {
                $maisonQuery->where(function ($query) use ($q) {
                    $query->where('numero_porte', 'like', "%{$q}%")
                          ->orWhere('adresse', 'like', "%{$q}%")
                          ->orWhere('reference_cadastrale', 'like', "%{$q}%")
                          ->orWhere('proprietaire_nom', 'like', "%{$q}%")
                          ->orWhere('proprietaire_telephone', 'like', "%{$q}%");
                });
            }

            if ($carreId) {
                $maisonQuery->where('carre_id', $carreId);
            }
            if ($quartierId) {
                $maisonQuery->whereHas('carre', function ($query) use ($quartierId) {
                    $query->where('quartier_id', $quartierId);
                });
            }
            if ($statut) {
                $maisonQuery->where('statut', $statut);
            }

            $maisons = $maisonQuery->orderBy('created_at', 'desc')->limit(50)->get();

            foreach ($maisons as $m) {
                $quartierObj = $m->carre && $m->carre->quartier ? [
                    'id' => $m->carre->quartier->id,
                    'nom' => $m->carre->quartier->nom
                ] : null;

                $carreObj = $m->carre ? [
                    'id' => $m->carre->id,
                    'nom' => $m->carre->nom
                ] : null;

                $statutVal = is_object($m->statut) ? ($m->statut->value ?? $m->statut->name ?? 'VALIDE') : ($m->statut ?: 'VALIDE');

                $results[] = [
                    'id' => (string) $m->id,
                    'type' => 'habitat',
                    'type_label' => 'Habitation',
                    'titre' => "Porte N° " . ($m->numero_porte ?: 'S/N'),
                    'sous_titre' => $m->adresse ?: ($carreObj ? "Carré: {$carreObj['nom']}" : "Habitation"),
                    'code_ref' => $m->reference_cadastrale ?: ("PORTE-{$m->numero_porte}"),
                    'telephone' => $m->proprietaire_telephone ?: '',
                    'statut' => $statutVal,
                    'quartier' => $quartierObj,
                    'carre' => $carreObj,
                    'date_creation' => $m->created_at ? $m->created_at->toDateTimeString() : null,
                    'details' => [
                        'proprietaire' => $m->proprietaire_nom,
                        'habitants_total' => ($m->nombre_hommes + $m->nombre_femmes + $m->nombre_enfants),
                    ]
                ];
            }
        }

        // 2. RECHERCHE MÉNAGES (RECENSEMENTS)
        if (in_array($type, ['all', 'menage', 'household'])) {
            $recensementQuery = Recensement::with(['quartier', 'carre']);

            if (!empty($q)) {
                $recensementQuery->where(function ($query) use ($q) {
                    $query->where('chef_menage_nom', 'like', "%{$q}%")
                          ->orWhere('chef_nom', 'like', "%{$q}%")
                          ->orWhere('chef_prenom', 'like', "%{$q}%")
                          ->orWhere('chef_menage_telephone', 'like', "%{$q}%")
                          ->orWhere('telephone_chef', 'like', "%{$q}%")
                          ->orWhere('numero_fiche', 'like', "%{$q}%")
                          ->orWhere('adresse', 'like', "%{$q}%");
                });
            }

            if ($quartierId) {
                $recensementQuery->where('quartier_id', $quartierId);
            }
            if ($carreId) {
                $recensementQuery->where('carre_id', $carreId);
            }
            if ($statut) {
                $recensementQuery->where('statut', $statut);
            }

            $recensements = $recensementQuery->orderBy('created_at', 'desc')->limit(50)->get();

            foreach ($recensements as $r) {
                $chefNomComplet = trim(($r->chef_prenom ?? '') . ' ' . ($r->chef_nom ?? $r->chef_menage_nom ?? 'Inconnu'));

                $quartierObj = $r->quartier ? [
                    'id' => $r->quartier->id,
                    'nom' => $r->quartier->nom
                ] : null;

                $carreObj = $r->carre ? [
                    'id' => $r->carre->id,
                    'nom' => $r->carre->nom
                ] : null;

                $statutVal = is_object($r->statut) ? ($r->statut->value ?? $r->statut->name ?? 'VALIDE') : ($r->statut ?: 'VALIDE');

                $results[] = [
                    'id' => (string) $r->id,
                    'type' => 'menage',
                    'type_label' => 'Ménage',
                    'titre' => "Ménage " . ($chefNomComplet ?: 'Anonyme'),
                    'sous_titre' => "Fiche: " . ($r->numero_fiche ?: substr($r->id, 0, 8)) . " • " . ($r->nombre_personnes ?: 1) . " occupant(s)",
                    'code_ref' => $r->numero_fiche ?: (string)$r->id,
                    'telephone' => $r->telephone_chef ?: $r->chef_menage_telephone ?: '',
                    'statut' => $statutVal,
                    'quartier' => $quartierObj,
                    'carre' => $carreObj,
                    'date_creation' => $r->created_at ? $r->created_at->toDateTimeString() : null,
                    'details' => [
                        'chef' => $chefNomComplet,
                        'occupants' => $r->nombre_personnes ?: 1,
                    ]
                ];
            }
        }

        // 3. RECHERCHE OPÉRATEURS ÉCONOMIQUES
        if (in_array($type, ['all', 'operateur', 'fiscal'])) {
            $operateurQuery = Operateur::with(['quartier', 'carre']);

            if (!empty($q)) {
                $operateurQuery->where(function ($query) use ($q) {
                    $query->where('nom_commercial', 'like', "%{$q}%")
                          ->orWhere('nom_entreprise', 'like', "%{$q}%")
                          ->orWhere('promoteur_nom', 'like', "%{$q}%")
                          ->orWhere('promoteur_prenom', 'like', "%{$q}%")
                          ->orWhere('telephone', 'like', "%{$q}%")
                          ->orWhere('rccm', 'like', "%{$q}%")
                          ->orWhere('nif', 'like', "%{$q}%")
                          ->orWhere('adresse', 'like', "%{$q}%");
                });
            }

            if ($quartierId) {
                $operateurQuery->where('quartier_id', $quartierId);
            }
            if ($carreId) {
                $operateurQuery->where('carre_id', $carreId);
            }
            if ($statut) {
                $operateurQuery->where('statut', $statut);
            }

            $operateurs = $operateurQuery->orderBy('created_at', 'desc')->limit(50)->get();

            foreach ($operateurs as $op) {
                $nomCommercial = $op->nom_commercial ?: $op->nom_entreprise ?: 'Entreprise S/N';
                $promoteur = trim(($op->promoteur_prenom ?? '') . ' ' . ($op->promoteur_nom ?? ''));

                $quartierObj = $op->quartier ? [
                    'id' => $op->quartier->id,
                    'nom' => $op->quartier->nom
                ] : null;

                $carreObj = $op->carre ? [
                    'id' => $op->carre->id,
                    'nom' => $op->carre->nom
                ] : null;

                $statutVal = is_object($op->statut) ? ($op->statut->value ?? $op->statut->name ?? 'VALIDE') : ($op->statut ?: 'VALIDE');

                $results[] = [
                    'id' => (string) $op->id,
                    'type' => 'operateur',
                    'type_label' => 'Opérateur Économique',
                    'titre' => $nomCommercial,
                    'sous_titre' => "Promoteur: " . ($promoteur ?: 'Non renseigné') . ($op->nif ? " • NIF: {$op->nif}" : ""),
                    'code_ref' => $op->nif ?: $op->rccm ?: (string)$op->id,
                    'telephone' => $op->telephone ?: '',
                    'statut' => $statutVal,
                    'quartier' => $quartierObj,
                    'carre' => $carreObj,
                    'date_creation' => $op->created_at ? $op->created_at->toDateTimeString() : null,
                    'details' => [
                        'promoteur' => $promoteur,
                        'nif' => $op->nif,
                        'rccm' => $op->rccm,
                        'taille' => is_object($op->taille) ? ($op->taille->value ?? $op->taille->name ?? '') : ($op->taille ?: ''),
                    ]
                ];
            }
        }

        // Tri global combiné par date de création récente
        usort($results, function ($a, $b) {
            return strcmp($b['date_creation'] ?? '', $a['date_creation'] ?? '');
        });

        // Pagination
        $total = count($results);
        $offset = ($page - 1) * $perPage;
        $paginatedItems = array_slice($results, $offset, $perPage);
        $lastPage = (int) ceil($total / max($perPage, 1));

        return $this->buildResponse(
            success: true,
            message: "Résultats de recherche récupérés avec succès.",
            data: $paginatedItems,
            meta: [
                'current_page' => $page,
                'last_page' => max($lastPage, 1),
                'per_page' => $perPage,
                'total' => $total,
            ]
        );
    }
}
