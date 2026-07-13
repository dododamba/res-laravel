<?php

namespace App\Http\Controllers;

use App\Models\Maison;
use App\Models\Parameters\Carre;
use App\Enums\MaisonStatut;
use App\Services\Workflow\MaisonWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class MaisonController extends Controller
{
    /**
     * Liste des fiches d'habitations (Maisons)
     */
    public function index(Request $request)
    {
        // L'isolation de sécurité d'enquêteur s'applique automatiquement via le scope global !
        $query = Maison::query()
            ->with(['carre', 'enqueteur.personne'])
            ->latest();

        // Filtrage par Carré si spécifié
        if ($request->filled('carre_id')) {
            $query->where('carre_id', $request->input('carre_id'));
        }

        // Filtrage par statut
        if ($request->filled('statut')) {
            $query->where('statut', $request->input('statut'));
        }

        $maisons = $query->paginate(15)->withQueryString();
        $carres = Carre::active()->orderBy('nom')->get();

        return view('maison.index', compact('maisons', 'carres'));
    }

    /**
     * Formulaire de saisie d'une habitation.
     */
    public function create()
    {
        // Vérification de la permission via Policy (MaisonPolicy@create)
        Gate::authorize('create', Maison::class);

        $carres = Carre::active()->orderBy('nom')->get();
        return view('maison.create', compact('carres'));
    }

    /**
     * Enregistrement de la fiche d'habitation et de ses médias.
     */
    public function store(Request $request)
    {
        Gate::authorize('create', Maison::class);

        $request->validate([
            'numero_porte' => 'required|integer|min:1',
            'adresse' => 'required|string|max:255',
            'carre_id' => 'required|exists:carres,id',
            'nombre_hommes' => 'required|integer|min:0',
            'nombre_femmes' => 'required|integer|min:0',
            'nombre_enfants' => 'required|integer|min:0',
            'gps_latitude' => 'nullable|numeric|between:-90,90',
            'gps_longitude' => 'nullable|numeric|between:-180,180',
            'photo' => 'nullable|image|max:5120', // Max 5Mo
            'document_cadastre' => 'nullable|file|mimes:pdf,jpeg,png|max:10240', // Max 10Mo
        ]);

        $maison = new Maison();
        $maison->fill($request->all());
        $maison->statut = MaisonStatut::BROUILLON;

        // Trace automatique de l'enquêteur connecté
        if (auth()->user()->agent) {
            $maison->enqueteur_id = auth()->user()->agent->id;
        }

        $maison->save();

        // Téléversement polymorphique de la photo d'habitation (Spatie Media Library)
        if ($request->hasFile('photo')) {
            $maison->addMediaFromRequest('photo')
                   ->toMediaCollection('photos_habitation');
        }

        // Téléversement du document de cadastre
        if ($request->hasFile('document_cadastre')) {
            $maison->addMediaFromRequest('document_cadastre')
                   ->toMediaCollection('documents_cadastre');
        }

        // Consignation automatique d'historique
        $maison->historiques()->create([
            'action' => 'creation',
            'details' => [
                'message' => 'Création initiale de la fiche d\'habitation sur l\'interface bureau',
                'adresse' => $maison->adresse,
                'porte' => $maison->numero_porte
            ],
            'user_identifier' => auth()->user()->email
        ]);

        return redirect()
            ->route('maison.index')
            ->with('success', "La fiche d'habitation N°{$maison->numero_porte} a été créée avec ses justificatifs.");
    }

    /**
     * Visualisation d'une fiche d'habitation.
     */
    public function show(Maison $maison)
    {
        // Sécurité Policy (MaisonPolicy@view)
        Gate::authorize('view', $maison);

        // Chargement explicite des relations et des historiques
        $maison->load(['carre', 'enqueteur.personne', 'controleur.personne', 'validateur.personne', 'historiques']);

        return view('maison.show', compact('maison'));
    }

    /**
     * Formulaire d'édition.
     */
    public function edit(Maison $maison)
    {
        Gate::authorize('update', $maison);

        $carres = Carre::active()->orderBy('nom')->get();

        return view('maison.edit', compact('maison', 'carres'));
    }

    /**
     * Enregistrement des modifications.
     */
    public function update(Request $request, Maison $maison)
    {
        Gate::authorize('update', $maison);

        $request->validate([
            'numero_porte' => 'required|integer|min:1',
            'adresse' => 'required|string|max:255',
            'carre_id' => 'required|exists:carres,id',
            'nombre_hommes' => 'required|integer|min:0',
            'nombre_femmes' => 'required|integer|min:0',
            'nombre_enfants' => 'required|integer|min:0',
            'gps_latitude' => 'nullable|numeric|between:-90,90',
            'gps_longitude' => 'nullable|numeric|between:-180,180',
            'photo' => 'nullable|image|max:5120',
            'document_cadastre' => 'nullable|file|mimes:pdf,jpeg,png|max:10240',
        ]);

        $changes = [];
        $fields = ['numero_porte', 'adresse', 'carre_id', 'nombre_hommes', 'nombre_femmes', 'nombre_enfants', 'gps_latitude', 'gps_longitude'];
        foreach ($fields as $field) {
            if ($maison->{$field} != $request->input($field)) {
                $changes[$field] = [
                    'before' => $maison->{$field},
                    'after' => $request->input($field)
                ];
            }
        }

        $maison->update($request->all());

        if ($request->hasFile('photo')) {
            $maison->clearMediaCollection('photos_habitation');
            $maison->addMediaFromRequest('photo')->toMediaCollection('photos_habitation');
        }

        if ($request->hasFile('document_cadastre')) {
            $maison->clearMediaCollection('documents_cadastre');
            $maison->addMediaFromRequest('document_cadastre')->toMediaCollection('documents_cadastre');
        }

        if (!empty($changes)) {
            $maison->historiques()->create([
                'action' => 'modification',
                'details' => [
                    'message' => 'Mise à jour des données d\'habitations',
                    'changes' => $changes
                ],
                'user_identifier' => auth()->user()->email
            ]);
        }

        return redirect()
            ->route('maison.show', $maison)
            ->with('success', "La fiche d'habitation a été mise à jour.");
    }

    /**
     * Suppression d'une fiche d'habitation.
     */
    public function destroy(Maison $maison)
    {
        Gate::authorize('delete', $maison);

        $maison->historiques()->create([
            'action' => 'archivage',
            'details' => ['message' => 'Archivage de la fiche d\'habitation par l\'utilisateur'],
            'user_identifier' => auth()->user()->email
        ]);

        $maison->delete();

        return redirect()
            ->route('maison.index')
            ->with('success', "La fiche d'habitation a été archivée avec succès.");
    }

    /**
     * Exécute une transition d'état métier (workflow) pour l'habitation.
     */
    public function transition(Request $request, Maison $maison, MaisonWorkflowService $workflow)
    {
        $request->validate([
            'target_status' => 'required|string',
            'motif' => 'nullable|string|max:255',
        ]);

        $statusValue = $request->input('target_status');
        $targetStatus = MaisonStatut::from($statusValue);
        $motif = $request->input('motif', '');

        try {
            // Applique la transition d'état et historise de manière transactionnelle
            $workflow->transitionTo(
                $maison,
                $targetStatus,
                operator: auth()->user()->email,
                motif: $motif
            );

            return redirect()
                ->route('maison.show', $maison)
                ->with('success', "Le statut de l'habitation a été mis à jour avec succès : {$targetStatus->label()}.");

        } catch (\Exception $e) {
            return redirect()
                ->route('maison.show', $maison)
                ->with('error', $e->getMessage());
        }
    }
}
