<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Recensement;
use App\Models\Maison;
use App\Models\Operateur;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Exception;

class SyncApiController extends Controller
{
    use ApiResponse; // Fournit buildResponse()

    /**
     * API Endpoint : PUSH (Réception de paquets d'enquêtes hors-ligne collectées sur le terrain)
     */
    public function push(Request $request): JsonResponse
    {
        $payload = $request->json()->all();

        if (empty($payload)) {
            return $this->buildResponse(false, "Données de synchronisation vides.", [], ['payload' => 'JSON requis'], 400);
        }

        $recensements = $payload['recensements'] ?? $payload['menages'] ?? [];
        $maisons = $payload['maisons'] ?? $payload['habitats'] ?? [];
        $operateurs = $payload['operateurs'] ?? [];

        $syncedIds = [
            'recensements' => [],
            'maisons' => [],
            'operateurs' => []
        ];

        $errors = [];

        // Traitement transactionnel de synchronisation par paquets
        DB::transaction(function () use ($recensements, $maisons, $operateurs, &$syncedIds, &$errors) {
            
            // 1. Synchronisation des Recensements (Ménages)
            foreach ($recensements as $recData) {
                try {
                    $id = $recData['uuid'] ?? $recData['id'] ?? null;
                    if (!$id) continue;

                    // Mappage de compatibilité pour l'application mobile Ionic
                    $mappedData = [];
                    
                    // Identité du Chef de ménage & contacts
                    $mappedData['chefNom'] = $recData['chefNom'] ?? 'Inconnu';
                    $mappedData['chefPrenom'] = $recData['chefPrenom'] ?? 'Inconnu';
                    $mappedData['chefSexe'] = $recData['chefSexe'] ?? 'M';
                    $mappedData['chefAge'] = isset($recData['chefAge']) ? (int)$recData['chefAge'] : 35;
                    $mappedData['chefTelephone'] = $recData['telephonePrincipal'] ?? $recData['chefTelephone'] ?? '0600000000';
                    $mappedData['chefEmail'] = $recData['chefEmail'] ?? null;
                    $mappedData['observations'] = trim(
                        ($recData['observations'] ?? '') . "\n" .
                        "Situation Matrimoniale: " . ($recData['situationMatrimoniale'] ?? 'Marié(e)') . "\n" .
                        "Profession du Chef: " . ($recData['chefProfession'] ?? 'Sans')
                    );

                    // Localisation géographique
                    $mappedData['quartier_id'] = $recData['quartierId'] ?? $recData['quartier_id'] ?? null;
                    $mappedData['carre_id'] = $recData['carreId'] ?? $recData['carre_id'] ?? null;
                    $mappedData['secteur_id'] = $recData['secteurId'] ?? $recData['secteur_id'] ?? null;
                    $mappedData['avenue_id'] = $recData['avenueId'] ?? $recData['avenue_id'] ?? null;
                    $mappedData['numeroPorte'] = $recData['numeroPorte'] ?? $recData['numero_porte'] ?? '1';
                    $mappedData['adresse'] = $recData['adresse'] ?? 'Adresse Terrain';

                    // Démographie & composition du ménage
                    $hommes = isset($recData['hommes']) ? (int)$recData['hommes'] : (isset($recData['nombreHommes']) ? (int)$recData['nombreHommes'] : 1);
                    $femmes = isset($recData['femmes']) ? (int)$recData['femmes'] : (isset($recData['nombreFemmes']) ? (int)$recData['nombreFemmes'] : 1);
                    $enfants = isset($recData['enfants']) ? (int)$recData['enfants'] : (isset($recData['nombreEnfants']) ? (int)$recData['nombreEnfants'] : 0);
                    $jeunes = isset($recData['jeunes']) ? (int)$recData['jeunes'] : (isset($recData['nombreJeunes']) ? (int)$recData['nombreJeunes'] : 0);
                    $handicapes = isset($recData['handicap']) ? (int)$recData['handicap'] : (isset($recData['nombreHandicapes']) ? (int)$recData['nombreHandicapes'] : 0);

                    $mappedData['nombreHommes'] = $hommes;
                    $mappedData['nombreFemmes'] = $femmes;
                    $mappedData['nombreEnfants'] = $enfants;
                    $mappedData['nombreJeunes'] = $jeunes;
                    $mappedData['nombreHandicapes'] = $handicapes;
                    $mappedData['nombrePersonnes'] = $hommes + $femmes; // Règle métier : Cohérence Démographique stricte (Total = Hommes + Femmes)

                    // Niveaux d'instruction
                    $instruction = $recData['chefInstruction'] ?? 'Aucun';
                    $mappedData['instructionAucun'] = ($instruction === 'Aucun') ? 1 : 0;
                    $mappedData['instructionPrimaire'] = ($instruction === 'Primaire') ? 1 : 0;
                    $mappedData['instructionSecondaire'] = ($instruction === 'Moyen' || $instruction === 'Secondaire' || $instruction === 'Secondaire') ? 1 : 0;
                    $mappedData['instructionSuperieur'] = ($instruction === 'Supérieur') ? 1 : 0;

                    // Nom et Statut de l'enquête
                    $mappedData['nom_recensement'] = $recData['nom_recensement'] ?? 'SOC-MOB-' . uniqid();
                    $mappedData['statut'] = \App\Enums\RecensementStatut::SOUMIS;
                    
                    // Fallback d'assignation géographique
                    if (empty($mappedData['quartier_id']) || empty($mappedData['carre_id'])) {
                        $defaultCarre = \App\Models\Parameters\Carre::first();
                        if ($defaultCarre) {
                            $mappedData['carre_id'] = $defaultCarre->id;
                            $mappedData['quartier_id'] = $defaultCarre->quartier_id;
                        }
                    }

                    // Enregistrement de la fiche
                    $recensement = Recensement::firstOrNew(['id' => $id]);
                    $recensement->fill($mappedData);
                    
                    if (empty($recensement->uuid)) {
                        $recensement->uuid = $id;
                    }

                    if (auth()->check() && auth()->user()->agent) {
                        $recensement->enqueteur_id = auth()->user()->agent->id;
                    }

                    $recensement->save();
                    
                    // Synchronisation de la relation pivot des besoins prioritaires (Règle d'or Many-to-Many)
                    $priorites = $recData['priorites'] ?? [];
                    if (empty($priorites)) {
                        $defaultBesoin = \App\Models\Parameters\BesoinPrioritaire::first();
                        if ($defaultBesoin) {
                            $priorites = [$defaultBesoin->id];
                        }
                    }
                    $recensement->priorites()->sync($priorites);

                    $syncedIds['recensements'][] = $id;

                } catch (Exception $e) {
                    $errors[] = "Ménage [ID: {$id}]: " . $e->getMessage();
                }
            }

            // 2. Synchronisation des Maisons (Habitations)
            foreach ($maisons as $maisonData) {
                try {
                    $id = $maisonData['uuid'] ?? $maisonData['id'] ?? null;
                    if (!$id) continue;

                    $maison = Maison::firstOrNew(['id' => $id]);
                    
                    $mappedData = [
                        'numero_porte' => $maisonData['numero_porte'] ?? $maisonData['numeroPorte'] ?? null,
                        'adresse' => $maisonData['adresse'] ?? '',
                        'nombre_hommes' => (int)($maisonData['nombre_hommes'] ?? $maisonData['nombreHommes'] ?? 0),
                        'nombre_femmes' => (int)($maisonData['nombre_femmes'] ?? $maisonData['nombreFemmes'] ?? 0),
                        'nombre_enfants' => (int)($maisonData['nombre_enfants'] ?? $maisonData['nombreEnfants'] ?? 0),
                        'carre_id' => $maisonData['carre_id'] ?? $maisonData['carreId'] ?? null,
                        'recensement_id' => $maisonData['recensement_id'] ?? $maisonData['recensementId'] ?? null,
                        'reference_cadastrale' => $maisonData['reference_cadastrale'] ?? $maisonData['referenceCadastrale'] ?? null,
                        'usage_principal_id' => $maisonData['usage_principal_id'] ?? $maisonData['usagePrincipalId'] ?? $maisonData['usage_principal'] ?? $maisonData['usage'] ?? null,
                        'type_construction_id' => $maisonData['type_construction_id'] ?? $maisonData['typeConstructionId'] ?? $maisonData['type_construction'] ?? $maisonData['typeHabitation'] ?? null,
                        'statut_foncier_id' => $maisonData['statut_foncier_id'] ?? $maisonData['statutFoncierId'] ?? $maisonData['statut_foncier'] ?? $maisonData['statutFoncier'] ?? null,
                        'source_eau_id' => $maisonData['source_eau_id'] ?? $maisonData['sourceEauId'] ?? $maisonData['source_eau'] ?? $maisonData['accesEau'] ?? null,
                        'source_energie_id' => $maisonData['source_energie_id'] ?? $maisonData['sourceEnergieId'] ?? $maisonData['source_energie'] ?? $maisonData['accesElectricite'] ?? null,
                        'assainissement_id' => $maisonData['assainissement_id'] ?? $maisonData['assainissementId'] ?? $maisonData['assainissement'] ?? $maisonData['accesAssainissement'] ?? null,
                        'gestion_dechet_id' => $maisonData['gestion_dechet_id'] ?? $maisonData['gestionDechetId'] ?? $maisonData['gestion_dechet'] ?? $maisonData['gestionDechets'] ?? null,
                        'gps_latitude' => $maisonData['gps_latitude'] ?? $maisonData['gpsLatitude'] ?? null,
                        'gps_longitude' => $maisonData['gps_longitude'] ?? $maisonData['gpsLongitude'] ?? null,
                        'gps_altitude' => $maisonData['gps_altitude'] ?? $maisonData['gpsAltitude'] ?? null,
                        'gps_precision' => $maisonData['gps_precision'] ?? $maisonData['gpsPrecision'] ?? null,
                        'gps_date_capture' => $maisonData['gps_date_capture'] ?? $maisonData['gpsDateCapture'] ?? null,
                        'statut' => \App\Enums\MaisonStatut::SOUMIS,
                    ];

                    $maison->fill($mappedData);

                    if (auth()->check() && auth()->user()->agent) {
                        $maison->enqueteur_id = auth()->user()->agent->id;
                    }

                    $maison->save();
                    $syncedIds['maisons'][] = $id;

                } catch (Exception $e) {
                    $errors[] = "Habitat [ID: {$id}]: " . $e->getMessage();
                }
            }

            // 3. Synchronisation des Opérateurs Économiques
            foreach ($operateurs as $opData) {
                try {
                    $id = $opData['uuid'] ?? $opData['id'] ?? null;
                    if (!$id) continue;

                    $op = Operateur::firstOrNew(['id' => $id]);
                    
                    // Mappage de compatibilité pour l'application mobile Ionic
                    $mappedData = [];
                    $mappedData['nom_commercial'] = $opData['nomCommercial'] ?? 'Inconnu';
                    $mappedData['nom_entreprise'] = $opData['raisonSociale'] ?: ($opData['nomCommercial'] ?? 'Inconnu');
                    
                    $promoteur = $opData['promoteur'] ?? 'Inconnu';
                    $parts = explode(' ', $promoteur, 2);
                    $mappedData['promoteur_prenom'] = $parts[0] ?? 'Inconnu';
                    $mappedData['promoteur_nom'] = $parts[1] ?? 'Inconnu';
                    
                    $mappedData['telephone'] = $opData['telephone'] ?? null;
                    $mappedData['email'] = $opData['email'] ?? null;
                    $mappedData['adresse'] = $opData['adresse'] ?? '';
                    $mappedData['rccm'] = $opData['rccm'] ?? null;
                    $mappedData['nif'] = $opData['nif'] ?? null;
                    
                    // CategorieOperateur resolution
                    $catName = $opData['categorie'] ?? 'Informel';
                    $category = \App\Models\Parameters\CategorieOperateur::where('nom', 'like', "%{$catName}%")
                        ->orWhere('code', 'like', "%{$catName}%")
                        ->first();
                    if (!$category) {
                        $category = \App\Models\Parameters\CategorieOperateur::first();
                    }
                    if ($category) {
                        $mappedData['categorie_id'] = $category->id;
                    }
                    
                    // Secteur resolution
                    $secteurName = $opData['secteurActivite'] ?? 'Commerce';
                    $secteur = \App\Models\Parameters\Secteur::where('nom', 'like', "%{$secteurName}%")
                        ->orWhere('code', 'like', "%{$secteurName}%")
                        ->first();
                    if (!$secteur) {
                        $secteur = \App\Models\Parameters\Secteur::first();
                    }
                    if ($secteur) {
                        $mappedData['secteur_id'] = $secteur->id;
                    }
                    
                    // Effectifs
                    $effectif = isset($opData['effectif']) ? (int)$opData['effectif'] : 1;
                    $mappedData['effectif_total'] = $effectif;
                    $mappedData['effectif_hommes'] = $effectif;
                    $mappedData['effectif_femmes'] = 0;
                    $mappedData['effectif_permanents'] = $effectif;
                    $mappedData['effectif_temporaires'] = 0;
                    
                    // Taille entreprise
                    if ($effectif < 10) {
                        $mappedData['taille'] = \App\Enums\EntrepriseTaille::MICRO;
                    } elseif ($effectif < 50) {
                        $mappedData['taille'] = \App\Enums\EntrepriseTaille::PETITE;
                    } elseif ($effectif < 250) {
                        $mappedData['taille'] = \App\Enums\EntrepriseTaille::MOYENNE;
                    } else {
                        $mappedData['taille'] = \App\Enums\EntrepriseTaille::GRANDE;
                    }

                    // Extra fields to observations/description
                    $mappedData['adresse_descriptive'] = trim(
                        "Situation Fiscale: " . ($opData['situationFiscale'] ?? 'N/A') . "\n" .
                        "Statut Local: " . ($opData['statutLocal'] ?? 'N/A') . "\n" .
                        "Date de création: " . ($opData['dateCreation'] ?? 'N/A')
                    );
                    
                    $mappedData['statut'] = \App\Enums\OperateurStatut::SOUMIS;

                    // Fallback geographical info
                    $defaultCarre = \App\Models\Parameters\Carre::first();
                    if ($defaultCarre) {
                        $mappedData['carre_id'] = $defaultCarre->id;
                        $mappedData['quartier_id'] = $defaultCarre->quartier_id;
                    }

                    $op->fill($mappedData);
                    
                    if (empty($op->uuid)) {
                        $op->uuid = $id;
                    }

                    if (auth()->check() && auth()->user()->agent) {
                        $op->enqueteur_id = auth()->user()->agent->id;
                    }

                    $op->save();
                    $syncedIds['operateurs'][] = $id;

                } catch (Exception $e) {
                    $errors[] = "Opérateur [ID: {$id}]: " . $e->getMessage();
                }
            }
        });

        $hasFailures = !empty($errors);
        $successCount = count($syncedIds['recensements']) + count($syncedIds['maisons']) + count($syncedIds['operateurs']);

        return $this->buildResponse(
            success: !$hasFailures,
            message: $hasFailures 
                ? "Synchronisation complétée avec des erreurs de paquets." 
                : "Synchronisation réussie de {$successCount} fiche(s) terrain.",
            data: $syncedIds,
            errors: $errors,
            statusCode: $hasFailures ? 207 : 200 // Code HTTP 207 Multi-Status s'il y a des échecs partiels
        );
    }

    /**
     * API Endpoint : PULL (Téléchargement de mises à jour de fiches d'enquêtes récentes pour synchronisation bidirectionnelle)
     */
    public function pull(Request $request): JsonResponse
    {
        $request->validate([
            'last_sync_timestamp' => 'required|integer', // Horodatage UNIX de la dernière synchro
        ]);

        $lastSync = date('Y-m-d H:i:s', $request->input('last_sync_timestamp'));

        // Récupérer uniquement les fiches créées ou modifiées depuis la dernière synchro
        // L'isolation de sécurité d'enquêteur s'applique automatiquement !
        $recensements = Recensement::where('updated_at', '>=', $lastSync)->get();
        $maisons = Maison::where('updated_at', '>=', $lastSync)->get();
        $operateurs = Operateur::where('updated_at', '>=', $lastSync)->get();

        return $this->buildResponse(
            success: true,
            message: "Données de synchronisation récupérées avec succès.",
            data: [
                'recensements' => $recensements,
                'maisons' => $maisons,
                'operateurs' => $operateurs,
                'server_timestamp' => now()->timestamp
            ]
        );
    }
}
