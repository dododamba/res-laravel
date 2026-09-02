<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaveRecensementRequest;
use App\Models\Recensement;
use App\Models\Maison;
use App\Models\Operateur;
use App\Models\Parameters\Carre;
use App\Services\TaxAssignmentService;
use App\Enums\RecensementStatut;
use App\Enums\MaisonStatut;
use App\Enums\OperateurStatut;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;

use App\Services\AgentScopeService;

class SurveyApiController extends Controller
{
    use ApiResponse; // Fournit buildResponse() et renderData() unifiés

    public function __construct(
        protected TaxAssignmentService $assignmentService,
        protected AgentScopeService $scopeService
    ) {}

    /**
     * Endpoint API : Création d'une enquête de Ménage (Recensement)
     */
    public function createRecensement(SaveRecensementRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $quartierId = $validated['quartier_id'] ?? $validated['quartierId'] ?? null;
            if ($quartierId && !$this->scopeService->canAccessQuartier($quartierId)) {
                return $this->buildResponse(
                    success: false,
                    message: "Accès refusé : Vous n'êtes pas autorisé à enregistrer des données pour ce quartier.",
                    statusCode: 403
                );
            }

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
     * Tente de résoudre une valeur en UUID valide pour les clés étrangères paramétriques.
     */
    protected function resolveParamId(?string $value, string $modelClass): ?string
    {
        if (empty($value)) {
            return null;
        }

        if (Str::isUuid($value)) {
            return $value;
        }

        try {
            $found = $modelClass::where('id', $value)
                ->orWhere('nom', 'like', "%{$value}%")
                ->orWhere('code', 'like', "%{$value}%")
                ->first();

            return $found ? (string) $found->id : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Attache un média encodé en Base64 à la maison.
     */
    protected function attachBase64Media(Maison $maison, string $base64String, string $docType): void
    {
        try {
            $ext = 'jpg';
            $dataStr = $base64String;

            if (preg_match('/^data:image\/(\w+);base64,/', $base64String, $matches)) {
                $ext = strtolower($matches[1]);
                $dataStr = substr($base64String, strpos($base64String, ',') + 1);
            } elseif (preg_match('/^data:application\/pdf;base64,/', $base64String)) {
                $ext = 'pdf';
                $dataStr = substr($base64String, strpos($base64String, ',') + 1);
            }

            $decoded = base64_decode($dataStr);
            if ($decoded === false) return;

            $tempPath = sys_get_temp_dir() . '/' . Str::uuid() . '.' . $ext;
            file_put_contents($tempPath, $decoded);

            $collection = ($docType === 'foncier' || $docType === 'cadastre') ? 'documents_cadastre' : 'photos_habitation';
            $maison->addMedia($tempPath)->toMediaCollection($collection);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Erreur lors de l'attachement du média base64: " . $e->getMessage());
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
            $maison->id = $data['uuid'] ?? $data['id'] ?? (string) Str::uuid();
            $maison->numero_porte = $data['numeroPorte'] ?? $data['numero_porte'] ?? null;
            $maison->adresse = $data['adresse'] ?? '';
            $maison->nombre_hommes = (int)($data['nombreHommes'] ?? $data['nombre_hommes'] ?? 0);
            $maison->nombre_femmes = (int)($data['nombreFemmes'] ?? $data['nombre_femmes'] ?? 0);
            $maison->nombre_enfants = (int)($data['nombreEnfants'] ?? $data['nombre_enfants'] ?? 0);

            // Caractéristiques étendues du bâtiment
            $maison->annee_construction = isset($data['anneeConstruction']) || isset($data['annee_construction']) ? (int)($data['anneeConstruction'] ?? $data['annee_construction']) : null;
            $maison->nombre_pieces = isset($data['nombrePieces']) || isset($data['nombre_pieces']) ? (int)($data['nombrePieces'] ?? $data['nombre_pieces']) : null;
            $maison->nombre_etages = isset($data['nombreEtages']) || isset($data['nombre_etages']) ? (int)($data['nombreEtages'] ?? $data['nombre_etages']) : null;
            $maison->occupation = $data['occupation'] ?? null;
            $maison->materiau_murs = $data['materiauMurs'] ?? $data['materiau_murs'] ?? null;
            $maison->materiau_toiture = $data['materiauToiture'] ?? $data['materiau_toiture'] ?? null;
            $maison->materiau_sol = $data['materiauSol'] ?? $data['materiau_sol'] ?? null;
            $maison->etat_general = $data['etatGeneral'] ?? $data['etat_general'] ?? null;
            $maison->acces_voirie = $data['accesVoirie'] ?? $data['acces_voirie'] ?? null;
            $maison->acces_internet = $data['accesInternet'] ?? $data['acces_internet'] ?? null;

            // GPS & Métadonnées
            $maison->gps_latitude = $data['gpsLatitude'] ?? $data['gps_latitude'] ?? null;
            $maison->gps_longitude = $data['gpsLongitude'] ?? $data['gps_longitude'] ?? null;
            $maison->gps_altitude = $data['gpsAltitude'] ?? $data['gps_altitude'] ?? null;
            $maison->gps_precision = $data['gpsPrecision'] ?? $data['gps_precision'] ?? null;
            $maison->gps_date_capture = $data['gpsDateCapture'] ?? $data['gps_date_capture'] ?? null;

            $maison->proprietaire_nom = $data['proprietaire_nom'] ?? $data['proprietaireNom'] ?? null;
            $maison->proprietaire_telephone = $data['proprietaire_telephone'] ?? $data['proprietaireTelephone'] ?? null;
            $maison->statut = MaisonStatut::SOUMIS;

            $maison->reference_cadastrale = $data['referenceCadastrale'] ?? $data['reference_cadastrale'] ?? null;

            // Résolution des paramètres en UUIDs valides
            $maison->usage_principal_id = $this->resolveParamId($data['usage_principal_id'] ?? $data['usagePrincipalId'] ?? $data['usage_principal'] ?? $data['usage'] ?? null, \App\Models\Parameters\CategorieActivite::class);
            $maison->type_construction_id = $this->resolveParamId($data['type_construction_id'] ?? $data['typeConstructionId'] ?? $data['type_construction'] ?? $data['typeHabitation'] ?? null, \App\Models\Parameters\TypeBatiment::class);
            $maison->statut_foncier_id = $this->resolveParamId($data['statut_foncier_id'] ?? $data['statutFoncierId'] ?? $data['statut_foncier'] ?? $data['statutFoncier'] ?? null, \App\Models\Parameters\TypePropriete::class);
            $maison->source_eau_id = $this->resolveParamId($data['source_eau_id'] ?? $data['sourceEauId'] ?? $data['source_eau'] ?? $data['accesEau'] ?? null, \App\Models\Parameters\SourceEau::class);
            $maison->source_energie_id = $this->resolveParamId($data['source_energie_id'] ?? $data['sourceEnergieId'] ?? $data['source_energie'] ?? $data['accesElectricite'] ?? null, \App\Models\Parameters\SourceEnergie::class);
            $maison->assainissement_id = $this->resolveParamId($data['assainissement_id'] ?? $data['assainissementId'] ?? $data['assainissement'] ?? $data['accesAssainissement'] ?? null, \App\Models\Parameters\Assainissement::class);
            $maison->gestion_dechet_id = $this->resolveParamId($data['gestion_dechet_id'] ?? $data['gestionDechetId'] ?? $data['gestion_dechet'] ?? $data['gestionDechets'] ?? null, \App\Models\Parameters\GestionDechet::class);

            if (isset($data['carre_id']) || isset($data['carreId'])) {
                $maison->carre_id = $data['carre_id'] ?? $data['carreId'];
            }

            if ($maison->carre_id && !$this->scopeService->canAccessCarre($maison->carre_id)) {
                return $this->buildResponse(
                    success: false,
                    message: "Accès refusé : Vous n'êtes pas autorisé à enregistrer une habitation dans ce carré.",
                    statusCode: 403
                );
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

            // Attachement des photos et documents Base64
            if (isset($data['documents']) && is_array($data['documents'])) {
                foreach ($data['documents'] as $doc) {
                    $base64 = $doc['base64'] ?? $doc['preview'] ?? null;
                    if ($base64 && is_string($base64) && (str_starts_with($base64, 'data:') || strlen($base64) > 100)) {
                        $this->attachBase64Media($maison, $base64, $doc['type'] ?? 'facade');
                    }
                }
            }

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
            $op->nom_commercial = $data['nomCommercial'] ?? $data['nom_commercial'] ?? $op->nom_entreprise;
            $op->rccm = $data['rccm'] ?? null;
            $op->nif = $data['nif'] ?? null;
            $tableFields = ['adresse', 'telephone', 'gps_latitude', 'gps_longitude'];
            foreach ($tableFields as $field) {
                if (isset($data[$field])) {
                    $op->{$field} = $data[$field];
                }
            }
            $op->statut = OperateurStatut::SOUMIS;

            $op->categorie_id = $data['categorie_id'] ?? $data['categorieId'] ?? null;
            $op->carre_id = $data['carre_id'] ?? $data['carreId'] ?? null;
            $op->quartier_id = $data['quartier_id'] ?? $data['quartierId'] ?? null;
            if (empty($op->carre_id)) {
                $defaultCarre = \App\Models\Parameters\Carre::first();
                if ($defaultCarre) {
                    $op->carre_id = $defaultCarre->id;
                    $op->quartier_id = $defaultCarre->quartier_id;
                }
            }

            if ($op->quartier_id && !$this->scopeService->canAccessQuartier($op->quartier_id)) {
                return $this->buildResponse(
                    success: false,
                    message: "Accès refusé : Vous n'êtes pas autorisé à enregistrer un opérateur dans ce quartier.",
                    statusCode: 403
                );
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

            if ($op->categorie_id) {
                $this->assignmentService->autoAssignTaxesForOperateur($op);
            }

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
