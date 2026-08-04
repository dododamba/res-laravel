<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

abstract class AbstractParameterController extends Controller
{
    /**
     * Retourne le nom de la classe du modèle géré (ex: Quartier::class).
     */
    abstract protected function getModelClass(): string;

    /**
     * Retourne le préfixe du nom des vues Blade (ex: 'parameters.quartier').
     */
    abstract protected function getViewPrefix(): string;

    /**
     * Retourne le préfixe du nom de la route (ex: 'quartier').
     */
    abstract protected function getRoutePrefix(): string;

    /**
     * Retourne le label lisible du paramètre (ex: 'Besoins Prioritaires').
     * Peut être surchargé par les contrôleurs enfants.
     */
    protected function getParameterLabel(): string
    {
        return ucwords(str_replace(['-', '_'], ' ', $this->getRoutePrefix()));
    }

    /**
     * Retourne le label singulier du paramètre.
     */
    protected function getParameterLabelSingular(): string
    {
        return rtrim($this->getParameterLabel(), 's');
    }

    /**
     * Résout le nom de la vue : utilise la vue spécifique si elle existe,
     * sinon tombe sur la vue générique dans parameters/_generic.
     */
    protected function resolveView(string $suffix): string
    {
        $specificView = $this->getViewPrefix() . '.' . $suffix;
        if (View::exists($specificView)) {
            return $specificView;
        }
        return 'parameters._generic.' . $suffix;
    }

    /**
     * Données partagées injectées dans toutes les vues de paramètres.
     */
    protected function sharedViewData(): array
    {
        return [
            'routePrefix' => $this->getRoutePrefix(),
            'parameterLabel' => $this->getParameterLabel(),
            'parameterLabelSingular' => $this->getParameterLabelSingular(),
        ];
    }

    /**
     * Liste des enregistrements avec recherche et filtrage des archivés.
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
            $query->search($searchTerm); // Scope local hérité de BaseParameterModel
        }

        $entities = $query->orderBy('ordre_affichage', 'asc')->get();

        return view($this->resolveView('index'), array_merge($this->sharedViewData(), [
            'entities' => $entities,
            'searchTerm' => $searchTerm,
            'showArchived' => $showArchived,
        ]));
    }

    /**
     * Formulaire de création.
     */
    public function create()
    {
        return view($this->resolveView('create'), $this->sharedViewData());
    }

    /**
     * Enregistrement en base de données.
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
        ]);

        $modelClass = $this->getModelClass();
        
        $entity = $modelClass::create([
            'nom' => $request->input('nom'),
            'code' => $request->input('code'),
            'description' => $request->input('description'),
            'couleur' => $request->input('couleur'),
            'icone' => $request->input('icone'),
            'ordre_affichage' => $request->input('ordre_affichage', 0),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route($this->getRoutePrefix() . '.index')
            ->with('success', "L'enregistrement a été créé avec succès.");
    }

    /**
     * Formulaire d'édition.
     */
    public function edit(string $id)
    {
        $modelClass = $this->getModelClass();
        $entity = $modelClass::findOrFail($id);

        return view($this->resolveView('edit'), array_merge($this->sharedViewData(), [
            'entity' => $entity,
        ]));
    }

    /**
     * Mise à jour en base de données.
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
        ]);

        $modelClass = $this->getModelClass();
        $entity = $modelClass::findOrFail($id);

        $entity->update([
            'nom' => $request->input('nom'),
            'code' => $request->input('code'),
            'description' => $request->input('description'),
            'couleur' => $request->input('couleur'),
            'icone' => $request->input('icone'),
            'ordre_affichage' => $request->input('ordre_affichage', $entity->ordre_affichage),
            'is_active' => $request->boolean('is_active', $entity->is_active),
        ]);

        return redirect()
            ->route($this->getRoutePrefix() . '.index')
            ->with('success', "L'enregistrement a été modifié avec succès.");
    }

    /**
     * Archivage souple (Soft Delete).
     */
    public function destroy(string $id)
    {
        $modelClass = $this->getModelClass();
        $entity = $modelClass::findOrFail($id);
        
        $entity->delete();

        return redirect()
            ->route($this->getRoutePrefix() . '.index')
            ->with('success', "L'enregistrement a été archivé avec succès.");
    }

    /**
     * Restauration d'un enregistrement archivé.
     */
    public function restore(string $id)
    {
        $modelClass = $this->getModelClass();
        $entity = $modelClass::onlyTrashed()->findOrFail($id);
        
        $entity->restore();

        return redirect()
            ->route($this->getRoutePrefix() . '.index')
            ->with('success', "L'enregistrement a été restauré avec succès.");
    }

    /**
     * Inverse l'état actif/inactif d'un paramètre.
     */
    public function toggle(string $id)
    {
        $modelClass = $this->getModelClass();
        $entity = $modelClass::findOrFail($id);
        
        $entity->is_active = !$entity->is_active;
        $entity->save();

        return redirect()
            ->route($this->getRoutePrefix() . '.index')
            ->with('success', "Le statut d'activité a été mis à jour.");
    }

    /**
     * Duplique un enregistrement existant.
     */
    public function duplicate(string $id)
    {
        $modelClass = $this->getModelClass();
        $entity = $modelClass::findOrFail($id);
        
        $clone = $entity->replicate();
        $clone->nom = $entity->nom . ' (Copie)';
        $clone->code = $entity->code ? $entity->code . '-COPY' : null;
        $clone->is_default = false;
        $clone->slug = Str::slug($clone->nom) . '-copie-' . rand(10, 99);
        $clone->save();

        return redirect()
            ->route($this->getRoutePrefix() . '.index')
            ->with('success', "L'enregistrement a été dupliqué avec succès sous le nom '{$clone->nom}'.");
    }
}
