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
        Route::middleware('auth:sanctum')->get('/profile', [App\Http\Controllers\Api\v1\AuthApiController::class, 'profile'])->name('auth.profile');
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
            Route::get('/users/{id}', [App\Http\Controllers\Api\v1\AdminApiController::class, 'showUser']);
            Route::put('/users/{id}', [App\Http\Controllers\Api\v1\AdminApiController::class, 'updateUser']);
            Route::delete('/users/{id}', [App\Http\Controllers\Api\v1\AdminApiController::class, 'destroyUser']);
            Route::get('/roles', [App\Http\Controllers\Api\v1\AdminApiController::class, 'listRoles']);
            Route::put('/users/{id}/permissions', [App\Http\Controllers\Api\v1\AdminApiController::class, 'updateUserPermissions']);
        });
    });
});
