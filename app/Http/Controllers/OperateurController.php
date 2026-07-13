<?php

namespace App\Http\Controllers;

use App\Models\Operateur;
use App\Models\Parameters\Quartier;
use App\Models\Parameters\Carre;
use App\Models\Parameters\CategorieOperateur;
use App\Models\Campagne;
use App\Enums\OperateurStatut;
use App\Enums\EntrepriseTaille;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;

class OperateurController extends Controller
{
    /**
     * Liste des fiches d'opérateurs économiques.
     */
    public function index(Request $request)
    {
        // L'isolation de sécurité d'accès Enquêteur s'applique automatiquement via le scope global !
        $query = Operateur::query()
            ->with(['categorie', 'quartier', 'carre', 'enqueteur.personne'])
            ->latest();

        // Recherche par raison sociale ou promoteur
        if ($request->filled('q')) {
            $search = $request->input('q');
            $query->where(function ($q) use ($search) {
                $q->where('nom_commercial', 'like', "%{$search}%")
                  ->orWhere('nom_entreprise', 'like', "%{$search}%")
                  ->orWhere('promoteur_nom', 'like', "%{$search}%")
                  ->orWhere('rccm', 'like', "%{$search}%");
            });
        }

        // Filtrage par quartier et statut
        if ($request->filled('quartier_id')) {
            $query->where('quartier_id', $request->input('quartier_id'));
        }
        if ($request->filled('statut')) {
            $query->where('statut', $request->input('statut'));
        }

        $operateurs = $query->paginate(15)->withQueryString();
        $quartiers = Quartier::active()->orderBy('nom')->get();

        return view('operateur.index', compact('operateurs', 'quartiers'));
    }

    /**
     * Formulaire d'ouverture d'une fiche d'opérateur économique.
     */
    public function create()
    {
        $categories = CategorieOperateur::active()->orderBy('nom')->get();
        $campagnes = Campagne::active()->get();
        $quartiers = Quartier::active()->orderBy('nom')->get();
        $carres = Carre::active()->orderBy('nom')->get();

        return view('operateur.create', compact('categories', 'campagnes', 'quartiers', 'carres'));
    }

    /**
     * Enregistrement de l'opérateur en base de données.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nom_commercial' => 'required|string|max:255',
            'nom_entreprise' => 'required|string|max:255',
            'promoteur_nom' => 'required|string|max:100',
            'promoteur_prenom' => 'required|string|max:100',
            'rccm' => 'nullable|string|max:50|unique:operateurs,rccm',
            'nif' => 'nullable|string|max:50|unique:operateurs,nif',
            'categorie_id' => 'required|exists:categorie_operateurs,id',
            'campagne_id' => 'required|exists:campagnes,id',
            'quartier_id' => 'required|exists:quartiers,id',
            'carre_id' => 'required|exists:carres,id',
            'effectif_hommes' => 'required|integer|min:0',
            'effectif_femmes' => 'required|integer|min:0',
            'effectif_total' => 'required|integer|min:0',
            'effectif_permanents' => 'required|integer|min:0',
            'effectif_temporaires' => 'required|integer|min:0',
            'gps_latitude' => 'nullable|numeric|between:-90,90',
            'gps_longitude' => 'nullable|numeric|between:-180,180',
            'photo_commerce' => 'nullable|image|max:5120',
            'document_commerciaux' => 'nullable|file|mimes:pdf,jpeg,png|max:10240',
        ]);

        // Validation croisée des effectifs (Règles métiers Symfony d'origine)
        $total = (int)$request->input('effectif_total');
        $hommes = (int)$request->input('effectif_hommes');
        $femmes = (int)$request->input('effectif_femmes');
        $permanents = (int)$request->input('effectif_permanents');
        $temporaires = (int)$request->input('effectif_temporaires');

        if ($total !== ($hommes + $femmes)) {
            return back()->withInput()->withErrors(['effectif_total' => 'L\'effectif total doit être exactement égal à la somme hommes + femmes.']);
        }
        if ($total !== ($permanents + $temporaires)) {
            return back()->withInput()->withErrors(['effectif_total' => 'L\'effectif total doit être exactement égal à la somme permanents + temporaires.']);
        }

        $operateur = DB::transaction(function () use ($request) {
            $op = new Operateur();
            $op->fill($request->all());
            $op->statut = OperateurStatut::BROUILLON;
            
            // Assignation automatique de la taille de l'entreprise d'après ses effectifs
            $total = (int)$request->input('effectif_total');
            if ($total < 10) {
                $op->taille = EntrepriseTaille::MICRO;
            } elseif ($total < 50) {
                $op->taille = EntrepriseTaille::PETITE;
            } elseif ($total < 250) {
                $op->taille = EntrepriseTaille::MOYENNE;
            } else {
                $op->taille = EntrepriseTaille::GRANDE;
            }

            if (auth()->user()->agent) {
                $op->enqueteur_id = auth()->user()->agent->id;
            }

            $op->save();

            // Gestion de la photo commerciale
            if ($request->hasFile('photo_commerce')) {
                $op->addMediaFromRequest('photo_commerce')
                   ->toMediaCollection('photos_commerce');
            }

            // Gestion du justificatif RCCM/Patente
            if ($request->hasFile('document_commerciaux')) {
                $op->addMediaFromRequest('document_commerciaux')
                   ->toMediaCollection('documents_commerciaux');
            }

            // Enregistrement dans la timeline
            $op->historiques()->create([
                'action' => 'creation',
                'details' => [
                    'message' => 'Création initiale de la fiche de l\'opérateur économique sur l\'interface bureau',
                    'raison_sociale' => $op->nom_commercial,
                    'promoteur' => "{$op->promoteur_prenom} {$op->promoteur_nom}"
                ],
                'user_identifier' => auth()->user()->email
            ]);

            return $op;
        });

        return redirect()
            ->route('operateur.show', $operateur)
            ->with('success', "La fiche de l'opérateur économique '{$operateur->nom_commercial}' a été créée avec succès.");
    }

    /**
     * Visualisation d'une fiche d'opérateur économique.
     */
    public function show(Operateur $operateur)
    {
        $operateur->load(['recensement', 'categorie', 'campagne', 'quartier', 'carre', 'enqueteur.personne', 'validateur.personne', 'historiques']);

        return view('operateur.show', compact('operateur'));
    }

