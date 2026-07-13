<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RecensementController;
use App\Http\Controllers\MaisonController;
use App\Http\Controllers\OperateurController;
use App\Http\Controllers\AgentController;
use App\Http\Controllers\Parameters\TypeBatimentController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// 1. Routes d'authentification Web publiques
Route::middleware('guest')->group(function () {
    Route::get('/login', [App\Http\Controllers\Auth\LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [App\Http\Controllers\Auth\LoginController::class, 'login']);
});

// 2. Espace d'administration Web sécurisé (Session-based)
Route::middleware(['auth', 'middleware' => App\Http\Middleware\ThemeLayoutMiddleware::class])->group(function () {
    
    // Déconnexion
    Route::post('/logout', [App\Http\Controllers\Auth\LoginController::class, 'logout'])->name('logout');

    // Tableau de bord global
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Profil utilisateur & Paramètres du compte
    Route::get('/profile', [App\Http\Controllers\ProfileController::class, 'show'])->name('profile.show');
    Route::get('/settings', [App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/settings', [App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');

    // -------------------------------------------------------------
    // Enquêtes Métiers (Sécurisées de base via les Policies)
    // -------------------------------------------------------------

    // Recensements (Ménages)
    Route::resource('recensement', RecensementController::class);
    Route::post('recensement/{recensement}/transition', [RecensementController::class, 'transition'])->name('recensement.transition');

    // Habitations (Maisons)
    Route::resource('maison', MaisonController::class);
    Route::post('maison/{maison}/transition', [MaisonController::class, 'transition'])->name('maison.transition');

    // Opérateurs Économiques
    Route::resource('operateur', OperateurController::class);
    Route::post('operateur/{operateur}/transition', [OperateurController::class, 'transition'])->name('operateur.transition');

    // -------------------------------------------------------------
    // Administration & Paramétrages (Réservé ROLE_ADMIN / RBAC)
    // -------------------------------------------------------------
    Route::middleware('can:PARAM_VIEW')->prefix('admin')->group(function () {
        
        // Exemple de CRUD de Paramètres (S'appuie sur AbstractParameterController)
        Route::prefix('type-batiment')->name('type-batiment.')->group(function () {
            Route::get('/', [TypeBatimentController::class, 'index'])->name('index');
            Route::get('/create', [TypeBatimentController::class, 'create'])->name('create');
            Route::post('/', [TypeBatimentController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [TypeBatimentController::class, 'edit'])->name('edit');
            Route::put('/{id}', [TypeBatimentController::class, 'update'])->name('update');
            Route::delete('/{id}', [TypeBatimentController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/restore', [TypeBatimentController::class, 'restore'])->name('restore');
            Route::post('/{id}/toggle', [TypeBatimentController::class, 'toggle'])->name('toggle');
            Route::post('/{id}/duplicate', [TypeBatimentController::class, 'duplicate'])->name('duplicate');
        });

        // Autres CRUDs paramétriques génériques
        $paramTables = ['assainissement', 'avenue', 'besoin-prioritaire', 'carre', 'categorie-activite', 'categorie-operateur', 'secteur', 'source-eau', 'source-energie', 'type-propriete', 'fonction', 'quartier'];
        foreach ($paramTables as $param) {
            $controllerClass = "App\\Http\\Controllers\\Parameters\\" . Str::studly($param) . "Controller";
            Route::prefix($param)->name("{$param}.")->group(function () use ($controllerClass) {
                Route::get('/', [$controllerClass, 'index'])->name('index');
                Route::get('/create', [$controllerClass, 'create'])->name('create');
                Route::post('/', [$controllerClass, 'store'])->name('store');
                Route::get('/{id}/edit', [$controllerClass, 'edit'])->name('edit');
                Route::put('/{id}', [$controllerClass, 'update'])->name('update');
                Route::delete('/{id}', [$controllerClass, 'destroy'])->name('destroy');
                Route::post('/{id}/restore', [$controllerClass, 'restore'])->name('restore');
                Route::post('/{id}/toggle', [$controllerClass, 'toggle'])->name('toggle');
                Route::post('/{id}/duplicate', [$controllerClass, 'duplicate'])->name('duplicate');
            });
        }

        // Gestion des Agents et Utilisateurs
        Route::resource('agent', AgentController::class);
        Route::resource('user', App\Http\Controllers\UserController::class);
        Route::get('/audit-log', [App\Http\Controllers\AuditLogController::class, 'index'])->middleware('can:AUDIT_VIEW')->name('audit.index');
    });
});
