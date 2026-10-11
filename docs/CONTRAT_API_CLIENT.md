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
