
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
