# Application de Recensement Territorial & Cartographie Socio-Économique
## 🚀 Squelette Applicatif Cible - Laravel 11 & Metronic (Bootstrap 5)

Ce répertoire contient l'ossature d'architecture cible complète migrée depuis la base **Symfony 7.1 d'origine**. Il comporte l'ensemble du socle de données, d'API mobile, de sécurité RBAC avec politiques d'accès fins, d'audit global, et d'interface d'administration sous **Laravel 11**.

---

## 🛠️ Installation & Configuration Initiale

Suivez ces étapes pour installer et initialiser le projet localement :

### 1. Pré-requis système
*   **PHP 8.2 ou supérieur** (avec extensions requises : `pdo_mysql`, `bcmath`, `ctype`, `fileinfo`, `json`, `mbstring`, `openssl`, `xml`).
*   **Composer** (gestionnaire de dépendances PHP).
*   **MySQL 8.0+** ou **MariaDB 10.3+**.

### 2. Installation des dépendances Composer
Dans le répertoire `laravel-app/`, exécutez la commande suivante :
```bash
composer install
```

### 3. Configuration de l'environnement (`.env`)
Dupliquez le fichier d'exemple pour créer votre fichier `.env` local :
```bash
cp .env.example .env
```
Éditez ensuite le fichier `.env` pour y renseigner vos variables d'accès à la base de données :
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=recensement_db
DB_USERNAME=votre_utilisateur
DB_PASSWORD=votre_mot_de_passe
```

Générez la clé de sécurité de l'application :
```bash
php artisan key:generate
```

### 4. Génération du Schéma SQL & Peuplement (RBAC & Paramètres)
Exécutez l'ensemble des migrations ordonnées et des seeders pour initialiser les permissions, les rôles, les nomenclatures d'enquêtes et la hiérarchie géographique :
```bash
php artisan migrate --seed
```

---

## 🧪 Exécution de la Suite de Tests

Le projet est configuré pour supporter le framework de tests moderne **Pest** (ou PHPUnit standard).

Pour exécuter la validation de cohérence démographique et d'APIs de collecte, lancez :
```bash
php artisan test
```

---

## 🧭 Architecture des Dossiers Physique Livrée

*   `app/Enums/` : Les statuts métiers en Backed Enums PHP (ex: `RecensementStatut`).
*   `app/Helpers/` : Le singleton `ThemeHelper` pilotant l'affichage d'actifs Metronic.
*   `app/Http/Controllers/` : 
    *   `AbstractParameterController` : Moteur générique de 14 CRUDs.
    *   `DashboardController` : Statistiques consolidées optimisées par Cache.
    *   `MaisonController`, `RecensementController`, `OperateurController` : Enquêtes d'exploitation.
    *   `Api/v1/` : Endpoints d'API de collecte et d'authentification mobile (`SurveyApiController`, `AuthApiController`).
*   `app/Http/Middleware/` : 
    *   `AuditLogMiddleware` : Journalisation d'écriture (IP, OS, delta de données JSON).
    *   `ThemeLayoutMiddleware` : Injection d'attributs HTML de structure Metronic.
*   `app/Http/Requests/` : Les Form Requests de validations croisées complexes (`SaveRecensementRequest`).
*   `app/Models/` : Les entités et leurs relations, intégrant le scope global de cloisonnement transparent enquêteur (`UserRecordFilterScope`).
*   `app/Policies/` : Les barrières de droits fins d'accès aux instances (ex: `MaisonPolicy`).
*   `app/Services/` : Logique de provisionnement de comptes et de machines de transition d'états d'enquêtes (`AgentAccountService`, `MaisonWorkflowService`).
*   `database/migrations/` : Les schémas SQL ordonnés contournant les dépendances circulaires.
*   `resources/views/` : Les templates de présentation Blade unifiés avec Metronic.

---

Excellent codage et beaucoup de succès dans le déploiement de votre nouvelle plateforme de cartographie socio-démographique ! 🚀💻🔒🛡️🏁
