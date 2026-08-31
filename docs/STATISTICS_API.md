# Documentation API — Module de Statistiques Territoriales Hiérarchiques

Cette documentation décrit les endpoints REST sécurisés mis à disposition par le backend Laravel pour la consommation du module de statistiques hiérarchiques (**Global &rarr; Quartiers &rarr; Carrés**) dans le back-office web et l'application mobile Ionic.

---

## 1. Sécurité et Gestion du Scope Géographique

Toutes les requêtes vers les endpoints de statistiques vérifient automatiquement le périmètre de l'utilisateur authentifié (`User` &rarr; `Agent` &rarr; `Affectation`).

- **Administrateur / Super-Admin (`ROLE_ADMIN`)** : Vue illimitée sur l'ensemble de la commune (Global).
- **Recenseur / Enquêteur (`ROLE_ENQUETEUR`)** : Vue restreinte aux quartiers et carrés figurant dans ses affectations actives.
- **Accès Non Autorisé** : Toute tentative d'accès explicite à un quartier ou carré hors périmètre (ex: `?quartier_id=99`) retourne immédiatement un statut **`HTTP 403 Forbidden`**.
- **Utilisateur Sans Affectation** : Retourne une structure valide avec un tableau vide (`items: []`) sans crash serveur.

---

## 2. Spécification des Endpoints

### 2.1 Niveau 1 : Statistiques Globales / Agrégées
- **URL** : `GET /api/v1/statistics/global` ou `GET /api/v1/global-stats`
- **Authentification** : Bearer Token (Sanctum)
- **Description** : Renvoie les indicateurs clés agrégés (Population, Ménages, Habitats, Opérateurs, Paiements et Progression %).

#### Exemple de Réponse (200 OK)
```json
{
    "status": "success",
    "message": "Statistiques globales récupérées avec succès.",
    "data": {
        "scope": "agent_scope",
        "total_menages": 142,
        "total_population": 850,
        "total_hommes": 410,
        "total_femmes": 440,
        "total_enfants": 320,
        "total_jeunes": 290,
        "total_handicapes": 15,
        "total_habitats": 120,
        "total_operateurs": 45,
        "total_fiches": 307,
        "fiches_validees": 280,
        "fiches_en_attente": 27,
        "total_paiements": 18,
        "montant_encaisse": 135000.0,
        "progression": 88.5
    }
}
```

---

### 2.2 Niveau 2 : Consolidation par Quartier
- **URL** : `GET /api/v1/statistics/quartiers`
- **Params Optionnels** : `quartier_id` (string UUID ou int)
- **Description** : Renvoie la liste agrégée des quartiers autorisés avec les effectifs de ménages, habitations, opérateurs économiques, montant encaissé et taux de progression.

#### Exemple de Réponse (200 OK)
```json
{
    "status": "success",
    "message": "Statistiques par quartier récupérées avec succès.",
    "data": {
        "scope": "quartiers",
        "items": [
            {
                "id": "quartier-uuid-01",
                "nom": "Quartier Sud",
                "code": "QS-01",
                "menages": 80,
                "habitants": 480,
                "habitats": 60,
                "operateurs": 25,
                "taxes": 25,
                "paiements": 10,
                "montantEncaisse": 75000.0,
                "fiches_validees": 150,
                "fiches_en_attente": 15,
                "fiches_collectees": 165,
                "progression": 82.5
            }
        ],
        "totals": {
            "menages": 80,
            "habitants": 480,
            "habitats": 60,
            "operateurs": 25,
            "fiches_validees": 150,
            "fiches_en_attente": 15,
            "fiches_collectees": 165,
            "paiements": 10,
            "montantEncaisse": 75000.0,
            "progression": 82.5
        }
    }
}
```

#### Exemple de Réponse d'Erreur (403 Forbidden)
```json
{
    "status": "error",
    "message": "Accès refusé : Le quartier #quartier-hors-zone ne fait pas partie de votre périmètre autorisé.",
    "data": []
}
```

---

### 2.3 Niveau 3 : Consolidation Granulaire par Carré
- **URL** : `GET /api/v1/statistics/quartiers/{quartier}/carres` ou `GET /api/v1/statistics/carres?quartier_id={quartier_id}`
- **Params Optionnels** : `carre_id` (string UUID)
- **Description** : Renvoie le détail granulaire par carré pour un quartier spécifié.

#### Exemple de Réponse (200 OK)
```json
{
    "status": "success",
    "message": "Statistiques des carrés récupérées avec succès.",
    "data": {
        "scope": "carres",
        "quartier": {
            "id": "quartier-uuid-01",
            "nom": "Quartier Sud",
            "code": "QS-01"
        },
        "items": [
            {
                "id": "carre-uuid-01",
                "nom": "Carré S1",
                "code": "CR-S1",
                "quartier_id": "quartier-uuid-01",
                "quartier_nom": "Quartier Sud",
                "menages": 40,
                "habitants": 240,
                "habitats": 30,
                "operateurs": 12,
                "taxes": 12,
                "paiements": 5,
                "montantEncaisse": 37500.0,
                "fiches_validees": 75,
                "fiches_en_attente": 7,
                "fiches_collectees": 82,
                "progression": 90.0
            }
        ]
    }
}
```

---

## 3. Optimisation des Performances (Requêtes SQL)

Le module s'appuie sur le service `StatisticsService` qui exécute des requêtes SQL groupées via Eloquent (`selectRaw` + `GROUP BY`). Aucune boucle N+1 n'est exécutée, ce qui garantit des temps de réponse d'API **inférieurs à 50ms** même avec des dizaines de milliers d'enregistrements en base de données.
