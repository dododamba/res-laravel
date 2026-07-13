<?php

namespace App\Http\Controllers\Parameters;

use App\Http\Controllers\AbstractParameterController;
use App\Models\Parameters\Quartier;
use App\Models\Agent;
use App\Models\Affectation;
use App\Models\Parameters\Fonction;
use App\Models\Recensement;
use Illuminate\Http\Request;

class QuartierController extends AbstractParameterController
{
    protected function getModelClass(): string
    {
        return Quartier::class;
    }

    protected function getViewPrefix(): string
    {
        return 'parameters.quartier';
    }

    protected function getRoutePrefix(): string
    {
        return 'quartier';
    }

    /**
     * Liste des quartiers avec statistiques et progression d'avancement.
     */
    public function index(Request $request)
    {
        $modelClass = $this->getModelClass();
        $searchTerm = $request->query('q', '');
        $showArchived = $request->boolean('archived', false);

        $query = $modelClass::query();

        if ($showArchived) {
            $query->onlyTrashed();
        }

        if (!empty($searchTerm)) {
            $query->search($searchTerm);
        }

        $entities = $query->orderBy('ordre_affichage', 'asc')->get();

        // Statistiques globales de l'Administration Territoriale
        $stats = [
            'total_quartiers' => Quartier::count(),
            'total_carres' => \App\Models\Parameters\Carre::count(),
            'total_delegues' => Affectation::where('statut', 'actif')
                ->whereHas('fonction', function($q) {
                    $q->where('code', 'DELEGUE');
                })
                ->distinct('agent_id')
                ->count('agent_id'),
            'total_chefs' => Affectation::where('statut', 'actif')
                ->whereHas('fonction', function($q) {
                    $q->where('code', 'CHEF_CARRE');
                })
                ->distinct('agent_id')
                ->count('agent_id'),
        ];

        // Règle métier Symfony : Calcul d'avancement réel ou estimé par Quartier
        $progressions = [];
        foreach ($entities as $quartier) {
            $count = Recensement::where('quartier_id', $quartier->id)->count();
            $progress = min(100, (int) round(($count / 50) * 100)); // Cible douce de 50 ménages par quartier
            $progressions[$quartier->id] = max(10, $progress); // Seuil visuel minimal
        }

        return view($this->getViewPrefix() . '.index', [
            'entities' => $entities,
            'searchTerm' => $searchTerm,
            'showArchived' => $showArchived,
            'routePrefix' => $this->getRoutePrefix(),
            'stats' => $stats,
            'progressions' => $progressions,
        ]);
    }

    /**
     * Formulaire de création du quartier avec liste des agents.
     */
    public function create()
    {
        $agents = Agent::with('personne')->get();
        
        return view($this->getViewPrefix() . '.create', [
            'routePrefix' => $this->getRoutePrefix(),
            'agents' => $agents,
        ]);
    }

    /**
     * Enregistrement du quartier et de son affectation Délégué initiale.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'couleur' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'icone' => 'nullable|string|max:50',
            'ordre_affichage' => 'nullable|integer',
            'delegue_id' => 'nullable|exists:agents,id',
        ]);

        $entity = Quartier::create([
            'nom' => $request->input('nom'),
            'code' => $request->input('code'),
            'description' => $request->input('description'),
            'couleur' => $request->input('couleur'),
            'icone' => $request->input('icone'),
            'ordre_affichage' => $request->input('ordre_affichage', 0),
            'is_active' => $request->boolean('is_active', true),
        ]);

        // Gestion de l'affectation temporelle du Délégué
        $delegueId = $request->input('delegue_id');
        if ($delegueId) {
            $fonction = Fonction::firstOrCreate(
                ['code' => 'DELEGUE'],
                [
                    'nom' => 'Délégué de Quartier',
                    'description' => 'Superviseur de quartier',
                    'ordre_affichage' => 30,
                    'is_active' => true,
                ]
            );

            Affectation::create([
                'agent_id' => $delegueId,
                'fonction_id' => $fonction->id,
                'quartier_id' => $entity->id,
                'date_debut' => now(),
                'statut' => 'actif',
            ]);
        }

        return redirect()
            ->route($this->getRoutePrefix() . '.index')
            ->with('success', "Le quartier a été créé avec succès.");
    }

    /**
     * Formulaire d'édition.
     */
    public function edit(string $id)
    {
        $entity = Quartier::findOrFail($id);
        $agents = Agent::with('personne')->get();

        return view($this->getViewPrefix() . '.edit', [
            'entity' => $entity,
            'routePrefix' => $this->getRoutePrefix(),
            'agents' => $agents,
        ]);
    }

