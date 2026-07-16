<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaveRecensementRequest;
use App\Models\Recensement;
use App\Models\Maison;
use App\Models\Operateur;
use App\Models\Parameters\Carre;
use App\Enums\RecensementStatut;
use App\Enums\MaisonStatut;
use App\Enums\OperateurStatut;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;

class SurveyApiController extends Controller
{
    use ApiResponse; // Fournit buildResponse() et renderData() unifiés

    /**
     * Endpoint API : Création d'une enquête de Ménage (Recensement)
     */
    public function createRecensement(SaveRecensementRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $rec = DB::transaction(function () use ($validated) {
                $recensement = new Recensement();
                $recensement->fill($validated);
                
                // UUID par défaut de secours
                if (empty($recensement->uuid)) {
                    $recensement->uuid = (string) Str::uuid();
                    $recensement->id = $recensement->uuid;
                }
                
                if (empty($recensement->nom_recensement)) {
                    $recensement->nom_recensement = 'SOC-MOB-' . uniqid();
                }

                $recensement->statut = RecensementStatut::SOUMIS;

                // Assignation automatique de l'agent enquêteur connecté via Sanctum
                // Check duplicate: Règle 6 : Détection de doublons (même téléphone principal + même adresse)
                $tel = $validated['chefTelephone'] ?? $validated['chef_telephone'] ?? null;
                $adr = $validated['adresse'] ?? null;
                if ($tel && $adr) {
                    $exists = Recensement::where('chef_telephone', $tel)
                        ->where('adresse', $adr)
                        ->exists();
                    if ($exists) {
                        throw new Exception("Un ménage avec le même numéro de téléphone et la même adresse existe déjà.");
                    }
                }

                $recensement->save();

                // Synchronisation de la relation pivot Many-to-Many des besoins prioritaires
                if (isset($validated['priorites'])) {
                    $recensement->priorites()->sync($validated['priorites']);
                }

                // Journalisation de l'historique de statut
                $recensement->historiques()->create([
                    'action' => RecensementStatut::SOUMIS->value,
                    'details' => [
                        'message' => 'Soumission de la fiche de recensement du ménage via l\'API mobile',
                        'chef_de_menage' => "{$recensement->chef_prenom} {$recensement->chef_nom}",
                        'matricule_enqueteur' => $recensement->enqueteur?->matricule
                    ],
                    'user_identifier' => auth()->user()?->email ?? 'api-system'
                ]);

                return $recensement;
            });

            return $this->buildResponse(
                success: true,
                message: "Ménage créé avec succès.",
                data: [
                    'id' => $rec->id,
                    'uuid' => $rec->uuid,
                    'statut' => $rec->statut->value
                ],
                statusCode: 201
            );

        } catch (Exception $e) {
            return $this->buildResponse(
                success: false,
                message: "Erreur lors de l'enregistrement du ménage.",
                errors: ['exception' => $e->getMessage()],
                statusCode: 500
            );
        }
    }

    /**
     * Endpoint API : Création d'une fiche d'habitation (Maison)
     */
    public function createMaison(Request $request): JsonResponse
    {
        $data = $request->json()->all();
        if (empty($data)) {
            return $this->buildResponse(false, "Données JSON invalides.", [], ['payload' => 'JSON requis'], 400);
        }

        try {
            $maison = new Maison();
            $maison->id = (string) Str::uuid();
            $maison->numero_porte = $data['numeroPorte'] ?? $data['numero_porte'] ?? null;
            $maison->adresse = $data['adresse'] ?? '';
            $maison->nombre_hommes = (int)($data['nombreHommes'] ?? $data['nombre_hommes'] ?? 0);
            $maison->nombre_femmes = (int)($data['nombreFemmes'] ?? $data['nombre_femmes'] ?? 0);
            $maison->nombre_enfants = (int)($data['nombreEnfants'] ?? $data['nombre_enfants'] ?? 0);
            $maison->gps_latitude = $data['gpsLatitude'] ?? null;
            $maison->gps_longitude = $data['gpsLongitude'] ?? null;
            $maison->proprietaire_nom = $data['proprietaire_nom'] ?? $data['proprietaireNom'] ?? null;
            $maison->proprietaire_telephone = $data['proprietaire_telephone'] ?? $data['proprietaireTelephone'] ?? null;
            $maison->statut = MaisonStatut::SOUMIS;

            $maison->reference_cadastrale = $data['referenceCadastrale'] ?? $data['reference_cadastrale'] ?? null;
            $maison->usage_principal_id = $data['usage_principal_id'] ?? $data['usagePrincipalId'] ?? $data['usage_principal'] ?? $data['usage'] ?? null;
            $maison->type_construction_id = $data['type_construction_id'] ?? $data['typeConstructionId'] ?? $data['type_construction'] ?? $data['typeHabitation'] ?? null;
            $maison->statut_foncier_id = $data['statut_foncier_id'] ?? $data['statutFoncierId'] ?? $data['statut_foncier'] ?? $data['statutFoncier'] ?? null;
            $maison->source_eau_id = $data['source_eau_id'] ?? $data['sourceEauId'] ?? $data['source_eau'] ?? $data['accesEau'] ?? null;
            $maison->source_energie_id = $data['source_energie_id'] ?? $data['sourceEnergieId'] ?? $data['source_energie'] ?? $data['accesElectricite'] ?? null;
            $maison->assainissement_id = $data['assainissement_id'] ?? $data['assainissementId'] ?? $data['assainissement'] ?? $data['accesAssainissement'] ?? null;
            $maison->gestion_dechet_id = $data['gestion_dechet_id'] ?? $data['gestionDechetId'] ?? $data['gestion_dechet'] ?? $data['gestionDechets'] ?? null;

            if (isset($data['carre_id'])) {
                $maison->carre_id = $data['carre_id'];
            }

            if (auth()->check() && auth()->user()->agent) {
                $maison->enqueteur_id = auth()->user()->agent->id;
            }

            // Check duplicate: Règle 2 : Détection de doublons (même adresse + même numéro de porte + même carré)
            $porte = $maison->numero_porte;
            $adr = $maison->adresse;
            $carreId = $maison->carre_id;
            if ($adr && $porte && $carreId) {
                $exists = Maison::where('adresse', $adr)
                    ->where('numero_porte', $porte)
                    ->where('carre_id', $carreId)
                    ->exists();
                if ($exists) {
                    throw new Exception("Une habitation avec la même adresse, le même numéro de porte et dans le même carré existe déjà.");
                }
            }

            $maison->save();

            return $this->buildResponse(
                success: true,
                message: "Habitat créé avec succès.",
                data: ['id' => $maison->id],
                statusCode: 201
            );

        } catch (Exception $e) {
            return $this->buildResponse(
                success: false,
                message: "Erreur lors de l'enregistrement de l'habitat.",
                errors: ['exception' => $e->getMessage()],
                statusCode: 500
            );
        }
    }

    /**
     * Endpoint API : Création d'un opérateur économique.
     */
    public function createOperateur(Request $request): JsonResponse
    {
        $data = $request->json()->all();
        if (empty($data)) {
            return $this->buildResponse(false, "Données JSON invalides.", [], ['payload' => 'JSON requis'], 400);
        }

        try {
            $op = new Operateur();
            $op->id = $data['uuid'] ?? (string) Str::uuid();
            $op->uuid = $op->id;
            $op->nom_entreprise = $data['nomEntreprise'] ?? $data['nom_entreprise'] ?? 'ENT-MOB-' . uniqid();
            $op->nom_commercial = $op->nom_entreprise;
            $tableFields = ['adresse', 'telephone', 'gps_latitude', 'gps_longitude'];
            foreach ($tableFields as $field) {
                if (isset($data[$field])) {
                    $op->{$field} = $data[$field];
                }
            }
            $op->statut = OperateurStatut::SOUMIS;

            $op->carre_id = $data['carre_id'] ?? $data['carreId'] ?? null;
            $op->quartier_id = $data['quartier_id'] ?? $data['quartierId'] ?? null;
            if (empty($op->carre_id)) {
                $defaultCarre = \App\Models\Parameters\Carre::first();
                if ($defaultCarre) {
                    $op->carre_id = $defaultCarre->id;
                    $op->quartier_id = $defaultCarre->quartier_id;
                }
            }

            // Check duplicate: Règle 3 & 4 : RCCM & NIF Uniqueness, Règle 5 : raison sociale / nom commercial in campaign
            $rccm = $data['rccm'] ?? null;
            $nif = $data['nif'] ?? null;
            $nom = $op->nom_commercial;
            if ($rccm) {
                $exists = Operateur::where('rccm', $rccm)->exists();
                if ($exists) {
                    throw new Exception("Ce numéro RCCM est déjà enregistré pour un autre opérateur économique.");
                }
            }
            if ($nif) {
                $exists = Operateur::where('nif', $nif)->exists();
                if ($exists) {
                    throw new Exception("Ce numéro NIF est déjà enregistré pour un autre opérateur économique.");
                }
            }
            if ($nom) {
                $exists = Operateur::where('nom_commercial', $nom)->exists();
                if ($exists) {
                    throw new Exception("Cet opérateur économique a déjà été recensé.");
                }
            }

            if (auth()->check() && auth()->user()->agent) {
                $op->enqueteur_id = auth()->user()->agent->id;
            }

            $op->save();

            return $this->buildResponse(
                success: true,
                message: "Opérateur économique créé avec succès.",
                data: ['id' => $op->id, 'uuid' => $op->uuid],
                statusCode: 201
            );

        } catch (Exception $e) {
            return $this->buildResponse(
                success: false,
                message: "Erreur lors de l'enregistrement de l'opérateur.",
                errors: ['exception' => $e->getMessage()],
                statusCode: 500
            );
        }
    }
}
