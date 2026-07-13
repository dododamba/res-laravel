<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    /**
     * Liste des comptes utilisateurs système (Vérification USER_MANAGE).
     */
    public function index(Request $request)
    {
        Gate::authorize('can', 'USER_MANAGE');

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

        $users = $query->paginate(15)->withQueryString();
        $roles = Role::orderBy('name')->get();

        return view('user.index', compact('users', 'roles'));
    }

    /**
     * Visualisation des droits, surcharges et sessions actives d'un utilisateur.
     */
    public function show(User $user)
    {
        Gate::authorize('can', 'USER_MANAGE');

        $user->load(['roles.permissions', 'permissions', 'agent.personne']);
        $allPermissions = Permission::orderBy('category')->orderBy('name')->get();

        return view('user.show', compact('user', 'allPermissions'));
    }

    /**
     * Formulaire d'édition de l'utilisateur (Compte & RBAC).
     */
    public function edit(User $user)
    {
        Gate::authorize('can', 'USER_MANAGE');

        $roles = Role::orderBy('name')->get();

        return view('user.edit', compact('user', 'roles'));
    }

    /**
     * Enregistrement des modifications du compte et des rôles d'accès.
     */
    public function update(Request $request, User $user)
    {
        Gate::authorize('can', 'USER_MANAGE');

        $request->validate([
            'email' => 'required|email|max:180|unique:users,email,' . $user->id,
            'firstname' => 'nullable|string|max:255',
            'lastname' => 'nullable|string|max:255',
            'telephone' => 'nullable|string|max:50',
            'status' => 'required|string|in:active,pending,suspended',
            'is_active' => 'required|boolean',
            'password' => 'nullable|string|min:8|confirmed',
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,id',
        ]);

        DB::transaction(function () use ($request, $user) {
            
            $user->fill($request->only('email', 'firstname', 'lastname', 'telephone', 'status', 'is_active'));
            
            // Si le mot de passe est renseigné, le modifier
            if ($request->filled('password')) {
                $user->password = Hash::make($request->input('password'));
            }

            $user->save();

            // Synchroniser les rôles RBAC
            $user->roles()->sync($request->input('roles'));

            // S'il y a un agent lié, synchroniser son email civile de secours
            if ($user->agent && $user->agent->personne) {
                $user->agent->personne->update([
                    'email' => $request->input('email'),
                    'prenom' => $request->input('firstname') ?? $user->agent->personne->prenom,
                    'nom' => $request->input('lastname') ?? $user->agent->personne->nom,
                    'telephone' => $request->input('telephone') ?? $user->agent->personne->telephone,
                ]);
            }
        });

        return redirect()
            ->route('user.show', $user)
            ->with('success', "Le compte utilisateur a été mis à jour de manière cohérente.");
    }

    /**
     * Enregistre les surcharges de permissions fines individuelles (Surcharge du PermissionVoter Symfony).
     */
    public function updatePermissions(Request $request, User $user)
    {
        Gate::authorize('can', 'ROLE_MANAGE');

        $request->validate([
            'overrides' => 'nullable|array',
            'overrides.*' => 'in:grant,revoke,none',
        ]);

        $overrides = $request->input('overrides', []);

        DB::transaction(function () use ($user, $overrides) {
            
            // Vider les surcharges existantes de l'utilisateur
            $user->permissions()->detach();

            // Enregistrer uniquement les surcharges actives (grant ou revoke)
            foreach ($overrides as $permissionId => $status) {
                if ($status === 'grant') {
                    $user->permissions()->attach($permissionId, ['is_granted' => true]);
                } elseif ($status === 'revoke') {
                    $user->permissions()->attach($permissionId, ['is_granted' => false]);
                }
            }
        });

        return redirect()
            ->route('user.show', $user)
            ->with('success', "Les surcharges de droits individuels de l'utilisateur ont été mises à jour.");
    }

    /**
     * Supprime ou suspend un compte d'accès utilisateur.
     */
    public function destroy(User $user)
    {
        Gate::authorize('can', 'USER_MANAGE');

        if ($user->id === auth()->id()) {
            return back()->with('error', "Impossible de supprimer votre propre compte utilisateur en cours d'utilisation !");
        }

        DB::transaction(function () use ($user) {
            $user->is_active = false;
            $user->status = 'suspended';
            $user->save();

            $user->delete(); // Soft Delete Eloquent
        });

        return redirect()
            ->route('user.index')
            ->with('success', "Le compte utilisateur d'accès système a été désactivé et archivé.");
    }
}
