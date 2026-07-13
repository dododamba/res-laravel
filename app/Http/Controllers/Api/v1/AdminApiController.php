<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Parameters\Quartier;
use App\Models\Parameters\Carre;
use App\Models\Parameters\Secteur;
use App\Models\Parameters\Avenue;
use App\Models\Agent;
use App\Models\Personne;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Parameters\Fonction;
use App\Enums\AgentStatut;
use App\Services\AgentAccountService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminApiController extends Controller
{
    use ApiResponse;



    // ==========================================
    // QUARTIERS
    // ==========================================

    public function listQuartiers(Request $request): JsonResponse
    {
        $searchTerm = $request->query('q', '');
        $showArchived = $request->boolean('archived', false);

        $query = Quartier::query();
        if ($showArchived) {
            $query->onlyTrashed();
        }
        if (!empty($searchTerm)) {
            $query->search($searchTerm);
        }

        $entities = $query->orderBy('ordre_affichage', 'asc')->get();
        return $this->buildResponse(true, "Quartiers récupérés.", $entities);
    }

    public function storeQuartier(Request $request): JsonResponse
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'couleur' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'icone' => 'nullable|string|max:50',
            'ordre_affichage' => 'nullable|integer',
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

        return $this->buildResponse(true, "Quartier créé avec succès.", $entity);
    }

    public function showQuartier(string $id): JsonResponse
    {
        $entity = Quartier::withTrashed()->findOrFail($id);
        return $this->buildResponse(true, "Quartier récupéré.", $entity);
    }

    public function updateQuartier(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'couleur' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'icone' => 'nullable|string|max:50',
            'ordre_affichage' => 'nullable|integer',
            'is_active' => 'required|boolean',
        ]);

        $entity = Quartier::withTrashed()->findOrFail($id);
        $entity->update([
            'nom' => $request->input('nom'),
            'code' => $request->input('code'),
            'description' => $request->input('description'),
            'couleur' => $request->input('couleur'),
            'icone' => $request->input('icone'),
            'ordre_affichage' => $request->input('ordre_affichage', $entity->ordre_affichage),
            'is_active' => $request->boolean('is_active', $entity->is_active),
        ]);

        return $this->buildResponse(true, "Quartier modifié avec succès.", $entity);
    }

    public function destroyQuartier(string $id): JsonResponse
    {
        $entity = Quartier::findOrFail($id);
        $entity->delete();
        return $this->buildResponse(true, "Quartier archivé avec succès.");
    }

    public function restoreQuartier(string $id): JsonResponse
    {
        $entity = Quartier::onlyTrashed()->findOrFail($id);
        $entity->restore();
        return $this->buildResponse(true, "Quartier restauré avec succès.", $entity);
    }

    public function toggleQuartier(string $id): JsonResponse
    {
        $entity = Quartier::findOrFail($id);
        $entity->is_active = !$entity->is_active;
        $entity->save();
        return $this->buildResponse(true, "Le statut d'activité a été mis à jour.", $entity);
    }

    public function duplicateQuartier(string $id): JsonResponse
    {
        $entity = Quartier::findOrFail($id);
        $clone = $entity->replicate();
        $clone->uuid = (string) Str::uuid();
        $clone->nom = $entity->nom . ' (Copie)';
        $clone->code = $entity->code ? $entity->code . '-COPY' : null;
        $clone->is_default = false;
        $clone->slug = Str::slug($clone->nom) . '-copie-' . rand(10, 99);
        $clone->save();

        return $this->buildResponse(true, "Quartier dupliqué avec succès.", $clone);
    }

    // ==========================================
    // CARRÉS (BLOCS)
    // ==========================================

    public function listCarres(Request $request): JsonResponse
    {
        $searchTerm = $request->query('q', '');
        $showArchived = $request->boolean('archived', false);

        $query = Carre::query()->with('quartier');
        if ($showArchived) {
            $query->onlyTrashed();
        }
        if (!empty($searchTerm)) {
            $query->search($searchTerm);
        }

        $entities = $query->orderBy('ordre_affichage', 'asc')->get();
        return $this->buildResponse(true, "Carrés récupérés.", $entities);
    }

    public function storeCarre(Request $request): JsonResponse
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'couleur' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'icone' => 'nullable|string|max:50',
            'ordre_affichage' => 'nullable|integer',
            'quartier_id' => 'required|exists:quartiers,id',
            'est_chef' => 'nullable|boolean',
        ]);

        $entity = Carre::create([
            'nom' => $request->input('nom'),
            'code' => $request->input('code'),
            'description' => $request->input('description'),
            'couleur' => $request->input('couleur'),
            'icone' => $request->input('icone'),
            'ordre_affichage' => $request->input('ordre_affichage', 0),
            'quartier_id' => $request->input('quartier_id'),
            'est_chef' => $request->boolean('est_chef', false),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return $this->buildResponse(true, "Carré créé avec succès.", $entity);
    }

    public function showCarre(string $id): JsonResponse
    {
        $entity = Carre::withTrashed()->with('quartier')->findOrFail($id);
        return $this->buildResponse(true, "Carré récupéré.", $entity);
    }

    public function updateCarre(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'couleur' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'icone' => 'nullable|string|max:50',
            'ordre_affichage' => 'nullable|integer',
            'quartier_id' => 'required|exists:quartiers,id',
            'est_chef' => 'nullable|boolean',
            'is_active' => 'required|boolean',
        ]);

        $entity = Carre::withTrashed()->findOrFail($id);
        $entity->update([
            'nom' => $request->input('nom'),
            'code' => $request->input('code'),
            'description' => $request->input('description'),
            'couleur' => $request->input('couleur'),
            'icone' => $request->input('icone'),
            'ordre_affichage' => $request->input('ordre_affichage', $entity->ordre_affichage),
            'quartier_id' => $request->input('quartier_id'),
            'est_chef' => $request->boolean('est_chef', $entity->est_chef),
            'is_active' => $request->boolean('is_active', $entity->is_active),
        ]);

        return $this->buildResponse(true, "Carré modifié avec succès.", $entity);
    }

    public function destroyCarre(string $id): JsonResponse
    {
        $entity = Carre::findOrFail($id);
        $entity->delete();
        return $this->buildResponse(true, "Carré archivé avec succès.");
    }

    public function restoreCarre(string $id): JsonResponse
    {
        $entity = Carre::onlyTrashed()->findOrFail($id);
        $entity->restore();
        return $this->buildResponse(true, "Carré restauré avec succès.", $entity);
    }

    public function toggleCarre(string $id): JsonResponse
    {
        $entity = Carre::findOrFail($id);
        $entity->is_active = !$entity->is_active;
        $entity->save();
        return $this->buildResponse(true, "Le statut d'activité a été mis à jour.", $entity);
    }

    public function duplicateCarre(string $id): JsonResponse
    {
        $entity = Carre::findOrFail($id);
        $clone = $entity->replicate();
        $clone->uuid = (string) Str::uuid();
        $clone->nom = $entity->nom . ' (Copie)';
        $clone->code = $entity->code ? $entity->code . '-COPY' : null;
        $clone->is_default = false;
        $clone->slug = Str::slug($clone->nom) . '-copie-' . rand(10, 99);
        $clone->save();

        return $this->buildResponse(true, "Carré dupliqué avec succès.", $clone);
    }

    // ==========================================
    // SECTEURS
    // ==========================================

    public function listSecteurs(Request $request): JsonResponse
    {
        $searchTerm = $request->query('q', '');
        $showArchived = $request->boolean('archived', false);

        $query = Secteur::query()->with('carre');
        if ($showArchived) {
            $query->onlyTrashed();
        }
        if (!empty($searchTerm)) {
            $query->search($searchTerm);
        }

        $entities = $query->orderBy('ordre_affichage', 'asc')->get();
        return $this->buildResponse(true, "Secteurs récupérés.", $entities);
    }

    public function storeSecteur(Request $request): JsonResponse
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'ordre_affichage' => 'nullable|integer',
            'carre_id' => 'required|exists:carres,id',
        ]);

        $entity = Secteur::create([
            'nom' => $request->input('nom'),
            'code' => $request->input('code'),
            'description' => $request->input('description'),
            'ordre_affichage' => $request->input('ordre_affichage', 0),
            'carre_id' => $request->input('carre_id'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return $this->buildResponse(true, "Secteur créé avec succès.", $entity);
    }

    public function showSecteur(string $id): JsonResponse
    {
        $entity = Secteur::withTrashed()->with('carre')->findOrFail($id);
        return $this->buildResponse(true, "Secteur récupéré.", $entity);
    }

    public function updateSecteur(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'ordre_affichage' => 'nullable|integer',
            'carre_id' => 'required|exists:carres,id',
            'is_active' => 'required|boolean',
        ]);

        $entity = Secteur::withTrashed()->findOrFail($id);
        $entity->update([
            'nom' => $request->input('nom'),
            'code' => $request->input('code'),
            'description' => $request->input('description'),
            'ordre_affichage' => $request->input('ordre_affichage', $entity->ordre_affichage),
            'carre_id' => $request->input('carre_id'),
            'is_active' => $request->boolean('is_active', $entity->is_active),
        ]);

        return $this->buildResponse(true, "Secteur modifié avec succès.", $entity);
    }

    public function destroySecteur(string $id): JsonResponse
    {
        $entity = Secteur::findOrFail($id);
        $entity->delete();
        return $this->buildResponse(true, "Secteur archivé avec succès.");
    }

    public function restoreSecteur(string $id): JsonResponse
    {
        $entity = Secteur::onlyTrashed()->findOrFail($id);
        $entity->restore();
        return $this->buildResponse(true, "Secteur restauré avec succès.", $entity);
    }

    public function toggleSecteur(string $id): JsonResponse
    {
        $entity = Secteur::findOrFail($id);
        $entity->is_active = !$entity->is_active;
        $entity->save();
        return $this->buildResponse(true, "Le statut d'activité a été mis à jour.", $entity);
    }

    public function duplicateSecteur(string $id): JsonResponse
    {
        $entity = Secteur::findOrFail($id);
        $clone = $entity->replicate();
        $clone->uuid = (string) Str::uuid();
        $clone->nom = $entity->nom . ' (Copie)';
        $clone->code = $entity->code ? $entity->code . '-COPY' : null;
        $clone->is_default = false;
        $clone->slug = Str::slug($clone->nom) . '-copie-' . rand(10, 99);
        $clone->save();

        return $this->buildResponse(true, "Secteur dupliqué avec succès.", $clone);
    }

    // ==========================================
    // AVENUES (VOIES)
    // ==========================================

    public function listAvenues(Request $request): JsonResponse
    {
        $searchTerm = $request->query('q', '');
        $showArchived = $request->boolean('archived', false);

        $query = Avenue::query()->with('secteur');
        if ($showArchived) {
            $query->onlyTrashed();
        }
        if (!empty($searchTerm)) {
            $query->search($searchTerm);
        }

        $entities = $query->orderBy('ordre_affichage', 'asc')->get();
        return $this->buildResponse(true, "Avenues récupérées.", $entities);
    }

    public function storeAvenue(Request $request): JsonResponse
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'ordre_affichage' => 'nullable|integer',
            'secteur_id' => 'required|exists:secteurs,id',
        ]);

        $entity = Avenue::create([
            'nom' => $request->input('nom'),
            'code' => $request->input('code'),
            'description' => $request->input('description'),
            'ordre_affichage' => $request->input('ordre_affichage', 0),
            'secteur_id' => $request->input('secteur_id'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return $this->buildResponse(true, "Avenue créée avec succès.", $entity);
    }

    public function showAvenue(string $id): JsonResponse
    {
        $entity = Avenue::withTrashed()->with('secteur')->findOrFail($id);
        return $this->buildResponse(true, "Avenue récupérée.", $entity);
    }

    public function updateAvenue(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'ordre_affichage' => 'nullable|integer',
            'secteur_id' => 'required|exists:secteurs,id',
            'is_active' => 'required|boolean',
        ]);

        $entity = Avenue::withTrashed()->findOrFail($id);
        $entity->update([
            'nom' => $request->input('nom'),
            'code' => $request->input('code'),
            'description' => $request->input('description'),
            'ordre_affichage' => $request->input('ordre_affichage', $entity->ordre_affichage),
            'secteur_id' => $request->input('secteur_id'),
            'is_active' => $request->boolean('is_active', $entity->is_active),
        ]);

        return $this->buildResponse(true, "Avenue modifiée avec succès.", $entity);
    }

    public function destroyAvenue(string $id): JsonResponse
    {
        $entity = Avenue::findOrFail($id);
        $entity->delete();
        return $this->buildResponse(true, "Avenue archivée avec succès.");
    }

    public function restoreAvenue(string $id): JsonResponse
    {
        $entity = Avenue::onlyTrashed()->findOrFail($id);
        $entity->restore();
        return $this->buildResponse(true, "Avenue restaurée avec succès.", $entity);
    }

    public function toggleAvenue(string $id): JsonResponse
    {
        $entity = Avenue::findOrFail($id);
        $entity->is_active = !$entity->is_active;
        $entity->save();
        return $this->buildResponse(true, "Le statut d'activité a été mis à jour.", $entity);
    }

    public function duplicateAvenue(string $id): JsonResponse
    {
        $entity = Avenue::findOrFail($id);
        $clone = $entity->replicate();
        $clone->uuid = (string) Str::uuid();
        $clone->nom = $entity->nom . ' (Copie)';
        $clone->code = $entity->code ? $entity->code . '-COPY' : null;
        $clone->is_default = false;
        $clone->slug = Str::slug($clone->nom) . '-copie-' . rand(10, 99);
        $clone->save();

        return $this->buildResponse(true, "Avenue dupliquée avec succès.", $clone);
    }

    // ==========================================
    // AGENTS TERRITORIAUX
    // ==========================================

    public function listAgents(Request $request): JsonResponse
    {
        $query = Agent::query()->with(['personne', 'fonction', 'user']);

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

        if ($request->filled('fonction_id')) {
            $query->where('fonction_id', $request->input('fonction_id'));
        }
        if ($request->filled('statut')) {
            $query->where('statut', $request->input('statut'));
        }

        $agents = $query->orderBy('created_at', 'desc')->get();
        return $this->buildResponse(true, "Agents territoriaux récupérés.", $agents);
    }

    public function storeAgent(Request $request, AgentAccountService $accountService): JsonResponse
    {
        $request->validate([
            'prenom' => 'required|string|max:100',
            'nom' => 'required|string|max:100',
            'email' => 'required|email|max:100|unique:personnes,email',
            'telephone' => 'required|string|max:15',
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
            $personne = Personne::create([
                'id' => (string) Str::uuid(),
                'prenom' => $request->input('prenom'),
                'nom' => $request->input('nom'),
                'email' => $request->input('email'),
                'telephone' => $request->input('telephone'),
                'role' => 'user',
            ]);

            $agent = Agent::create([
                'id' => (string) Str::uuid(),
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

            $accountService->provisionAccount($agent);

            return $agent;
        });

        $agent->load(['personne', 'fonction', 'user']);
        return $this->buildResponse(true, "Agent territorial créé avec son compte utilisateur.", $agent);
    }

    public function showAgent(string $id): JsonResponse
    {
        $agent = Agent::with(['personne', 'fonction', 'user', 'affectations'])->findOrFail($id);
        return $this->buildResponse(true, "Détails de l'agent récupérés.", $agent);
    }

    public function updateAgent(Request $request, string $id): JsonResponse
    {
        $agent = Agent::findOrFail($id);

        $request->validate([
            'prenom' => 'required|string|max:100',
            'nom' => 'required|string|max:100',
            'email' => 'required|email|max:100|unique:personnes,email,' . $agent->personne_id,
            'telephone' => 'required|string|max:15',
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
            $agent->personne->update([
                'prenom' => $request->input('prenom'),
                'nom' => $request->input('nom'),
                'email' => $request->input('email'),
                'telephone' => $request->input('telephone'),
            ]);

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

        $agent->load(['personne', 'fonction', 'user']);
        return $this->buildResponse(true, "Profil de l'agent mis à jour avec succès.", $agent);
    }

    public function destroyAgent(string $id): JsonResponse
    {
        $agent = Agent::findOrFail($id);

        DB::transaction(function () use ($agent) {
            if ($agent->user) {
                $agent->user->update([
                    'is_active' => false,
                    'status' => 'suspended'
                ]);
                $agent->user->delete();
            }
            $agent->delete();
        });

        return $this->buildResponse(true, "L'agent et son compte utilisateur ont été désactivés et archivés.");
    }

    public function listFonctions(): JsonResponse
    {
        $fonctions = Fonction::active()->orderBy('nom')->get();
        return $this->buildResponse(true, "Fonctions récupérées.", $fonctions);
    }

    // ==========================================
    // UTILISATEURS & RBAC
    // ==========================================

    public function listUsers(Request $request): JsonResponse
    {
        $query = User::query()->with(['roles', 'agent.personne']);

        if ($request->filled('q')) {
            $search = $request->input('q');
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                  ->orWhere('firstname', 'like', "%{$search}%")
                  ->orWhere('lastname', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->whereHas('roles', function($rq) use ($request) {
                $rq->where('slug', $request->input('role'));
            });
        }

        $users = $query->orderBy('created_at', 'desc')->get();
        return $this->buildResponse(true, "Utilisateurs récupérés.", $users);
    }

    public function showUser(string $id): JsonResponse
    {
        $user = User::with(['roles.permissions', 'permissions', 'agent.personne'])->findOrFail($id);
        $allPermissions = Permission::orderBy('category')->orderBy('name')->get();
        return $this->buildResponse(true, "Détails de l'utilisateur récupérés.", [
            'user' => $user,
            'all_permissions' => $allPermissions
        ]);
    }

    public function updateUser(Request $request, string $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $request->validate([
            'email' => 'required|email|max:180|unique:users,email,' . $user->id,
            'firstname' => 'nullable|string|max:255',
            'lastname' => 'nullable|string|max:255',
            'telephone' => 'nullable|string|max:50',
            'status' => 'required|string|in:active,pending,suspended',
            'is_active' => 'required|boolean',
            'password' => 'nullable|string|min:8',
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,id',
        ]);

        DB::transaction(function () use ($request, $user) {
            $user->fill($request->only('email', 'firstname', 'lastname', 'telephone', 'status', 'is_active'));
            
            if ($request->filled('password')) {
                $user->password = Hash::make($request->input('password'));
            }

            $user->save();

            $user->roles()->sync($request->input('roles'));

            if ($user->agent && $user->agent->personne) {
                $user->agent->personne->update([
                    'email' => $request->input('email'),
                    'prenom' => $request->input('firstname') ?? $user->agent->personne->prenom,
                    'nom' => $request->input('lastname') ?? $user->agent->personne->nom,
                    'telephone' => $request->input('telephone') ?? $user->agent->personne->telephone,
                ]);
            }
        });

        $user->load(['roles', 'agent.personne']);
        return $this->buildResponse(true, "Compte utilisateur mis à jour avec succès.", $user);
    }

    public function destroyUser(string $id): JsonResponse
    {
        $user = User::findOrFail($id);

        if ($user->id === auth()->id()) {
            return $this->buildResponse(false, "Impossible de supprimer votre propre compte utilisateur !", null, [], 400);
        }

        DB::transaction(function () use ($user) {
            $user->is_active = false;
            $user->status = 'suspended';
            $user->save();
            $user->delete();
        });

        return $this->buildResponse(true, "Le compte utilisateur a été désactivé et archivé.");
    }

    public function listRoles(): JsonResponse
    {
        $roles = Role::orderBy('name')->get();
        return $this->buildResponse(true, "Rôles récupérés.", $roles);
    }

    public function updateUserPermissions(Request $request, string $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $request->validate([
            'overrides' => 'nullable|array',
            'overrides.*' => 'in:grant,revoke,none',
        ]);

        $overrides = $request->input('overrides', []);

        DB::transaction(function () use ($user, $overrides) {
            $user->permissions()->detach();

            foreach ($overrides as $permissionId => $status) {
                if ($status === 'grant') {
                    $user->permissions()->attach($permissionId, ['is_granted' => true]);
                } elseif ($status === 'revoke') {
                    $user->permissions()->attach($permissionId, ['is_granted' => false]);
                }
            }
        });

        $user->load(['permissions']);
        return $this->buildResponse(true, "Les surcharges de droits de l'utilisateur ont été mises à jour.", $user);
    }
}