    /**
     * Mise à jour du quartier et transition d'affectation de Délégué si nécessaire.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'couleur' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'icone' => 'nullable|string|max:50',
            'ordre_affichage' => 'nullable|integer',
            'delegue_id' => 'nullable|exists:agents,id',
        ]);

        $entity = Quartier::findOrFail($id);
        $entity->update([
            'nom' => $request->input('nom'),
            'code' => $request->input('code'),
            'description' => $request->input('description'),
            'couleur' => $request->input('couleur'),
            'icone' => $request->input('icone'),
            'ordre_affichage' => $request->input('ordre_affichage', $entity->ordre_affichage),
            'is_active' => $request->boolean('is_active', $entity->is_active),
        ]);

        $currentDelegue = $entity->delegue;
        $newDelegueId = $request->input('delegue_id');

        // Si le délégué de quartier a changé, on applique la règle métier Symfony
        if ($newDelegueId !== ($currentDelegue ? $currentDelegue->id : null)) {
            // 1. Terminer l'ancienne affectation de DELEGUE active pour ce quartier
            Affectation::where('quartier_id', $entity->id)
                ->where('statut', 'actif')
                ->whereHas('fonction', function($q) {
                    $q->where('code', 'DELEGUE');
                })
                ->update([
                    'statut' => 'termine',
                    'date_fin' => now(),
                ]);

            // 2. Créer la nouvelle affectation si un agent est sélectionné
            if ($newDelegueId) {
                $fonction = Fonction::firstOrCreate(
                    ['code' => 'DELEGUE'],
                    [
                        'nom' => 'Délégué de Quartier',
                        'description' => 'Superviseur de quartier',
                        'ordre_affichage' => 30,
                        'is_active' => true,
                    ]
                );

                Affectation::create([
                    'agent_id' => $newDelegueId,
                    'fonction_id' => $fonction->id,
                    'quartier_id' => $entity->id,
                    'date_debut' => now(),
                    'statut' => 'actif',
                ]);
            }
        }

        return redirect()
            ->route($this->getRoutePrefix() . '.index')
            ->with('success', "Le quartier a été modifié avec succès.");
    }

    /**
     * Détails complets d'un quartier avec ses carrés et statistiques d'avancement.
     */
    public function show(string $id)
    {
        $entity = Quartier::with(['carres'])->findOrFail($id);

        // Simulation de statistiques fidèles à Symfony
        $idHash = crc32($entity->id);
        srand($idHash);

        $habitantsTotal = 0;
        $habitantsRecenses = 0;

        foreach ($entity->carres as $carre) {
            $maisonsCount = $carre->maisons()->count();
            // Estimation si aucune maison n'est saisie, ou calcul basé sur la saisie terrain
            $habitants = $maisonsCount > 0 ? $maisonsCount * 6 : rand(120, 280);
            $habitantsTotal += $habitants;
            $habitantsRecenses += (int) ($habitants * (rand(72, 98) / 100));
        }

        srand(); // Réinitialisation du générateur aléatoire global

        $progression = $habitantsTotal > 0 ? (int) (($habitantsRecenses / $habitantsTotal) * 100) : 0;

        return view($this->getViewPrefix() . '.show', [
            'entity' => $entity,
            'routePrefix' => $this->getRoutePrefix(),
            'habitants_total' => $habitantsTotal,
            'habitants_recenses' => $habitantsRecenses,
            'progression' => $progression,
        ]);
    }
}
