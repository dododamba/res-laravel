<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\Personne;
use App\Models\Parameters\Fonction;
use App\Models\User;
use App\Enums\AgentStatut;
use App\Services\AgentAccountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class AgentController extends Controller
{
    /**
     * Liste des agents territoriaux d'enquêtes et d'administration.
     */
    public function index(Request $request)
    {
        // Seul l'administrateur peut lister ou gérer les agents (Vérification Gate)
        Gate::authorize('can', 'USER_MANAGE');

        $query = Agent::query()->with(['personne', 'fonction', 'user']);

        // Recherche par nom, matricule ou email
        if ($request->filled('q')) {
            $search = $request->input('q');
            $query->where(function ($q) use ($search) {
                $q->where('matricule', 'like', "%{$search}%")
                  ->orWhereHas('personne', function ($pq) use ($search) {
                      $pq->where('nom', 'like', "%{$search}%")
                        ->orWhere('prenom', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // Filtrage par fonction et statut
        if ($request->filled('fonction_id')) {
            $query->where('fonction_id', $request->input('fonction_id'));
        }
        if ($request->filled('statut')) {
            $query->where('statut', $request->input('statut'));
        }

        $agents = $query->paginate(15)->withQueryString();
        $fonctions = Fonction::active()->orderBy('nom')->get();

        return view('agent.index', compact('agents', 'fonctions'));
    }

    /**
     * Formulaire d'enregistrement d'un nouvel agent.
     */
    public function create()
    {
        Gate::authorize('can', 'USER_MANAGE');

        $fonctions = Fonction::active()->orderBy('nom')->get();

        return view('agent.create', compact('fonctions'));
    }

    /**
     * Enregistrement en base de données de la personne et de l'agent.
     */
    public function store(Request $request, AgentAccountService $accountService)
    {
        Gate::authorize('can', 'USER_MANAGE');

        $request->validate([
            // Données civiles (Personne)
            'prenom' => 'required|string|max:100',
            'nom' => 'required|string|max:100',
            'email' => 'required|email|max:100|unique:personnes,email',
            'telephone' => 'required|string|max:15',

            // Données techniques (Agent)
            'fonction_id' => 'required|exists:fonctions,id',
            'sexe' => 'required|string|in:M,F',
            'date_naissance' => 'nullable|date|before:today',
            'lieu_naissance' => 'nullable|string|max:255',
            'nationalite' => 'nullable|string|max:100',
            'telephone_secondaire' => 'nullable|string|max:30',
            'adresse' => 'nullable|string|max:255',
            'profession' => 'nullable|string|max:255',
            'matricule' => 'required|string|max:50|unique:agents,matricule',
            'cni' => 'nullable|string|max:50',
            'observations' => 'nullable|string',
        ]);

        $agent = DB::transaction(function () use ($request, $accountService) {
            
            // 1. Création de la fiche d'identité civile (Personne)
            $personne = Personne::create([
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'prenom' => $request->input('prenom'),
                'nom' => $request->input('nom'),
                'email' => $request->input('email'),
                'telephone' => $request->input('telephone'),
                'role' => 'user',
            ]);

            // 2. Création de la fiche d'Agent liée à la personne
            $agent = Agent::create([
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'personne_id' => $personne->id,
                'fonction_id' => $request->input('fonction_id'),
                'sexe' => $request->input('sexe'),
                'date_naissance' => $request->input('date_naissance'),
                'lieu_naissance' => $request->input('lieu_naissance'),
                'nationalite' => $request->input('nationalite'),
                'telephone_secondaire' => $request->input('telephone_secondaire'),
                'adresse' => $request->input('adresse'),
                'profession' => $request->input('profession'),
                'matricule' => $request->input('matricule'),
                'cni' => $request->input('cni'),
                'statut' => AgentStatut::ACTIF,
                'date_nomination' => now(),
                'observations' => $request->input('observations'),
            ]);

            // 3. Provisionnement automatique du compte d'accès utilisateur (User + Role) via le Service dédié
            $accountService->provisionAccount($agent);

            return $agent;
        });

        return redirect()
            ->route('agent.index')
            ->with('success', "L'agent territorial {$agent->personne->prenom} {$agent->personne->nom} a été créé avec son compte utilisateur.");
    }

    /**
     * Visualisation détaillée du profil de l'agent.
     */
    public function show(Agent $agent)
    {
        Gate::authorize('can', 'USER_MANAGE');

        $agent->load(['personne', 'fonction', 'user', 'affectations']);

        return view('agent.show', compact('agent'));
    }

    /**
     * Formulaire d'édition de l'agent.
     */
    public function edit(Agent $agent)
    {
        Gate::authorize('can', 'USER_MANAGE');

        $fonctions = Fonction::active()->orderBy('nom')->get();

        return view('agent.edit', compact('agent', 'fonctions'));
    }

    /**
     * Enregistrement des modifications de la personne civile et de l'agent.
     */
    public function update(Request $request, Agent $agent)
    {
        Gate::authorize('can', 'USER_MANAGE');

        $request->validate([
            // Données civiles (Personne)
            'prenom' => 'required|string|max:100',
            'nom' => 'required|string|max:100',
            'email' => 'required|email|max:100|unique:personnes,email,' . $agent->personne_id,
            'telephone' => 'required|string|max:15',

            // Données techniques (Agent)
            'fonction_id' => 'required|exists:fonctions,id',
            'sexe' => 'required|string|in:M,F',
            'date_naissance' => 'nullable|date|before:today',
            'lieu_naissance' => 'nullable|string|max:255',
            'nationalite' => 'nullable|string|max:100',
            'telephone_secondaire' => 'nullable|string|max:30',
            'adresse' => 'nullable|string|max:255',
            'profession' => 'nullable|string|max:255',
            'matricule' => 'required|string|max:50|unique:agents,matricule,' . $agent->id,
            'cni' => 'nullable|string|max:50',
            'statut' => 'required|string',
            'observations' => 'nullable|string',
        ]);

        DB::transaction(function () use ($request, $agent) {
            
            // 1. Mise à jour de la personne civile
            $agent->personne->update([
                'prenom' => $request->input('prenom'),
                'nom' => $request->input('nom'),
                'email' => $request->input('email'),
                'telephone' => $request->input('telephone'),
            ]);

            // 2. Mise à jour du profil agent
            $agent->update([
                'fonction_id' => $request->input('fonction_id'),
                'sexe' => $request->input('sexe'),
                'date_naissance' => $request->input('date_naissance'),
                'lieu_naissance' => $request->input('lieu_naissance'),
                'nationalite' => $request->input('nationalite'),
                'telephone_secondaire' => $request->input('telephone_secondaire'),
                'adresse' => $request->input('adresse'),
                'profession' => $request->input('profession'),
                'matricule' => $request->input('matricule'),
                'cni' => $request->input('cni'),
                'statut' => AgentStatut::from($request->input('statut')),
                'observations' => $request->input('observations'),
            ]);

            // 3. Mise à jour synchronisée du compte utilisateur (email et identité)
            if ($agent->user) {
                $agent->user->update([
                    'email' => $request->input('email'),
                    'firstname' => $request->input('prenom'),
                    'lastname' => $request->input('nom'),
                    'telephone' => $request->input('telephone'),
                    'is_active' => $request->input('statut') === 'actif',
                ]);
            }
        });

        return redirect()
            ->route('agent.show', $agent)
            ->with('success', "Le profil de l'agent a été mis à jour avec succès.");
    }

    /**
     * Archivage technique de l'agent (Soft Delete) et désactivation du compte utilisateur.
     */
    public function destroy(Agent $agent)
    {
        Gate::authorize('can', 'USER_MANAGE');

        DB::transaction(function () use ($agent) {
            
            // Désactiver le compte d'authentification lié
            if ($agent->user) {
                $agent->user->update([
                    'is_active' => false,
                    'status' => 'suspended'
                ]);
                $agent->user->delete(); // Soft delete du compte user
            }

            // Soft Delete du profil d'agent
            $agent->delete();
        });

        return redirect()
            ->route('agent.index')
            ->with('success', "Le profil d'agent territorial et son compte utilisateur ont été désactivés et archivés.");
    }
}
