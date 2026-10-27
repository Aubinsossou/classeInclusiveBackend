# Classe Inclusive - API Back-End (Reference Front-End)

> Document de reference pour l'equipe FRONT-END. Un developpeur front qui ne connait pas le back doit pouvoir consommer l'API sans lire le code PHP.
> Reconstruit uniquement a partir du code : `routes/api.php` (verifie par `php artisan route:list`), controleurs `app/Http/Controllers/**`, modeles `app/Models/**`, migrations `database/migrations/**`, `config/auth.php`, `config/cors.php`, `config/passport.php`, `config/cloudinary.php`, `composer.json`, `.env.example`, `bootstrap/app.php`.
> Aucun secret reel dans ce document : toutes les valeurs sont FICTIVES. Si une information n'a pas ete trouvee dans le code, elle est marquee "A confirmer".

**Base URL (exemple fictif) :** `http://localhost:8000/api` - toutes les routes API sont prefixees par `/api` (prefixe Laravel) + `/v1/...`, sauf `GET /indexNote` et `GET /indexEleve` qui sont a la racine `/api`.

## Sommaire

1. [Presentation et stack](#1-presentation-du-projet-et-stack-technique)
2. [Installation et lancement](#2-installation-et-lancement)
3. [Authentification](#3-authentification)
4. [Liste complete des endpoints](#4-liste-complete-des-endpoints)
5. [Modeles de donnees exposes](#5-modeles-de-donnees-exposes)
6. [Format standard des reponses et erreurs](#6-format-standard-des-reponses-et-des-erreurs)
7. [Roles et permissions](#7-roles-et-permissions)
8. [Upload de fichiers et medias](#8-upload-de-fichiers-et-medias)
9. [CORS, limites, versioning, contraintes](#9-cors-limites-de-requetes-versioning-et-contraintes-front)
10. [Ecarts et points a confirmer](#10-ecarts-et-points-a-confirmer)

---

## 1. Presentation du projet et stack technique

Plateforme scolaire inclusive multi-acteurs : **Ecoles** (portail admin), **Enseignants** (cours multimedia, quiz, eleves de leur classe), **Eleves** (connexion par code, quiz/notes, bilans), **Clients** (lecture globale + statistiques analytiques).

| Composant | Version / valeur lue |
|---|---|
| PHP | `composer.json` exige `^8.2` (runtime observe : 8.5) |
| Laravel Framework | `^12.0` |
| Authentification | `laravel/passport` `^13.0`, driver `passport` sur 5 guards (voir section 3) |
| Autorisations | `spatie/laravel-permission` `^7.2` (roles `ecole`, `enseignant`, `eleve`, `client`) |
| Base de donnees | Defaut `sqlite` (`config/database.php` : default `env('DB_CONNECTION','sqlite')`, `.env.example` : `DB_CONNECTION=sqlite`). MySQL/MariaDB/PgSQL supportes via `.env`. **A confirmer** : moteur utilise en staging/prod |
| Medias | Stockage **local** disque `public` (`storage/app/public/cours/...`, servi via `Storage::url()` soit `/storage/...`). `intervention/image` `^3.11` (compression JPEG), conversion video via binaire systeme `ffmpeg` (appel `exec()`). `cloudinary-labs/cloudinary-laravel` `^3.0` est installe et configure (`config/cloudinary.php`) mais **jamais appele** dans le code lu |
| Mail | `MAIL_MAILER=log` (mot de passe enseignant logue, pas envoye en prod sans config SMTP) |
| Pas de FormRequests ni de Resources | Validation inline par `Validator::make()` dans chaque controleur ; reponses JSON construites a la main |
| Pas de Policies | Dossier `app/Policies` inexistant ; controles d'acces codes a la main (`classe_id`, `enseignant_id`) |
| Prefixe API | `/api` (Laravel) + `/v1/...` sauf `GET /indexNote`, `GET /indexEleve` a la racine `/api` |

## 2. Installation et lancement

### 2.1 Prerequis

- PHP >= 8.2 avec extensions `pdo_sqlite` (ou `pdo_mysql`), `gd`, `mbstring`, `openssl`, `fileinfo`
- Composer 2, Node 18+ (optionnel, `vite`), `ffmpeg` installe sur le systeme (sinon les videos non-mp4 sont ignorees silencieusement)
- Aucun compte externe requis (Cloudinary inutilise a ce jour)

### 2.2 Procedure (valeurs fictives)

### 2.3 Exemple de `.env` (FICTIF)

```env
APP_NAME="Classe Inclusive"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
DB_CONNECTION=sqlite
MAIL_MAILER=log
MAIL_FROM_ADDRESS="bonjour@exemple.test"
FILESYSTEM_DISK=public
```

### 2.4 Seeders et comptes de demo

- `DatabaseSeeder` : cree uniquement un `User` (`test@example.com`) - table `users` **inutilisee par l'API**.
- `ComptesFictifsSeeder` (a lancer explicitement, jamais en prod) : roles ecole/enseignant/eleve/client ; handicaps Visuel/Auditif/Moteur/Aucun ; 1 ecole, classes 6e A/6e B, 2 enseignants (dont 1 sans classe), 5 eleves (codes 100001-100004 + 200001), 1 client. Mot de passe fictif de type `password123` (**A confirmer** avant usage).
- Ancien README : conserve le perimetre multi-acteurs et `php artisan serve` ; le reste est remplace par ce document.

## 3. Authentification

### 3.1 Principes (lus dans `config/auth.php`)

| Guard Passport | Modele | Obtenu via |
|---|---|---|
| `ecole_api` | `App\Models\Ecole` | `POST /v1/ecole/register` puis `POST /v1/ecole/login` |
| `enseignant_api` | `App\Models\Enseignant` | cree par l'ecole ou `POST /v1/enseignant/register` ; login `POST /v1/enseignant/login` |
| `eleve_api` | `App\Models\Eleve` | cree par ecole/enseignant ; login `POST /v1/eleve/login` par **code uniquement** |
| `client_api` | `App\Models\Client` | `POST /v1/client/register` puis `POST /v1/client/login` |
| `api` (users) | `App\Models\User` | `GET /user` uniquement - **hors perimetre front** |


### 3.2 Flux complet

**Inscription ecole** `POST /api/v1/ecole/register` : cree l'ecole (role `ecole` assigne). **Ne renvoie aucun token** : enchainer avec login. Idem **client** `POST /api/v1/client/register`.

**Inscription enseignant publique** `POST /api/v1/enseignant/register` : **sans token et sans mot de passe dans la reponse** (genere serveur + envoye par mail). Le front ecole utilisera `POST /api/v1/ecole/enseignant/registerEnseignant` (authentifie) qui renvoie `password` en clair.

**Eleve** : pas d'inscription publique. Cree par l'ecole ou l'enseignant, avec un `code` (max 6 car.).

**Connexion** : `POST .../login` donne `200` avec `access_token` + `refresh_token` + `data`. Exemple :

```json
// POST /api/v1/ecole/login
{ "email": "ecole@exemple.test", "password": "mot-de-passe-fictif" }
// -> 200
{
  "status": "success",
  "message": "Connexion reussi",
  "data": { "id": 1, "name": "Lycee Exemple", "email": "ecole@exemple.test" },
  "access_token": "<TOKEN_FICTIF>",
  "refresh_token": "<TOKEN_FICTIF>"
}
```

Eleve : `{ "code": "100001" }` donne les memes cles.

**Deconnexion** : `DELETE /api/v1/ecole/logout`, `DELETE /api/v1/enseignant/logout`, `POST /api/v1/eleve/logout`, `DELETE /api/v1/client/logout`. Revoque **le token courant uniquement**. Reponse `200 { "status": "Success", "message": "Logout is success" }`.

**Refresh** : le login renvoie un second token nomme `refresh_token` mais c'est un **token d'acces classique**, pas un refresh OAuth. **Aucune route `/oauth/token` configuree pour le front**. **Conduite front : A confirmer** - refaire un `POST .../login` a l'expiration.

### 3.3 Duree de validite

**A confirmer** : aucune duree dans le code lu, donc defaut Passport 13. Le front gere le 401 en redirigeant vers le login concerne.

## 4. Liste COMPLETE des endpoints

Conventions : `[public]` sans token, `[auth]` Bearer requis (guard indique). Corps en JSON sauf `multipart`. URLs avec prefixe `/api`.

### 4.1 Authentification / inscription ([public] sauf logout)


### 4.2 Espace ecole - profil ([auth] `ecole_api`)

| Methode | URL | Reponse |
|---|---|---|
| GET | `/v1/ecole/index` | `200 {status:"success",data:Ecole[]}` - **TOUTES les ecoles**, sans pagination |
| GET | `/v1/ecole/getEcole` | `200 {status:"Success",data:Ecole}` avec `classes.matieres, matieres, eleves.handicap, enseignants` + `eleves_handicap_count` |

- Token : **Bearer OAuth2 Passport** (opaque, pas un JWT lisible).
- En-tetes obligatoires :

```http
Authorization: Bearer <access_token_du_guard_concerne>
Accept: application/json
Content-Type: application/json
```

Un token `ecole_api` ne passe PAS sur une route `enseignant_api`. Chaque espace a son propre token.


```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
# Comptes de demo (NE JAMAIS executer sur dev/prod partagee sans accord) :
php artisan db:seed --class=Database\Seeders\ComptesFictifsSeeder
# Cles Passport (OAuth) - commande exacte A confirmer selon Passport 13 :
php artisan passport:keys
php artisan storage:link
php artisan serve
```

### 4.3 Espace ecole - Classes ([auth] `ecole_api`)

| Methode | URL | Corps | Reponse / erreurs |
|---|---|---|---|
| GET | `/v1/ecole/classe/index/{id}` | `{id}` = ecole_id | `200 {status:"Success",data:Classe[]}` avec `eleves, enseignant, matieres`. `data:[]` si inconnu (pas de 404) |
| POST | `/v1/ecole/classe/store` | `name*`, `ecole_id*` exists:ecoles | `200 {status:"Success",data}` / `400` validation |
| GET | `/v1/ecole/classe/edit/{id}` | - | `200 {status,data,classeEleves}` ou `200 {status:"Echec"}` (code 200 !) |
| PUT | `/v1/ecole/classe/update/{id}` | `name*, ecole_id*` | `200 {status:"Success",data}` / validation en `200` / `400` si inconnu |
| DELETE | `/v1/ecole/classe/destroy/{id}` | - | `200 {status:"Success"}` / **500 possible** si inexistant |

Front : envoyer `PUT` (pas `POST`) sur `update` classe. Les autres `update` utilisent `POST`.

### 4.4 Espace ecole - Matieres ([auth] `ecole_api`)

| Methode | URL | Corps | Reponse |
|---|---|---|---|
| GET | `/v1/ecole/matiere/index/{id}` | `{id}` = ecole_id | `200 {data:Matiere[]}` avec `ecole,cours` |
| POST | `/v1/ecole/matiere/store` | `name*`, `ecole_id*` exists:ecoles | `200 {data}` / `400` |
| GET | `/v1/ecole/matiere/edit/{id}` | - | `200 {data,matiereCours}` ou `200 {status:"Echec"}` |
| POST | `/v1/ecole/matiere/update/{id}` | `name*`, `ecole_id*` (regle `exists:ecoles` sans colonne) | `200` / validation en `200` / `400` si inconnu |
| DELETE | `/v1/ecole/matiere/destroy/{id}` | - | `200` / **500** si inexistant |
| POST | `/v1/ecole/classe/assigneMatiere/store` | `classe_id*` exists:classes, `matiere_id*` array exists:matieres, `ecole_id*` exists:ecoles (idempotent) | `200 {data:ClasseMatiere[]}` / `422` validation |

```json
// POST /api/v1/ecole/classe/assigneMatiere/store
{ "classe_id": 1, "ecole_id": 1, "matiere_id": [1, 2] }
```

### 4.5 Espace ecole - Eleves ([auth] `ecole_api`)

### 4.6 Espace ecole - Enseignants ([auth] `ecole_api`)

| Methode | URL | Corps | Reponse |
|---|---|---|---|
| GET | `/v1/ecole/enseignant/index/{id}` | `{id}` = ecole_id | `200 {data:Enseignant[]}` avec `classe` + `date_creation:"JJ/MM/AAAA"` |
| POST | `/v1/ecole/enseignant/registerEnseignant` | `name*,prenom*,matricule*`, `numero` opt., `email*`, `ecole_id*`. Password **genere serveur**, envoye par mail | `200 {data,password:"<CLAIR_FICTIF>"}` - **afficher ce password une seule fois** / `400` |
| GET | `/v1/ecole/enseignant/edit/{id}` | - | `200 {data+roles}` ou `200 {status:"Echec"}` |
| PUT | `/v1/ecole/enseignant/update/{id}` | `name*,prenom*,matricule*,numero,email*,ecole_id*` (ni password ni classe) | `200 {data}` / validation en `200` / `400` si inconnu |
| PUT | `/v1/ecole/enseignant/update/assigne/classe/{id}` | `{id}` = enseignant, `classe_id*` exists:classes | `200 {data+classe}` / `400` |
| GET | `/v1/ecole/enseignant/getEnseignant` | - (charge le profil enseignant du token, voir section 10) | `200 {data,roles,nombreCours[],nombreQuiz[],nombreElevesConnectes}` (tableaux, pas compteurs) |
| DELETE | `/v1/ecole/enseignant/destroy/{id}` | - | `200` / **500 probable** si inexistant |
| POST | `/v1/ecole/enseignant/enseignantClasse/store` | `classe_id*,enseignant_id*,ecole_id*` (unique) | `200 {data}` / `400` / `409 {message:"Deja existant"}` |

### 4.7 Espace enseignant - profil et eleves ([auth] `enseignant_api`)

| Methode | URL | Corps | Reponse |
|---|---|---|---|
| GET | `/v1/enseignant/getEnseignant` | - | `200 {data:Enseignant+matiere+classe..., roles[], nombreCours[], nombreQuiz[], nombreElevesConnectes:int}` |
| GET | `/v1/enseignant/update/{id}` | **Anomalie** : GET vers `update()` qui exige un corps -> **A confirmer, ne pas utiliser** | validation en `200` |
| POST | `/v1/enseignant/change-password` | `current_password*`, `password*` min:8 + `password_confirmation*` | `200 {data}` / `400` validation ou actuel incorrect / `401` |

### 4.8 Espace enseignant - Cours ([auth] `enseignant_api`, `multipart/form-data` si fichiers)

Deux prefixes menent au meme controleur : `/v1/enseignant/cours` declare deux fois (voir section 10). `show` et `authorise_quiz` **uniquement** sur le 2e groupe. **Aucun `edit`** pour les cours.

| Methode | URL | Corps | Reponse |
|---|---|---|---|
| GET | `/v1/enseignant/cours/index` | - | `200 {data:Cours[]}` de **l'enseignant connecte uniquement**, avec `medias,quizzes` |
| POST | `/v1/enseignant/cours/store` | `title*` max:255, `description/contenu/resume` opt., `matiere_id*,classe_id*`, `is_published` opt., `date_programmation` opt. date, `listMateriels*[]+name*`, `listStrategies*[]+name*`, fichiers `medias_files[]+medias_types[]+medias_ordres[]` (section 8) | `201 {data+medias}` / `422` |
| GET | `/v1/enseignant/cours/show/{id}` | - | `200 {data+medias+retoursProjections}` / `404` si inconnu |
| POST | `/v1/enseignant/cours/update/{id}` | comme store **sans** listMateriels/Strategies + fichiers opt. (ajoutes) | `200 {data+medias}` / `422` / `404` |
| DELETE | `/v1/enseignant/cours/destroy/{id}` | - (supprime les fichiers) | `200` / `404` |
| POST | `/v1/enseignant/cours/authorise_quiz` | `enseignant_id*`, `cours_id*` -> **bascule** `quiz_authorise` | `200 {status:"success",quiz_authorise:bool}` / `422` / `404` |
| *media* | `/v1/enseignant/cours/media/...` | controleur **vide** | **Toujours 500. Ne pas appeler.** |

```http
POST /api/v1/enseignant/cours/store
Authorization: Bearer <TOKEN_FICTIF>
Content-Type: multipart/form-data

title: "Les fractions" / matiere_id: 1 / classe_id: 1 / is_published: true
listMateriels[0][name]: "Jetons" / listStrategies[0][name]: "Manipulation"
medias_files[0]: (fichier fictif) / medias_types[0]: image / medias_ordres[0]: 0
```

### 4.9 Espace enseignant - Quiz / Questions / Reponses / Notes ([auth] `enseignant_api`)

CRUD **sans scoping** (un enseignant voit les objets de tous - voir section 10).


### 4.10 Handicaps (lecture partagee, ecriture ecole)

| Methode | URL | Guard | Corps | Reponse |
|---|---|---|---|---|
| GET | `/v1/ecole/handicap/index` | `ecole_api` OU `enseignant_api` (syntaxe `auth:ecole_api,enseignant_api` - voir section 10) | - | `200 {data:Handicap[]}` |
| POST | `/v1/ecole/handicap/store` | `ecole_api` | `name*` | `200 {data}` / `400` |
| GET | `/v1/ecole/handicap/edit/{id}` | `ecole_api` | - | `200 {data,handicapeEleve}` ou `200 {status:"Echec"}` |
| POST | `/v1/ecole/handicap/update/{id}` | `ecole_api` | `name*` | `200` / `400` si inconnu |
| DELETE | `/v1/ecole/handicap/destroy/{id}` | `ecole_api` | - | `200` / **500** si inexistant |

### 4.11 Espace eleve ([auth] `eleve_api`)

| Methode | URL | Corps | Reponse |
|---|---|---|---|
| GET | `/v1/eleve/getEleve` | - | `200 {data:Eleve+handicap+classe...}` - **dashboard complet en un appel** |
| GET | `/v1/eleve/connexion` | - (bascule `is_connect`) | `200 {data}` (message identique dans les 2 sens) / `400` |
| POST | `/v1/eleve/logout` | - | `200` |
| POST | `/v1/eleve/note/store` | `note*` integer sans bornes, `eleve_id*` exists:eleves, `quiz_id*` exists:quizzes | `200 {data:Note}` / `400`. **Pas de controle eleve_id = connecte** (section 10) |
| POST | `/v1/eleve/retour-projection/store` | `apprentissage*,methode_apprentissage*,difficultes*,application_future*`, `eleve_id*`, `cours_id*` | `200 {data}` / `400`. **Pas de controle d'appartenance** |

```json
// POST /api/v1/eleve/note/store
{ "note": 14, "eleve_id": 3, "quiz_id": 1 }
// POST /api/v1/eleve/retour-projection/store
{ "apprentissage": "J'ai appris...", "methode_apprentissage": "...", "difficultes": "...", "application_future": "...", "eleve_id": 3, "cours_id": 1 }
```

### 4.12 Espace client - lecture ([auth] `client_api`)

| Methode | URL | Reponse (relations) |
|---|---|---|
| GET | `/v1/client/getClient` | `200 {data:Client}` (password masque) |
| GET | `/v1/client/ecoles` | `200 {data:Ecole[]}` brut |

### 4.13 Espace client - statistiques ([auth] `client_api`)

Seules routes **filtrables par query**. Reponse type : `200 {success:true,status:"success",message,filters:{...},data:{...}}`. Filtres communs (tous opt.) : `?ecole_id=&classe_id=&matiere_id=&handicap_id=` (int, `exists`, echec -> `422` Laravel).

| Methode | URL | Particularites |
|---|---|---|
| GET | `/v1/client/statistics/schema` | `{data:{disponibles:[...],indisponibles:[{repartition_sexe:false},{repartition_niveau:false}]}}` |
| GET | `/v1/client/statistics/overview` | Totaux globaux + taux et moyenne (**A confirmer** : cles exactes) |
| GET | `/v1/client/statistics/eleves` | Par classe / etablissement / handicap (+ taux connectes) |
| GET | `/v1/client/statistics/enseignants` | Par etablissement, avec/sans classe, total cours/quiz |
| GET | `/v1/client/statistics/quiz` | totaux quiz/questions/reponses, moyenne notes, min/max |
| GET | `/v1/client/statistics/handicaps` | `total`, `repartition[{id,name,total,pourcentage}]` |
| GET | `/v1/client/statistics/matieres` | `repartition[{id,name,ecole_id,total_cours,total_classes,pourcentage_cours}]` |
| GET | `/v1/client/statistics/cours` | publies, programmes, quiz autorises, avec medias/quiz |
| GET | `/v1/client/statistics/etablissements/{id}` | Fiche `{ecole,totaux}` ; `404` si inconnu (**A confirmer**) |
| GET | `/v1/client/eleves` | `?ecole_id&classe_id&handicap_id&per_page` (1-200, defaut 50) -> `200 {data:Eleve[] (sans code/numeroParent),meta:{current_page,per_page,total,last_page}}`. **Seule pagination du projet** : `?page=N&per_page=...` |

```http
GET /api/v1/client/statistics/eleves?ecole_id=1&classe_id=2
Authorization: Bearer <TOKEN_FICTIF>
Accept: application/json
```

### 4.14 Routes utilitaires / hors perimetre

| Methode | URL | Acces | Usage front |
|---|---|---|---|
| GET | `/user` | `auth:api` (users) | **Inutilisable** (aucun token distribue). **A confirmer** |
| GET | `/up` | public | Health-check Laravel |
| GET | `/` (web) | public | Vue `welcome` (pas d'API) |
| GET | `/storage/{path}` | public | Lecture des medias locaux |

## 5. Modeles de donnees exposes

`id` = int auto-increment, `created_at/updated_at` = datetime ISO (sauf mention).

| Modele (table) | Champs | Relations / valeurs |
|---|---|---|
| Ecole (`ecoles`) | `id, name, numero nullable, email unique, password masque` | `classes[], enseignants[], matieres[], eleves[]` ; `eleves_handicap_count` (getEcole) |
| Classe (`classes`) | `id, name, ecole_id` | `ecole, eleves[], enseignant` (via enseignants.classe_id), `matieres[]` (pivot) |
| Matiere (`matieres`) | `id, name, ecole_id` | `ecole, cours[], classes[]` ; `Enseignant.matiere` **toujours null** (aucune route n'ecrit matiere_id, section 10) |
| Enseignant (`enseignants`) | `id, name, prenom, matricule, numero` (migration int vs validation string), `classe_id` nullable, `ecole_id`, `email` unique nullable | `ecole, classe, cours[], quizzes[], matiere(null)` ; `date_creation:"JJ/MM/AAAA"` sur index |
| Eleve (`eleves`) | `id, name, prenom, code` (migration integer vs string max:6), `dateOfNaissance` libre, `is_connect` bool defaut false, `handicap_id, classe_id, numeroParent` | `classe, handicap, notes[], retoursProjections[]` |
| Handicap (`handicaps`) | `id, name` | `eleves[]` |
| Cours (`cours`) | `id, title, description nullable, contenu/resume text nullable, date_programmation nullable, enseignant_id, classe_id, matiere_id, is_published/quiz_authorise bool defaut false` | `enseignant, classe, matiere, medias[]` (tries ordre), `quizzes[], materiels[], strategies[], retoursProjections[]` |
| CoursMedias (`cours_medias`) | `id, cours_id, type` **enum video/image/audio**, `url` (700), `path` nullable, `ordre` defaut 0 | `cours` |
| Quiz (`quizzes`) | `id, name, cours_id, enseignant_id` | `cours, questions[], enseignant` |

## 6. Format standard des reponses et des erreurs

### 6.1 Succes - enveloppe habituelle

```json
{ "status": "success", "message": "Liste des cours", "data": {} }
```

Variantes lues : `"success"/"Success"`, `"Echec"/"Echoue"/"error"/"errors"/"echec"` (casse instable - **comparer en insensible a la casse**). Cles supplementaires selon la route : `access_token`, `refresh_token`, `roles[]`, `nombreCours[]`, `nombreQuiz[]`, `nombreElevesConnectes`, `classeEleves`, `matiereCours`, `questionReponse`, `quiz_authorise`, `password`. Stats : `{success:true,status,message,filters,data,meta?}`. Creations : `200` le plus souvent, `201` pour cours/store et eleve/store enseignant.

### 6.2 Validation - trois formats coexistent

```json
// Cas A (majoritaire, 400) : "message" contient les erreurs
{ "status": "Echec", "message": { "name": ["The name field is required."] } }
// Cas B (avec "errors", 400 ou 422) :
{ "status": "error", "errors": { "title": ["..."] }, "message": "Validation echouee" }
// Cas C (filtres stats, 422 Laravel natif) :
{ "message": "The ecole id field must be an integer.", "errors": { "ecole_id": ["..."] } }
```

**Conduite front :** chercher dans `message` **puis** `errors` (objet `{champ:[...]}` ou chaine). Les `update` (classe/matiere/enseignant/eleve/quiz/question/reponse) renvoient la validation en **HTTP 200** - detecter via `status`. `422` uniquement pour cours, authorise_quiz, assigneMatiere et filtres stats.

### 6.3 Codes d'erreur

| Code | Sens ici | Exemple |
|---|---|---|
| 400 | Validation (cas A/B) OU metier (login, id inconnu, sans classe, code) | `{message:"Email ou mot de passe incorrect"}` |
| 401 | Bearer absent/invalide/mauvais guard | `{"message":"Unauthenticated."}` |
| 403 | Eleve d'une autre classe (update/destroy enseignant) | `{status:"Echec",message:"Cet eleve..."}` |
| 404 | `findOrFail` (cours show/update/destroy) | `{"message":"No query results..."}` |
| 409 | Doublon : pivot enseignant-classe, `code` eleve enseignant | voir 4.6/4.7 |
| 422 | Validation cours / assignation / filtres stats | voir 6.2 |
| 500 | `delete()` sur `null`, routes medias vides, echec FK, video sans ffmpeg | corps **A confirmer** |


### 6.4 Pagination

**Une seule route paginee** : `GET /v1/client/eleves` (`meta:{current_page,per_page,total,last_page}`, per_page 1-200 defaut 50). Tout le reste retourne des tableaux complets - paginer **cote front** (getEnseignant/getEleve peuvent etre tres lourds).

## 7. Roles et permissions

| Role (guard) | Peut faire | Ne peut pas faire |
|---|---|---|
| Ecole (`ecole_api`) | Profil ; CRUD classes, matieres, handicaps ; assigner matieres ; gerer eleves (tous) et enseignants (ecole_id passe en path/corps - **sans verification d'appartenance**, section 10) ; lier enseignant-classe | Voir cours/quiz/notes ; changer un password |
| Enseignant (`enseignant_api`) | Profil complet ; CRUD cours (index filtres, mais show/update/destroy **sans controle** - section 10) ; CRUD quiz/questions/reponses (sans scoping) ; authorise_quiz ; eleves **de sa classe uniquement** (403 sinon) ; notes/eleves/retours ; change-password | Autres classes ; ecrire handicaps ; espaces ecole/client/eleve |
| Eleve (`eleve_api`) | getEleve, connexion (toggle), logout, note/store, retour-projection/store (**sans controle**, section 10) | Tout le reste |
| Client (`client_api`) | Lecture globale + 9 stats + logout | Toute ecriture |

Aucune permission Spatie fine : le middleware de guard fait foi. Mauvais guard = `401`.

| Question (`questions`) | `id, question, quiz_id` | `reponses[], quiz` |
| Reponse (`reponses`) | `id, name, status` (libre ex. `"correct"` - **A confirmer**), `question_id` | `question` |
| Note (`notes`) | `id, note` integer sans bornes, `eleve_id, quiz_id` | `eleve, quiz` (**A confirmer** : non chargees) |
| RetourProjection (`retour_projection`) | `id, apprentissage, methode_apprentissage, difficultes, application_future, cours_id, eleve_id` | `cours, eleve` |
| Materiels / Strategies | `id, name, cours_id` | `cours`. **Creation uniquement avec le cours** ; aucun CRUD propre |
| Client (`clients`) | `id, name, numero nullable, email unique` | aucun lien metier (lecture globale) |
| ClasseMatiere (`classe_matieres`) | `id, classe_id, matiere_id, ecole_id`, unique trio | pivot |
| classe_enseignant (`classe_enseignants`) | `id, classe_id, enseignant_id, ecole_id`, unique trio | pivot **redondant** avec enseignants.classe_id (section 10) |

Roles (Spatie) : `ecole/ecole_api`, `enseignant/enseignant_api`, `eleve/eleve_api`, `client/client_api`. Aucune permission fine - le role = le guard.

| GET | `/v1/client/classes` | `200 {data}` avec `ecole,eleves,enseignant,matieres` |
| GET | `/v1/client/matieres` | `200 {data}` avec `ecole,cours` |
| GET | `/v1/client/enseignants` | `200 {data}` avec `ecole,classe` |
| GET | `/v1/client/cours` | `200 {data}` avec `medias,matiere,classe,enseignant` |
| GET | `/v1/client/quizzes` | `200 {data}` avec `cours,questions.reponses` |
| GET | `/v1/client/handicaps` | `200 {data:Handicap[]}` |
| GET | `/v1/client/eleves` | voir 4.13 (filtres + meta) |

**Quiz** `/v1/quiz/{index,store,edit/{id},update/{id},destroy/{id}}` : store/update exigent `name*`, `cours_id*` exists:cours, `enseignant_id*` exists:enseignants. `index` : `200 {data:Quiz[]}` sans relations. Validation `400`, update inconnu `400`, destroy `200` meme si inexistant.

**Questions** `/v1/question/...` : store `question*` max:1000, `quiz_id*` exists:quizzes. `edit` ajoute `questionReponse`. Attention : `update` lit `$request->name` au lieu de `question` -> **texte efface, A confirmer, eviter d'appeler**.

**Reponses** `/v1/reponse/...` : store en lot `listReponse*[]+name*+status*` (libre, ex. `"correct"` - **A confirmer**), `question_id*` exists:questions -> `200 {data:listReponse}` (echo). `update` : regle malformee -> **toujours rejetee, A confirmer, eviter**.

```json
// POST /api/v1/reponse/store
{ "question_id": 1, "listReponse": [{ "name": "2/4", "status": "correct" }] }
```

**Notes (lecture)** : `GET /api/indexNote` (sans `/v1`) -> `200 {data:Note[]}`. `GET /api/indexEleve` -> eleves avec `classe,handicap`. Pas de edit/update/destroy routes pour les notes.

| DELETE | `/v1/enseignant/logout` | - | `200` |
| POST | `/v1/enseignant/classe/eleve/store` | `name*,prenom*,numeroParent*,handicap_id*,dateOfNaissance*,code*` max:6. `classe_id` deduit du token (ignore si envoye) | `201` / `401` / `400` sans classe / `400` validation / `409` code doublon |
| POST | `/v1/enseignant/classe/eleve/update/{id}` | memes champs, `code` opt. (vide = conserve) | `200 {data}` / `401` / `400` / `403` autre classe / `409` doublon |
| DELETE | `/v1/enseignant/classe/eleve/destroy/{id}` | - | `200` (cascade notes+retours) / `401` / `400` / `403` / `500` FK |


| Methode | URL | Corps | Reponse |
|---|---|---|---|
| GET | `/v1/ecole/eleve/index` | - | `200 {data:Eleve[]}` avec `classe,handicap` - **tous les eleves, sans filtre ecole** |
| POST | `/v1/ecole/eleve/registerEleve` | `name*,prenom*,numeroParent*`, `handicap_id*` exists:handicaps, `dateOfNaissance*` string libre, `classe_id*` exists:classes, `code*` max:6 | `200 {data:Eleve}` / `400`. **Pas d'unicite du `code`** (voir section 10) |
| POST | `/v1/ecole/eleve/edit/{id}` | - (verbe POST, pas GET) | `200 {data}` ou `200 {status:"Echec"}` |
| POST | `/v1/ecole/eleve/update/{id}` | `name*,prenom*,numeroParent*,handicap_id*,dateOfNaissance*,classe_id*` (**sans `code`**) | `200` / validation en `200` / `400` si inconnu |
| DELETE | `/v1/ecole/eleve/destroy/{id}` | - | **A confirmer** : methode `destroy` absente du controleur lu |

| Methode | URL | Corps (validation) | Reponse 200 | Erreurs |
|---|---|---|---|---|
| POST | `/v1/ecole/register` | `name*` max:255, `email*` email unique:`users` (!), `numero*` string, `password*` min:4 | `{status:success,data:Ecole}` **sans token** | `400` validation |
| POST | `/v1/ecole/login` | `email*`, `password*` | `{status,data,access_token,refresh_token}` | `400` validation / compte absent / mauvais password |
| POST | `/v1/enseignant/register` | `name*,prenom*,matricule*`, `numero` opt., `email*`, `ecole_id*` exists:ecoles | `{status,data}` **sans password ni token** | `400` validation |
| POST | `/v1/enseignant/login` | `email*` exists:enseignants, `password*` | `{status,data,access_token,refresh_token}` | `400` |
| POST | `/v1/eleve/login` | `code*` string | `{status,data,access_token,refresh_token}` | `400` / `400 "Aucun eleve..."` / `400 "code incorrect"` |
| POST | `/v1/client/register` | `name*`, `email*` unique:clients, `numero` opt., `password*` min:4 | `{status,data}` sans token | `400` validation |
| POST | `/v1/client/login` | `email*`, `password*` | `{status,data,access_token,refresh_token}` | `400` |

Logouts ([auth]) : `DELETE /v1/ecole/logout` (ecole_api), `DELETE /v1/enseignant/logout` (enseignant_api), `POST /v1/eleve/logout` (eleve_api), `DELETE /v1/client/logout` (client_api). Succes `200`, erreur `401`.

## 8. Upload de fichiers et medias

**Cloudinary : ne pas utiliser.** Package + config presents mais aucun appel dans le code. Medias sur **disque local `public`** (`storage/app/public/cours/{images,videos,audios}/`), URL `Storage::url()` (ex. fictif `/storage/cours/images/550e8400-....jpg`) - penser a `php artisan storage:link`.

### 8.1 Champs multipart (`POST /v1/enseignant/cours/store|update/{id}`)

| Champ | Type | Regles lues |
|---|---|---|
| `medias_files[]` | file[] | `mimes:jpg,jpeg,png,gif,webp,mp4,mov,avi,webm,mkv,mp3,wav,ogg,m4a,aac,flac`, `max:102400` (env. **100 Mo par fichier**, `max` = kilo-octets) |
| `medias_types[]` | string[] | `in:image,video,audio` (defaut `image` si absent - toujours envoyer le type reel) |
| `medias_ordres[]` | int[] | ordre d'affichage (defaut = index) |

Melanger champs JSON + fichiers dans le **meme** `multipart/form-data` (`listMateriels[i][name]`, `listStrategies[i][name]`). `update` : fichiers **ajoutes**, jamais remplaces ; aucune suppression unitaire routee (`destroyMedia` existe mais **sans route**).

### 8.2 Traitement serveur (impacts front)

- Image : max 1280 px + convertie **JPEG q80**, extension forcee `.jpg`.
- Video : `mp4` copie tel quel ; autres formats convertis via `ffmpeg` (libx264+aac). **Sans ffmpeg : media ignore silencieusement** (cours cree `201` sans lui). Prevoir timeout long + progression.
- Audio : stocke tel quel.
- Reponse : `data.medias[] = {id, cours_id, type, url, path, ordre}`. Enregistrer `url` ; `path` est interne.
- Echec validation fichier -> `422` (cas B, voir 6.2).

## 9. CORS, limites de requetes, versioning et contraintes front

## 10. Ecarts et points a confirmer

1. **Routes medias mortes** : `v1/enseignant/cours/media/*` -> controleur vide = **500 garanti**. Upload reel via cours/store|update. **Confirmer** : retirer ou implementer ?
2. **`destroyMedia` sans route** : pas de suppression unitaire. **Confirmer** le besoin front.
3. **Doublon routes cours** : deux groupes `v1/enseignant/cours` ; show/authorise_quiz seulement dans le 2e. **Confirmer** le nettoyage.
4. **`GET /v1/enseignant/update/{id}`** appelle update() (validation) - inutilisable. **Confirmer** sa suppression.
5. **Question update** lit `$request->name` au lieu de `question` -> texte efface. **Confirmer** le correctif avant usage.
6. **Reponse update** : regle `question_id` malformee -> rejet systematique. **Confirmer**.
7. **Destructions fragiles** : Classe/Matiere/Handicap/Enseignant `destroy` sans null-check -> **500** si inexistant. Quiz/Question/Reponse testent `if ($id)` -> **200 meme si inexistant**. **Confirmer** le contrat (404 vs 200).
8. **Eleve destroy (ecole)** absent du controleur lu alors que la route existe -> **A confirmer**.
9. **Absence de scoping** : quiz/question/reponse, cours show/update/destroy, retour index `{id}` libre, note/store et retour/store (`eleve_id` libre), eleve/index ecole, /indexNote, /indexEleve, ecole/index, getEnseignant ecole. Le front **ne doit pas s'appuyer** dessus pour isoler les donnees. **Confirmer** les regles metier.
10. **`auth:ecole_api,enseignant_api`** sur handicap/index : syntaxe non standard. **Confirmer** quel token passe.
11. **Types divergents** : enseignants.numero (migration int vs string), eleves.code (integer vs string max:6), registerEcole.email unique sur **`users`** au lieu d'ecoles. **Confirmer**.
12. **registerEnseignant public** sans classe/token/password renvoye (mail en log). Preferer la route authentifiee. **Confirmer** si la route publique reste ouverte.
13. **Enseignant.matiere_id** jamais ecrit -> toujours null. **Confirmer** (champ mort ?).
14. **Double rattachement classe** : enseignants.classe_id (utilise) vs pivot classe_enseignants (jamais lu). **Confirmer** la source de verite.
15. **numeroParent** expose cote ecole/enseignant mais masque cote client - incoherence **a confirmer**.
16. **is_published / date_programmation** ecrits mais jamais filtres en lecture. **Confirmer** l'usage attendu.
17. **reponses.status** libre (pas d'enum) ; **notes.note** sans bornes ; **thumbnail** : exposition JSON **A confirmer**.
18. **Cles de statistics/overview** et **404 etablissements/{id}** : **A confirmer** par appel reel.
19. **Refresh token** : second access-token, pas de grant OAuth ; duree de vie **A confirmer** (defaut Passport 13).
20. **BDD prod, SMTP, ffmpeg, storage:link, passport:keys** : **A confirmer** avec l'ops (exemple = sqlite + log).
21. **GET /user** (guard users) : hors perimetre, **a confirmer** qu'on l'ignore.
22. **Ancien README** : conserve perimetre multi-acteurs + `php artisan serve` ; le reste est remplace par ce document.

---

*Fin du document - genere le 20/10/2026 a partir du code de `classeInclusiveBackend`. Valeurs fictives, aucun secret reel.*


- **CORS** (`config/cors.php`) : `paths: api/*`, `origins: *`, `methods/headers: *`, `supports_credentials: false`. Ne pas envoyer de cookies (`withCredentials=false`) - seul le Bearer compte.
- **Rate limiting : aucun** (`throttle` absent). Le front s'auto-limite (eviter rafales sur getEnseignant/getEleve/stats).
- **Versioning** : prefixe `/v1` fige (sauf `/indexNote`, `/indexEleve` a la racine `/api`, et `/user`, `/up`). Aucune `/v2`. Coder la base `.../api/v1` en constante + exceptions pour les 2 routes racine.
- **Verbes** : update classe/enseignant = `PUT` ; update matiere/handicap/quiz/question/reponse/cours/eleves = `POST` ; logouts ecole/enseignant/client = `DELETE`, eleve = `POST`. `edit` = `GET` sauf eleves ecole (`POST /edit/{id}`). Mauvais verbe -> `405`.
- **En-tetes** : toujours `Accept: application/json` (sinon 401/404/405 en HTML, et redirection vers `/`). `Content-Type: application/json` (ou multipart pour les cours).
- **IDs** : ids de path en int ; `code` eleve en **chaine** (max 6) ; dates en chaines (ex. fictifs `"2015-03-15"`, `"2026-11-02 08:00:00"`).
- **Passwords** : ecole min:4, client min:4, enseignant self-service min:8 + confirmation. Jamais renvoyes sauf registerEnseignant ecole (clair, a afficher une fois).
- **Securite front** : stocker chaque token par espace separement ; purger au logout ; jamais en query ; masquer password/code dans les logs.
