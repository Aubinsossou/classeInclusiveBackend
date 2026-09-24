# Contrat API — Espace Client (Frontend ↔ Backend Classe Inclusive)

Document destiné à l'agent frontend pour adapter les interfaces Client au backend.
Backend : Laravel 12 + Passport (Bearer). Base URL : `http://127.0.0.1:8000/api`.

> Source de vérité : `routes/api.php:38-53`, `app/Http/Controllers/Api/ClientController.php`, `app/Models/Client.php`, `config/cors.php`.

---

## 1. Base URL, headers, CORS

| Élément | Valeur |
|---|---|
| Base URL locale | `http://127.0.0.1:8000/api` |
| Format requêtes | `Content-Type: application/json`, `Accept: application/json` |
| Auth routes protégées | `Authorization: Bearer <access_token>` |
| CORS (`config/cors.php`) | `paths: api/*`, `methods/origins/headers: *`, `supports_credentials: false` → aucun cookie/CSRF, tout passe par le header Bearer |

## 2. Authentification Client

### 2.1 Inscription — `POST /v1/client/register` (publique)

Fichier : `app/Http/Controllers/Api/ClientController.php:22-60`.

Body JSON :

```json
{
  "name": "Client Demo",
  "email": "client@exemple.com",
  "numero": "0700000000",
  "password": "test1234"
}
```

Règles : `name` requis ≤255 ; `email` requis, format email, unique table `clients` ; `numero` optionnel ; `password` requis ≥4 caractères.

Réponse `200` : `{ "status": "success", "message": "Client créé avec succès", "data": { "id": 3, "name": "...", "email": "...", "numero": null } }`.

Erreurs : `400` → `{ "errors": { "email": ["..."] }, "message": "Validation échoué" }`. Email déjà utilisé = 400 (pas 409).

### 2.2 Connexion — `POST /v1/client/login` (publique)

Fichier : `ClientController.php:62-103`.

Body : `{ "email": "client@exemple.com", "password": "test1234" }`.

Réponse `200` (testée en réel) : `{ "status": "success", "message": "Connexion réussie", "data": { "id", "name", "email", "roles": [{ "name": "client" }] }, "access_token": "<~1027 car.>", "refresh_token": "<token>" }`.

Frontend : stocker `access_token`, l'envoyer sur chaque route §3. Le `refresh_token` actuel est un second access_token Passport, pas un vrai refresh OAuth : si `401`, renvoyer vers login.

Erreurs : `400` email inconnu → `{ "message": "Aucun client trouvé avec ce mail" }` ; `400` mdp faux → `{ "message": "Email ou mot de passe incorrect" }`.

### 2.3 Déconnexion — `DELETE /v1/client/logout` (protégée)

Fichier : `ClientController.php:196-204`. Révoque le token courant. Réponse `200` `{ "status": "Success", "message": "Logout is success" }`. Frontend : supprimer le token local après appel.

## 3. Routes protégées (lecture seule) — toutes en `GET` + Bearer

Préfixe commun : `/v1/client`. Middleware : `auth:client_api` (`routes/api.php:43`).

| Écran frontend suggéré | Méthode + URL | Contrôleur | Contenu `data` |
|---|---|---|---|
| Profil / header | `GET /v1/client/getClient` | `ClientController.php:105-123` | `{ id, name, email, numero, roles }`, `password` masqué |
| Liste écoles | `GET /v1/client/ecoles` | `:125-132` `Ecole::all()` | `[{ id, name, numero, email }]` |
| Liste classes | `GET /v1/client/classes` | `:134-141` + `ecole, eleves, enseignant, matieres` | Classe + école + élèves + enseignant + matières |
| Liste matières | `GET /v1/client/matieres` | `:143-150` + `ecole, cours` | Matière + école + cours liés |
| Liste enseignants | `GET /v1/client/enseignants` | `:152-162` + `ecole, classe`, `password` masqué | Enseignant **sans** `password` |
| Catalogue cours | `GET /v1/client/cours` | `:164-176` + `medias, matiere, classe, enseignant` (mdp enseignant masqué) | Cours + `medias[]` (`{ type: video|image|audio, url, path, ordre }`) + matière/classe/enseignant |
| Quiz | `GET /v1/client/quizzes` | `:178-185` + `cours, questions.reponses` | Quiz + cours + questions + réponses (dont `status` — **ne pas afficher brut**, voir §6) |
| Référentiel handicaps | `GET /v1/client/handicaps` | `:187-194` `Handicap::all()` | `[{ id, name }]` |

