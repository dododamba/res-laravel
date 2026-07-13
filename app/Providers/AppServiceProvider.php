<?php

namespace App\Providers;

use App\Helpers\ThemeHelper;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Enregistrement du Singleton ThemeHelper dans le conteneur Laravel
        $this->app->singleton(ThemeHelper::class, function ($app) {
            return new ThemeHelper();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 1. Partager automatiquement l'instance singleton $theme avec toutes les vues Blade
        View::composer('*', function ($view) {
            $view->with('theme', app(ThemeHelper::class));
        });

        // 2. Intercepteur global de sécurité Gate (Réplique exacte du PermissionVoter de Symfony)
        Gate::before(function (User $user, string $ability) {
            
            // Les administrateurs globaux possèdent tous les accès par défaut
            if ($user->hasRole(['ROLE_SUPER_ADMIN'])) {
                return true;
            }

            // Gestion de la table pivot user_permissions (surcharges utilisateur directes prioritaires)
            // On vérifie si l'utilisateur possède une surcharge d'autorisation explicite
            $override = $user->permissions()
                ->where('name', $ability)
                ->withPivot('is_granted')
                ->first();

            if ($override) {
                // Si is_granted = true (1) -> accès accordé. Si false (0) -> accès strictement exclu !
                return (bool) $override->pivot->is_granted;
            }

            // Liaison de compatibilité : Vérifier les droits RBAC (role_permissions) pour les accès système de premier niveau
            if (str_starts_with($ability, 'PARAM_') || str_starts_with($ability, 'USER_') || str_starts_with($ability, 'AUDIT_')) {
                $hasRolePermission = $user->roles()->whereHas('permissions', function ($q) use ($ability) {
                    $q->where('name', $ability);
                })->exists();

                if ($hasRolePermission) {
                    return true;
                }
            }

            // Si aucune surcharge explicite n'existe, Laravel continue l'analyse vers les Policies standards
            return null;
        });

        // 3. Liaison de compatibilité pour les Gates dynamiques de rôles de base
        // Vérifie si l'utilisateur possède un droit RBAC basé sur ses rôles pivots (role_permissions)
        Gate::define('can', function (User $user, string $permissionName) {
            return $user->roles()->whereHas('permissions', function ($q) use ($permissionName) {
                $q->where('name', $permissionName);
            })->exists();
        });
    }
}
