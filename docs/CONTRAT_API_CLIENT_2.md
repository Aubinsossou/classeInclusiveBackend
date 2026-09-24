
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
