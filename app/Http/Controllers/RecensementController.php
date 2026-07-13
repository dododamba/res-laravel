<?php

namespace App\Http\Controllers;

use App\Models\Recensement;
use App\Models\Parameters\Quartier;
use App\Models\Parameters\Carre;
use App\Models\Parameters\BesoinPrioritaire;
use App\Enums\RecensementStatut;
use App\Http\Requests\SaveRecensementRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;

class RecensementController extends Controller
{
    /**
     * Liste des fiches de recensements de ménages.
     */
    public function index(Request $request)
    {
        // L'isolation de sécurité d'accès Enquêteur s'applique automatiquement via le scope global !
        $query = Recensement::query()
            ->with(['quartier', 'carre', 'enqueteur.personne'])
            ->latest();

        // Recherche par nom de chef de ménage
        if ($request->filled('q')) {
            $search = $request->input('q');
            $query->where(function ($q) use ($search) {
                $q->where('chef_nom', 'like', "%{$search}%")
                  ->orWhere('chef_prenom', 'like', "%{$search}%")
                  ->orWhere('nom_recensement', 'like', "%{$search}%");
            });
        }

        // Filtres géographiques
        if ($request->filled('quartier_id')) {
            $query->where('quartier_id', $request->input('quartier_id'));
        }
        if ($request->filled('statut')) {
            $query->where('statut', $request->input('statut'));
        }

        $recensements = $query->paginate(15)->withQueryString();
        
        $quartiers = Quartier::active()->orderBy('nom')->get();

        return view('recensement.index', compact('recensements', 'quartiers'));
    }

    /**
     * Formulaire d'ouverture d'un nouveau recensement.
     */
    public function create()
    {
        $quartiers = Quartier::active()->orderBy('nom')->get();
        $carres = Carre::active()->orderBy('nom')->get();
        $priorites = BesoinPrioritaire::active()->orderBy('ordre_affichage')->get();

        return view('recensement.create', compact('quartiers', 'carres', 'priorites'));
    }

    /**
     * Enregistrement en base de données.
     */
    public function store(SaveRecensementRequest $request)
    {
        $recensement = DB::transaction(function () use ($request) {
            $rec = new Recensement();
            $rec->fill($request->validated());
            $rec->statut = RecensementStatut::BROUILLON;

            if (auth()->user()->agent) {
                $rec->enqueteur_id = auth()->user()->agent->id;
            }

            $rec->save();

            // Synchronisation de la relation pivot ManyToMany des besoins prioritaires
            if ($request->has('priorites')) {
                $rec->priorites()->sync($request->input('priorites'));
            }

            // Journalisation de la timeline de statut
            $rec->historiques()->create([
                'action' => 'creation',
                'details' => [
                    'message' => 'Création initiale du ménage sur l\'interface bureau',
                    'chef' => "{$rec->chef_prenom} {$rec->chef_nom}"
                ],
                'user_identifier' => auth()->user()->email
            ]);

            return $rec;
        });

        return redirect()
            ->route('recensement.show', $recensement)
            ->with('success', "Le recensement du ménage de {$recensement->chef_prenom} {$recensement->chef_nom} a été ouvert.");
    }

    /**
     * Visualisation d'une fiche de recensement.
     */
    public function show(Recensement $recensement)
    {
        // Chargement des relations et historiques
        $recensement->load(['quartier', 'carre', 'secteur', 'avenue', 'priorites', 'enqueteur.personne', 'controleur.personne', 'validateur.personne', 'historiques']);

        return view('recensement.show', compact('recensement'));
    }

    /**
     * Formulaire d'édition.
     */
    public function edit(Recensement $recensement)
    {
        $quartiers = Quartier::active()->orderBy('nom')->get();
        $carres = Carre::active()->orderBy('nom')->get();
        $priorites = BesoinPrioritaire::active()->orderBy('ordre_affichage')->get();

        return view('recensement.edit', compact('recensement', 'quartiers', 'carres', 'priorites'));
    }

    /**
     * Enregistrement des modifications.
     */
    public function update(SaveRecensementRequest $request, Recensement $recensement)
    {
        DB::transaction(function () use ($request, $recensement) {
            $changes = [];
            foreach ($request->validated() as $field => $value) {
                if ($field !== 'priorites' && $recensement->{$field} != $value) {
                    $changes[$field] = [
                        'before' => $recensement->{$field},
                        'after' => $value
                    ];
                }
            }

            $recensement->update($request->validated());

            if ($request->has('priorites')) {
                $recensement->priorites()->sync($request->input('priorites'));
            }

            if (!empty($changes)) {
                $recensement->historiques()->create([
                    'action' => 'modification',
                    'details' => [
                        'message' => 'Mise à jour des informations démographiques du ménage',
                        'changes' => $changes
                    ],
                    'user_identifier' => auth()->user()->email
                ]);
            }
        });

        return redirect()
            ->route('recensement.show', $recensement)
            ->with('success', "La fiche de recensement a été mise à jour.");
    }

    /**
     * Suppression d'une fiche de recensement.
     */
    public function destroy(Recensement $recensement)
    {
        $recensement->historiques()->create([
            'action' => 'archivage',
            'details' => ['message' => 'Archivage de la fiche de recensement par l\'administration'],
            'user_identifier' => auth()->user()->email
        ]);

        $recensement->delete();

        return redirect()
            ->route('recensement.index')
            ->with('success', "La fiche de recensement a été archivée avec succès.");
    }
}
