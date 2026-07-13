<?php

namespace App\Http\Controllers\Parameters;

use App\Http\Controllers\AbstractParameterController;
use App\Models\Parameters\Carre;
use App\Models\Parameters\Quartier;
use App\Models\Agent;
use App\Models\Affectation;
use App\Models\Parameters\Fonction;
use Illuminate\Http\Request;

class CarreController extends AbstractParameterController
{
    protected function getModelClass(): string
    {
        return Carre::class;
    }

    protected function getViewPrefix(): string
    {
        return 'parameters.carre';
    }

    protected function getRoutePrefix(): string
    {
        return 'carre';
    }

    /**
     * Formulaire de création d'un carré (bloc).
     */
    public function create()
    {
        $quartiers = Quartier::orderBy('nom')->get();
        $agents = Agent::with('personne')->get();

        return view($this->getViewPrefix() . '.create', [
            'routePrefix' => $this->getRoutePrefix(),
            'quartiers' => $quartiers,
            'agents' => $agents,
            // Optionnel : lier directement à un quartier via GET param (comme l'ajout depuis les détails d'un quartier)
            'selectedQuartierId' => request('quartier_id'),
        ]);
    }

    /**
     * Enregistrement en base de données et affectation du Chef de Carré.
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
            'quartier_id' => 'required|exists:quartiers,id',
            'chef_carre_id' => 'nullable|exists:agents,id',
        ]);

        $entity = Carre::create([
            'nom' => $request->input('nom'),
            'code' => $request->input('code'),
            'description' => $request->input('description'),
            'couleur' => $request->input('couleur'),
            'icone' => $request->input('icone'),
            'ordre_affichage' => $request->input('ordre_affichage', 0),
            'quartier_id' => $request->input('quartier_id'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        // Gestion de l'affectation temporelle du Chef de Carré
        $chefCarreId = $request->input('chef_carre_id');
        if ($chefCarreId) {
            $fonction = Fonction::firstOrCreate(
                ['code' => 'CHEF_CARRE'],
                [
                    'nom' => 'Chef de Carré',
                    'description' => 'Superviseur de carré',
                    'ordre_affichage' => 20,
                    'is_active' => true,
                ]
            );

            Affectation::create([
                'agent_id' => $chefCarreId,
                'fonction_id' => $fonction->id,
                'carre_id' => $entity->id,
                'date_debut' => now(),
                'statut' => 'actif',
            ]);
        }

        return redirect()
            ->route($this->getRoutePrefix() . '.index')
            ->with('success', "Le carré géographique a été créé avec succès.");
    }

    /**
     * Formulaire d'édition.
     */
    public function edit(string $id)
    {
        $entity = Carre::findOrFail($id);
        $quartiers = Quartier::orderBy('nom')->get();
        $agents = Agent::with('personne')->get();

        return view($this->getViewPrefix() . '.edit', [
            'entity' => $entity,
            'routePrefix' => $this->getRoutePrefix(),
            'quartiers' => $quartiers,
            'agents' => $agents,
        ]);
    }

    /**
     * Mise à jour en base de données et gestion d'historique de superviseurs.
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
            'quartier_id' => 'required|exists:quartiers,id',
            'chef_carre_id' => 'nullable|exists:agents,id',
        ]);

        $entity = Carre::findOrFail($id);
        $entity->update([
            'nom' => $request->input('nom'),
            'code' => $request->input('code'),
            'description' => $request->input('description'),
            'couleur' => $request->input('couleur'),
            'icone' => $request->input('icone'),
            'ordre_affichage' => $request->input('ordre_affichage', $entity->ordre_affichage),
            'quartier_id' => $request->input('quartier_id'),
            'is_active' => $request->boolean('is_active', $entity->is_active),
        ]);

        $currentChef = $entity->chef_carre;
        $newChefId = $request->input('chef_carre_id');

        // Si le superviseur/chef de carré a changé, on applique la règle métier Symfony
        if ($newChefId !== ($currentChef ? $currentChef->id : null)) {
            // 1. Terminer l'ancienne affectation de CHEF_CARRE active pour ce carré
            Affectation::where('carre_id', $entity->id)
                ->where('statut', 'actif')
                ->whereHas('fonction', function($q) {
                    $q->where('code', 'CHEF_CARRE');
                })
                ->update([
                    'statut' => 'termine',
                    'date_fin' => now(),
                ]);

            // 2. Créer la nouvelle affectation si un superviseur est choisi
            if ($newChefId) {
                $fonction = Fonction::firstOrCreate(
                    ['code' => 'CHEF_CARRE'],
                    [
                        'nom' => 'Chef de Carré',
                        'description' => 'Superviseur de carré',
                        'ordre_affichage' => 20,
                        'is_active' => true,
                    ]
                );

                Affectation::create([
                    'agent_id' => $newChefId,
                    'fonction_id' => $fonction->id,
                    'carre_id' => $entity->id,
                    'date_debut' => now(),
                    'statut' => 'actif',
                ]);
            }
        }

        return redirect()
            ->route($this->getRoutePrefix() . '.index')
            ->with('success', "Le carré a été modifié avec succès.");
    }

    /**
     * Détails complets d'un carré avec ses habitations et son équipe.
     */
    public function show(string $id)
    {
        $entity = Carre::with(['quartier', 'maisons.enqueteur.personne'])->findOrFail($id);

        // Simulation de statistiques d'avancement
        $idHash = crc32($entity->id);
        srand($idHash);

        $habitantsTotal = $entity->maisons->count() * 6 ?: rand(25, 80);
        $habitantsRecenses = (int) ($habitantsTotal * (rand(76, 100) / 100));

        srand(); // Réinitialisation

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