Exemple appel :

```js
const res = await fetch("http://127.0.0.1:8000/api/v1/client/cours", {
  headers: { Accept: "application/json", Authorization: `Bearer ${accessToken}` }
});
const { status, data } = await res.json(); // status === "success", data = Cours[]
```

Comportements : pas de pagination (tableaux complets) ; listes lourdes possibles (`classes`, `cours`, `quizzes`) → prévoir lazy-load/skeleton. `401` = token absent/invalide/révoqué → retour login.

## 4. Modèles de données utiles au frontend

```ts
type Client = { id: number; name: string; email: string; numero: string | null };
type Ecole = { id: number; name: string; numero: string | null; email: string };
type Classe = { id: number; name: string; ecole_id: number; ecole?: Ecole; eleves?: Eleve[]; enseignant?: Enseignant; matieres?: Matiere[] };
type Matiere = { id: number; name: string; ecole_id: number; ecole?: Ecole; cours?: Cours[] };
type Enseignant = { id: number; name: string; prenom: string; matricule: string; email: string; ecole_id: number; classe_id: number | null; ecole?: Ecole; classe?: Classe };
type Cours = { id: number; title: string; description: string | null; contenu: string | null; resume: string | null; matiere_id: number; classe_id: number; enseignant_id: number; is_published: boolean; quiz_authorise: boolean; date_programmation: string | null; medias?: CoursMedia[]; matiere?: Matiere; classe?: Classe; enseignant?: Enseignant };
type CoursMedia = { id: number; type: "video" | "image" | "audio"; url: string; path: string | null; ordre: number };
type Quiz = { id: number; name: string; cours_id: number; enseignant_id: number; cours?: Cours; questions?: Question[] };
type Question = { id: number; question: string; quiz_id: number; reponses?: Reponse[] };
type Reponse = { id: number; name: string; status: string; question_id: number };
type Handicap = { id: number; name: string };
```

Notes : `password` Client masqué (`app/Models/Client.php:19-24`) ; `password` enseignant masqué dans les listes ; médias triés par `ordre` (`app/Models/Cours.php:36-40`). URL médias : utiliser `url` tel quel (ex `cours/videos/*.mp4`, `cours/images/*.jpg`, `cours/audios/*`).

## 5. Gestion d'erreurs recommandée

| Cas | Détection | Action UI |
|---|---|---|
| 400 validation | `errors` + `message: "Validation échoué"` | Afficher erreurs par champ |
| 400 login | `message` (`Aucun client trouvé...` / `Email ou mot de passe incorrect`) | Message sous le formulaire, sans retry auto |
| 401 | Pas de JSON métier | Vider token, rediriger login |
| 404/405 | Mauvais verbe ou URL sans `/api` | Vérifier méthode + préfixe `/api/v1/client/...` |
| 500 historique `Personal access client not found` | Déjà corrigé (5 clients OAuth créés) | Si réapparition : base ciblée vide (mauvais `.env`/serveur), pas le frontend |

## 6. Limites connues (à respecter côté UI)

1. **Lecture seule** : aucun `POST/PUT/DELETE` métier Client — masquer tout bouton créer/modifier/supprimer.
2. **Pas de pagination ni filtre serveur** : filtrer côté UI par `ecole_id`, `classe_id`, `matiere_id`, `is_published`.
3. **Réponses quiz exposées** : `GET /quizzes` inclut `reponses[].status` — ne jamais afficher la correction avant soumission (aucun endpoint de soumission Client).
4. **`refresh_token` non standard** : second access_token ; stratégie 401 → re-login.
5. **Pas de profil update / reset password Client** : ne pas prévoir ces écrans (aucune route).
6. **Données élèves absentes** de l'espace Client (volontaire) — aucune route élèves/notes.
7. **Casse `status` incohérente** (`success` vs `Success`) — comparer en insensible à la casse.
8. **Déploiement backend** : exiger `oauth_clients` peuplé (`provider=clients` + `personal_access`) et `storage/oauth-*.key` présents, sinon login = 500.