    /**
     * Formulaire d'édition de l'opérateur.
     */
    public function edit(Operateur $operateur)
    {
        $categories = CategorieOperateur::active()->orderBy('nom')->get();
        $campagnes = Campagne::active()->get();
        $quartiers = Quartier::active()->orderBy('nom')->get();
        $carres = Carre::active()->orderBy('nom')->get();

        return view('operateur.edit', compact('operateur', 'categories', 'campagnes', 'quartiers', 'carres'));
    }

    /**
     * Enregistrement des modifications de l'opérateur.
     */
    public function update(Request $request, Operateur $operateur)
    {
        $request->validate([
            'nom_commercial' => 'required|string|max:255',
            'nom_entreprise' => 'required|string|max:255',
            'promoteur_nom' => 'required|string|max:100',
            'promoteur_prenom' => 'required|string|max:100',
            'rccm' => 'nullable|string|max:50|unique:operateurs,rccm,' . $operateur->id,
            'nif' => 'nullable|string|max:50|unique:operateurs,nif,' . $operateur->id,
            'categorie_id' => 'required|exists:categorie_operateurs,id',
            'campagne_id' => 'required|exists:campagnes,id',
            'quartier_id' => 'required|exists:quartiers,id',
            'carre_id' => 'required|exists:carres,id',
            'effectif_hommes' => 'required|integer|min:0',
            'effectif_femmes' => 'required|integer|min:0',
            'effectif_total' => 'required|integer|min:0',
            'effectif_permanents' => 'required|integer|min:0',
            'effectif_temporaires' => 'required|integer|min:0',
            'gps_latitude' => 'nullable|numeric|between:-90,90',
            'gps_longitude' => 'nullable|numeric|between:-180,180',
            'photo_commerce' => 'nullable|image|max:5120',
            'document_commerciaux' => 'nullable|file|mimes:pdf,jpeg,png|max:10240',
        ]);

        $total = (int)$request->input('effectif_total');
        $hommes = (int)$request->input('effectif_hommes');
        $femmes = (int)$request->input('effectif_femmes');
        $permanents = (int)$request->input('effectif_permanents');
        $temporaires = (int)$request->input('effectif_temporaires');

        if ($total !== ($hommes + $femmes)) {
            return back()->withInput()->withErrors(['effectif_total' => 'L\'effectif total doit être exactement égal à la somme hommes + femmes.']);
        }
        if ($total !== ($permanents + $temporaires)) {
            return back()->withInput()->withErrors(['effectif_total' => 'L\'effectif total doit être exactement égal à la somme permanents + temporaires.']);
        }

        DB::transaction(function () use ($request, $operateur, $total) {
            $changes = [];
            foreach ($request->except(['photo_commerce', 'document_commerciaux']) as $field => $value) {
                if ($operateur->{$field} != $value) {
                    $changes[$field] = [
                        'before' => $operateur->{$field},
                        'after' => $value
                    ];
                }
            }

            $operateur->fill($request->all());

            // Recalcul de la taille de l'entreprise
            if ($total < 10) {
                $operateur->taille = EntrepriseTaille::MICRO;
            } elseif ($total < 50) {
                $operateur->taille = EntrepriseTaille::PETITE;
            } elseif ($total < 250) {
                $operateur->taille = EntrepriseTaille::MOYENNE;
            } else {
                $operateur->taille = EntrepriseTaille::GRANDE;
            }

            $operateur->save();

            if ($request->hasFile('photo_commerce')) {
                $operateur->clearMediaCollection('photos_commerce');
                $operateur->addMediaFromRequest('photo_commerce')->toMediaCollection('photos_commerce');
            }

            if ($request->hasFile('document_commerciaux')) {
                $operateur->clearMediaCollection('documents_commerciaux');
                $operateur->addMediaFromRequest('document_commerciaux')->toMediaCollection('documents_commerciaux');
            }

            if (!empty($changes)) {
                $operateur->historiques()->create([
                    'action' => 'modification',
                    'details' => [
                        'message' => 'Mise à jour des informations administratives et d\'effectifs',
                        'changes' => $changes
                    ],
                    'user_identifier' => auth()->user()->email
                ]);
            }
        });

        return redirect()
            ->route('operateur.show', $operateur)
            ->with('success', "La fiche de l'opérateur commercial a été mise à jour.");
    }

    /**
     * Archivage de l'opérateur (Soft Delete).
     */
    public function destroy(Operateur $operateur)
    {
        $operateur->historiques()->create([
            'action' => 'archivage',
            'details' => ['message' => 'Archivage de la fiche de l\'opérateur par l\'administration'],
            'user_identifier' => auth()->user()->email
        ]);

        $operateur->delete();

        return redirect()
            ->route('operateur.index')
            ->with('success', "La fiche de l'opérateur a été archivée.");
    }
}
