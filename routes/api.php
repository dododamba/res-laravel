<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\v1\SurveyApiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::prefix('v1')->name('api.v1.')->group(function () {

    // Espace de compatibilité spécifique pour l'application Mobile Ionic (/api/v1/auth/*)
    Route::prefix('auth')->group(function () {
        Route::post('/login', [App\Http\Controllers\Api\v1\AuthApiController::class, 'login'])->name('auth.login');
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('/profile', [App\Http\Controllers\Api\v1\AuthApiController::class, 'profile'])->name('auth.profile');
            Route::post('/profile', [App\Http\Controllers\Api\v1\AuthApiController::class, 'updateProfile'])->name('auth.profile.update');
        });
    });

    // 1. Routes d'authentification publiques (génération de tokens JWT/Sanctum)
    Route::post('/login', [App\Http\Controllers\Api\v1\AuthApiController::class, 'login'])->name('login');

    // 2. Routes d'enquêtes sécurisées (requiert un token Sanctum valide d'enquêteur)
    Route::middleware('auth:sanctum')->group(function () {
        
        // Enquêtes de ménages (Recensements)
        Route::post('/recensements', [SurveyApiController::class, 'createRecensement'])->name('recensements.create');

        // Enquêtes d'habitations (Maisons)
        Route::post('/maisons', [SurveyApiController::class, 'createMaison'])->name('maisons.create');

        // Enquêtes d'opérateurs économiques
        Route::post('/operateurs', [SurveyApiController::class, 'createOperateur'])->name('operateurs.create');

        // Endpoints de synchronisation offline/online (Sync)
        Route::post('/sync/pull', [App\Http\Controllers\Api\v1\SyncApiController::class, 'pull'])->name('sync.pull');
        Route::post('/sync/push', [App\Http\Controllers\Api\v1\SyncApiController::class, 'push'])->name('sync.push');
        Route::post('/sync', [App\Http\Controllers\Api\v1\SyncApiController::class, 'push'])->name('sync');

        // Référentiels géographiques et de paramétrages pour le mobile (Reference)
        Route::get('/references', [App\Http\Controllers\Api\v1\ReferenceApiController::class, 'index'])->name('references.index');

        // Indicateurs et affectations géographiques pour le dashboard mobile
        Route::get('/dashboard', [App\Http\Controllers\Api\v1\MobileDashboardController::class, 'getDashboard'])->name('mobile.dashboard');
        Route::get('/assignments', [App\Http\Controllers\Api\v1\MobileDashboardController::class, 'getAssignments'])->name('mobile.assignments');
        Route::get('/global-stats', [App\Http\Controllers\Api\v1\MobileDashboardController::class, 'getGlobalStats'])->name('mobile.global-stats');
        Route::get('/statistics/global', [App\Http\Controllers\Api\v1\MobileDashboardController::class, 'getGlobalStats'])->name('mobile.statistics.global');
        Route::get('/dashboard/statistics', [App\Http\Controllers\Api\v1\MobileDashboardController::class, 'getStatistics'])->name('mobile.dashboard.statistics');
        Route::get('/statistics/by-quartier', [App\Http\Controllers\Api\v1\MobileDashboardController::class, 'getStatistics'])->name('mobile.statistics.by-quartier');
        Route::get('/statistics/quartiers', [App\Http\Controllers\Api\v1\MobileDashboardController::class, 'getStatistics'])->name('mobile.statistics.quartiers');
        Route::get('/statistics/quartiers/{quartier}/carres', [App\Http\Controllers\Api\v1\MobileDashboardController::class, 'getCarreStatistics'])->name('mobile.statistics.quartier.carres');
        Route::get('/statistics/carres', [App\Http\Controllers\Api\v1\MobileDashboardController::class, 'getCarreStatistics'])->name('mobile.statistics.carres');

        // Endpoint de recherche globale multi-critères
        Route::get('/search', [App\Http\Controllers\Api\v1\SearchApiController::class, 'search'])->name('mobile.search');

        // -------------------------------------------------------------
        // REST API Fiscalité Municipale & Recouvrement (legacy)
        // -------------------------------------------------------------
        Route::get('/taxes', [App\Http\Controllers\Api\v1\TaxeApiController::class, 'index']);
        Route::get('/taxes/{id}', [App\Http\Controllers\Api\v1\TaxeApiController::class, 'show']);
        Route::post('/taxes', [App\Http\Controllers\Api\v1\TaxeApiController::class, 'store']);
        Route::put('/taxes/{id}', [App\Http\Controllers\Api\v1\TaxeApiController::class, 'update']);
        Route::delete('/taxes/{id}', [App\Http\Controllers\Api\v1\TaxeApiController::class, 'destroy']);

        Route::get('/paiements', [App\Http\Controllers\Api\v1\PaiementApiController::class, 'index']);
        Route::post('/paiements', [App\Http\Controllers\Api\v1\PaiementApiController::class, 'store']);
        Route::post('/paiements/create', [App\Http\Controllers\Api\v1\PaiementApiController::class, 'store']);

        // Legacy endpoint (conservé pour compatibilité)
        Route::get('/operateurs/{id}/taxes', [App\Http\Controllers\Api\v1\OperateurTaxeApiController::class, 'getOperateurTaxes']);
        Route::get('/dashboard/taxes', [App\Http\Controllers\Api\v1\FiscalDashboardApiController::class, 'getDashboardStats']);
        Route::get('/statistiques/taxes', [App\Http\Controllers\Api\v1\FiscalDashboardApiController::class, 'getDashboardStats']);

        Route::get('/recouvrements', [App\Http\Controllers\Api\v1\RecouvrementApiController::class, 'index']);
        Route::post('/recouvrements', [App\Http\Controllers\Api\v1\RecouvrementApiController::class, 'store']);

        Route::get('/exonerations', [App\Http\Controllers\Api\v1\ExonerationApiController::class, 'index']);
        Route::post('/exonerations', [App\Http\Controllers\Api\v1\ExonerationApiController::class, 'store']);

        // -------------------------------------------------------------
        // COLLECTE FISCALE MOBILE — Endpoints dédiés v2
        // -------------------------------------------------------------
        // Situation fiscale d'un opérateur (avec recalcul serveur)
        Route::get('/operators/{id}/taxes', [App\Http\Controllers\Api\v1\MobileTaxApiController::class, 'getOperateurTaxes'])
            ->name('mobile.operators.taxes');

        // Enregistrement d'un paiement unique (mode connecté)
        Route::post('/tax-payments', [App\Http\Controllers\Api\v1\MobileTaxApiController::class, 'store'])
            ->name('mobile.tax-payments.store');

        // Synchronisation par lots (mode hors-ligne → idempotent)
        Route::post('/tax-payments/sync', [App\Http\Controllers\Api\v1\MobileTaxApiController::class, 'syncBatch'])
            ->name('mobile.tax-payments.sync');

        // Tableau de bord KPI fiscal de l'enquêteur
        Route::get('/mobile/tax-dashboard', [App\Http\Controllers\Api\v1\MobileTaxApiController::class, 'taxDashboard'])
            ->name('mobile.tax-dashboard');

        // Gestion administrative - Rôles ADMIN requis
        Route::prefix('admin')->middleware('admin.api')->group(function () {
            // Quartiers
            Route::get('/quartiers', [App\Http\Controllers\Api\v1\AdminApiController::class, 'listQuartiers']);
            Route::post('/quartiers', [App\Http\Controllers\Api\v1\AdminApiController::class, 'storeQuartier']);
            Route::get('/quartiers/{id}', [App\Http\Controllers\Api\v1\AdminApiController::class, 'showQuartier']);
            Route::put('/quartiers/{id}', [App\Http\Controllers\Api\v1\AdminApiController::class, 'updateQuartier']);
            Route::delete('/quartiers/{id}', [App\Http\Controllers\Api\v1\AdminApiController::class, 'destroyQuartier']);
            Route::post('/quartiers/{id}/restore', [App\Http\Controllers\Api\v1\AdminApiController::class, 'restoreQuartier']);
            Route::post('/quartiers/{id}/toggle', [App\Http\Controllers\Api\v1\AdminApiController::class, 'toggleQuartier']);
            Route::post('/quartiers/{id}/duplicate', [App\Http\Controllers\Api\v1\AdminApiController::class, 'duplicateQuartier']);

            // Carrés
            Route::get('/carres', [App\Http\Controllers\Api\v1\AdminApiController::class, 'listCarres']);
            Route::post('/carres', [App\Http\Controllers\Api\v1\AdminApiController::class, 'storeCarre']);
            Route::get('/carres/{id}', [App\Http\Controllers\Api\v1\AdminApiController::class, 'showCarre']);
            Route::put('/carres/{id}', [App\Http\Controllers\Api\v1\AdminApiController::class, 'updateCarre']);
            Route::delete('/carres/{id}', [App\Http\Controllers\Api\v1\AdminApiController::class, 'destroyCarre']);
            Route::post('/carres/{id}/restore', [App\Http\Controllers\Api\v1\AdminApiController::class, 'restoreCarre']);
            Route::post('/carres/{id}/toggle', [App\Http\Controllers\Api\v1\AdminApiController::class, 'toggleCarre']);
            Route::post('/carres/{id}/duplicate', [App\Http\Controllers\Api\v1\AdminApiController::class, 'duplicateCarre']);

            // Secteurs
            Route::get('/secteurs', [App\Http\Controllers\Api\v1\AdminApiController::class, 'listSecteurs']);
            Route::post('/secteurs', [App\Http\Controllers\Api\v1\AdminApiController::class, 'storeSecteur']);
            Route::get('/secteurs/{id}', [App\Http\Controllers\Api\v1\AdminApiController::class, 'showSecteur']);
            Route::put('/secteurs/{id}', [App\Http\Controllers\Api\v1\AdminApiController::class, 'updateSecteur']);
            Route::delete('/secteurs/{id}', [App\Http\Controllers\Api\v1\AdminApiController::class, 'destroySecteur']);
            Route::post('/secteurs/{id}/restore', [App\Http\Controllers\Api\v1\AdminApiController::class, 'restoreSecteur']);
            Route::post('/secteurs/{id}/toggle', [App\Http\Controllers\Api\v1\AdminApiController::class, 'toggleSecteur']);
            Route::post('/secteurs/{id}/duplicate', [App\Http\Controllers\Api\v1\AdminApiController::class, 'duplicateSecteur']);

            // Avenues
            Route::get('/avenues', [App\Http\Controllers\Api\v1\AdminApiController::class, 'listAvenues']);
            Route::post('/avenues', [App\Http\Controllers\Api\v1\AdminApiController::class, 'storeAvenue']);
            Route::get('/avenues/{id}', [App\Http\Controllers\Api\v1\AdminApiController::class, 'showAvenue']);
            Route::put('/avenues/{id}', [App\Http\Controllers\Api\v1\AdminApiController::class, 'updateAvenue']);
            Route::delete('/avenues/{id}', [App\Http\Controllers\Api\v1\AdminApiController::class, 'destroyAvenue']);
            Route::post('/avenues/{id}/restore', [App\Http\Controllers\Api\v1\AdminApiController::class, 'restoreAvenue']);
            Route::post('/avenues/{id}/toggle', [App\Http\Controllers\Api\v1\AdminApiController::class, 'toggleAvenue']);
            Route::post('/avenues/{id}/duplicate', [App\Http\Controllers\Api\v1\AdminApiController::class, 'duplicateAvenue']);

            // Agents
            Route::get('/agents', [App\Http\Controllers\Api\v1\AdminApiController::class, 'listAgents']);
            Route::post('/agents', [App\Http\Controllers\Api\v1\AdminApiController::class, 'storeAgent']);
            Route::get('/agents/{id}', [App\Http\Controllers\Api\v1\AdminApiController::class, 'showAgent']);
            Route::put('/agents/{id}', [App\Http\Controllers\Api\v1\AdminApiController::class, 'updateAgent']);
            Route::delete('/agents/{id}', [App\Http\Controllers\Api\v1\AdminApiController::class, 'destroyAgent']);
            Route::get('/fonctions', [App\Http\Controllers\Api\v1\AdminApiController::class, 'listFonctions']);

            // Users
            Route::get('/users', [App\Http\Controllers\Api\v1\AdminApiController::class, 'listUsers']);
            Route::post('/users', [App\Http\Controllers\Api\v1\AdminApiController::class, 'storeUser']);
            Route::get('/users/{id}', [App\Http\Controllers\Api\v1\AdminApiController::class, 'showUser']);
            Route::put('/users/{id}', [App\Http\Controllers\Api\v1\AdminApiController::class, 'updateUser']);
            Route::delete('/users/{id}', [App\Http\Controllers\Api\v1\AdminApiController::class, 'destroyUser']);
            Route::get('/roles', [App\Http\Controllers\Api\v1\AdminApiController::class, 'listRoles']);
            Route::put('/users/{id}/permissions', [App\Http\Controllers\Api\v1\AdminApiController::class, 'updateUserPermissions']);

            // Affectations (Assignments)
            Route::post('/agents/{agentId}/affectations', [App\Http\Controllers\Api\v1\AdminApiController::class, 'storeAgentAffectation']);
            Route::delete('/affectations/{id}', [App\Http\Controllers\Api\v1\AdminApiController::class, 'destroyAffectation']);
        });
    });
});
