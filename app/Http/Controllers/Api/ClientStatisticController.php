<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Classe;
use App\Models\Cours;
use App\Models\CoursMedias;
use App\Models\Ecole;
use App\Models\Eleve;
use App\Models\Enseignant;
use App\Models\Handicap;
use App\Models\Matiere;
use App\Models\Note;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Reponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class ClientStatisticController extends Controller
{
    protected function pct($part, $total): float
    {
        if ((float) $total <= 0) return 0.0;
        return round(((float) $part / (float) $total) * 100, 2);
    }

    protected function filters(Request $request): array
    {
        $v = $request->validate([
            'ecole_id' => 'nullable|integer|exists:ecoles,id',
            'classe_id' => 'nullable|integer|exists:classes,id',
            'matiere_id' => 'nullable|integer|exists:matieres,id',
            'handicap_id' => 'nullable|integer|exists:handicaps,id',
        ]);
        return [
            'ecole_id' => $v['ecole_id'] ?? null,
            'classe_id' => $v['classe_id'] ?? null,
            'matiere_id' => $v['matiere_id'] ?? null,
            'handicap_id' => $v['handicap_id'] ?? null,
        ];
    }

    protected function ok(string $message, mixed $data, array $filters = [])
    {
        return response()->json([
            'success' => true,
            'status' => 'success',
            'message' => $message,
            'filters' => $filters,
            'data' => $data,
        ], 200);
    }

    protected function classeIds(array $f): ?array
    {
        if (!$f['ecole_id'] && !$f['classe_id']) return null;
        $q = Classe::query();
        if ($f['ecole_id']) $q->where('ecole_id', $f['ecole_id']);
        if ($f['classe_id']) $q->where('id', $f['classe_id']);
        return $q->pluck('id')->all();
    }

    protected function enseignantIds(array $f, ?array $classeIds): ?array
    {
        if (!$f['ecole_id'] && is_null($classeIds)) return null;
        $q = Enseignant::query();
        if ($f['ecole_id']) $q->where('ecole_id', $f['ecole_id']);
        if (!is_null($classeIds)) $q->whereIn('classe_id', $classeIds);
        return $q->pluck('id')->all();
    }

    protected function coursQuery(array $f, ?array $classeIds, ?array $enseignantIds)
    {
        $q = Cours::query();
        if (!is_null($classeIds)) $q->whereIn('classe_id', $classeIds);
        if (!is_null($enseignantIds)) $q->whereIn('enseignant_id', $enseignantIds);
        if ($f['matiere_id']) $q->where('matiere_id', $f['matiere_id']);
        if ($f['ecole_id'] && is_null($classeIds) && is_null($enseignantIds)) {
            $q->whereHas('enseignant', fn ($e) => $e->where('ecole_id', $f['ecole_id']));
        }
        return $q;
    }

    protected function buildTotaux(array $f): array
    {
        $classeIds = $this->classeIds($f);
        $enseignantIds = $this->enseignantIds($f, $classeIds);
        $eleveBase = Eleve::query()
            ->when(!is_null($classeIds), fn ($q) => $q->whereIn('classe_id', $classeIds))
            ->when($f['handicap_id'], fn ($q) => $q->where('handicap_id', $f['handicap_id']));
        $coursBase = $this->coursQuery($f, $classeIds, $enseignantIds);
        $coursIds = (clone $coursBase)->pluck('id')->all();
        $hasScope = $f['ecole_id'] || $f['classe_id'] || $f['matiere_id'] || !is_null($enseignantIds) || !is_null($classeIds);
        $quizBase = Quiz::query();
        if (!empty($coursIds)) $quizBase->whereIn('cours_id', $coursIds);
        elseif ($hasScope) $quizBase->whereRaw('1 = 0');
        $quizIds = (clone $quizBase)->pluck('id')->all();
        $questionIds = empty($quizIds) ? [] : Question::whereIn('quiz_id', $quizIds)->pluck('id')->all();
        return [
            'classeIds' => $classeIds, 'enseignantIds' => $enseignantIds,
            'coursIds' => $coursIds, 'quizIds' => $quizIds, 'questionIds' => $questionIds,
            'total_etablissements' => $f['ecole_id'] ? 1 : Ecole::count(),
            'total_classes' => is_null($classeIds) ? Classe::count() : count($classeIds),
            'total_enseignants' => is_null($enseignantIds) ? Enseignant::count() : count($enseignantIds),
            'total_eleves' => (clone $eleveBase)->count(),
            'total_matieres' => Matiere::when($f['ecole_id'], fn ($q) => $q->where('ecole_id', $f['ecole_id']))
                ->when($f['matiere_id'], fn ($q) => $q->where('id', $f['matiere_id']))->count(),
            'total_cours' => (clone $coursBase)->count(),
            'total_quiz' => (clone $quizBase)->count(),
        ];
    }

    protected function repClasse(array $f, int $totalEleves): array
    {
        return Classe::with('ecole:id,name')
            ->when($f['ecole_id'], fn ($q) => $q->where('ecole_id', $f['ecole_id']))
            ->when($f['classe_id'], fn ($q) => $q->where('id', $f['classe_id']))
            ->withCount(['eleves' => fn ($q) => $f['handicap_id'] ? $q->where('handicap_id', $f['handicap_id']) : $q])
            ->orderBy('name')->get()
            ->map(fn ($c) => [
                'id' => $c->id, 'name' => $c->name, 'ecole_id' => $c->ecole_id,
                'ecole_name' => $c->ecole->name ?? null,
                'total_eleves' => (int) $c->eleves_count,
                'pourcentage' => $this->pct($c->eleves_count, $totalEleves),
            ])->all();
    }

    protected function repEcole(array $f): array
    {
        return Ecole::when($f['ecole_id'], fn ($q) => $q->where('id', $f['ecole_id']))
            ->orderBy('name')->get()
            ->map(function ($e) use ($f) {
                $cIds = Classe::where('ecole_id', $e->id)->pluck('id')->all();
                $eIds = Enseignant::where('ecole_id', $e->id)->pluck('id')->all();
                $eleves = empty($cIds) ? 0 : Eleve::whereIn('classe_id', $cIds)
                    ->when($f['handicap_id'], fn ($q) => $q->where('handicap_id', $f['handicap_id']))->count();
                $coursQ = ($f['classe_id'] && !in_array($f['classe_id'], $cIds)) ? null : Cours::where(
                    fn ($q) => $q->whereIn('enseignant_id', empty($eIds) ? [-1] : $eIds)
                        ->orWhereIn('classe_id', empty($cIds) ? [-1] : $cIds)
                )->when($f['matiere_id'], fn ($q) => $q->where('matiere_id', $f['matiere_id']));
                $coursIds = is_null($coursQ) ? [] : (clone $coursQ)->pluck('id')->all();
                $matieres = Matiere::where('ecole_id', $e->id)
                    ->when($f['matiere_id'], fn ($q) => $q->where('id', $f['matiere_id']))->count();
                $totalQuiz = empty($coursIds) ? 0 : Quiz::whereIn('cours_id', $coursIds)->count();
                return [
                    'id' => $e->id, 'ecole_id' => $e->id,
                    'name' => $e->name, 'nom' => $e->name,
                    'total_classes' => count($cIds), 'total_enseignants' => count($eIds),
                    'total_eleves' => $eleves, 'total_cours' => count($coursIds),
                    'total_matieres' => $matieres, 'total_quiz' => $totalQuiz,
                ];
            })->all();
    }

    protected function repHandicap(array $f, ?array $classeIds, int $totalEleves): array
    {
        return Handicap::when($f['handicap_id'], fn ($q) => $q->where('id', $f['handicap_id']))
            ->orderBy('name')->get()
            ->map(function ($h) use ($classeIds, $totalEleves) {
                $c = Eleve::where('handicap_id', $h->id)
                    ->when(!is_null($classeIds), fn ($q) => $q->whereIn('classe_id', $classeIds))->count();
                return ['id' => $h->id, 'name' => $h->name, 'total_eleves' => $c, 'pourcentage' => $this->pct($c, $totalEleves)];
            })->all();
    }

    protected function overviewData(array $f): array
    {
        $t = $this->buildTotaux($f);
        $totalEleves = $t['total_eleves'];
        $totalCours = $t['total_cours'];
        $totalQuiz = $t['total_quiz'];
        $coursBase = $this->coursQuery($f, $t['classeIds'], $t['enseignantIds']);
        $publies = (clone $coursBase)->where('is_published', true)->count();
        $qa = (clone $coursBase)->where('quiz_authorise', true)->count();
        $avecMedias = (clone $coursBase)->whereHas('medias')->count();
        $mediaScope = CoursMedias::when(!empty($t['coursIds']), fn ($q) => $q->whereIn('cours_id', $t['coursIds']))
            ->when(empty($t['coursIds']) && ($f['ecole_id'] || $f['classe_id'] || $f['matiere_id'] || !is_null($t['enseignantIds'])), fn ($q) => $q->whereRaw('1 = 0'));
        $totalMedias = (clone $mediaScope)->count();
        $parType = (clone $mediaScope)->selectRaw('type, COUNT(*) as total')->groupBy('type')->pluck('total', 'type')->all();
        $hasQuizScope = $f['ecole_id'] || $f['classe_id'] || $f['matiere_id'] || !is_null($t['enseignantIds']) || !is_null($t['classeIds']);
        $totalQuestions = empty($t['quizIds']) ? 0 : Question::whereIn('quiz_id', $t['quizIds'])->count();
        $totalReponses = empty($t['questionIds']) ? 0 : Reponse::whereIn('question_id', $t['questionIds'])->count();
        $notesBase = Note::when(!empty($t['quizIds']), fn ($q) => $q->whereIn('quiz_id', $t['quizIds']))
            ->when(empty($t['quizIds']) && $hasQuizScope, fn ($q) => $q->whereRaw('1 = 0'));
        $totalNotes = (clone $notesBase)->count();
        $ensScope = Enseignant::when($f['ecole_id'], fn ($q) => $q->where('ecole_id', $f['ecole_id']))
            ->when($f['classe_id'], fn ($q) => $q->where('classe_id', $f['classe_id']));
        $avecClasse = (clone $ensScope)->whereNotNull('classe_id')->count();
        $sansClasse = (clone $ensScope)->whereNull('classe_id')->count();
        $data = [
            'total_etablissements' => $t['total_etablissements'],
            'total_classes' => $t['total_classes'],
            'total_enseignants' => $t['total_enseignants'],
            'total_eleves' => $totalEleves,
            'total_matieres' => $t['total_matieres'],
            'total_cours' => $totalCours,
            'total_cours_publies' => $publies,
            'total_cours_non_publies' => $totalCours - $publies,
            'total_cours_publies_pourcentage' => $this->pct($publies, $totalCours),
            'total_quiz' => $totalQuiz,
            'total_questions' => $totalQuestions,
            'total_reponses' => $totalReponses,
            'total_notes' => $totalNotes,
            'total_handicaps' => Handicap::when($f['handicap_id'], fn ($q) => $q->where('id', $f['handicap_id']))->count(),
            'total_medias' => $totalMedias,
            'repartition_sexe' => [
                'disponible' => false,
                'raison' => "Aucune colonne sexe/genre dans eleves et enseignants.",
                'donnees_manquantes' => ['eleves.sexe', 'enseignants.sexe'],
                'masculin' => null, 'feminin' => null,
                'masculin_pourcentage' => null, 'feminin_pourcentage' => null,
            ],
            'repartition_niveau' => [
                'disponible' => false,
                'raison' => "Aucune colonne niveau/cycle dans classes (seul champ : name).",
                'donnees_manquantes' => ['classes.niveau'],
            ],
            'repartition_par_classe' => $this->repClasse($f, $totalEleves),
            'repartition_par_etablissement' => $this->repEcole($f),
            'repartition_par_handicap' => $this->repHandicap($f, $t['classeIds'], $totalEleves),
            'enseignants' => [
                'total' => $t['total_enseignants'], 'avec_classe' => $avecClasse, 'sans_classe' => $sansClasse,
                'avec_classe_pourcentage' => $this->pct($avecClasse, $t['total_enseignants']),
                'sans_classe_pourcentage' => $this->pct($sansClasse, $t['total_enseignants']),
                'cours_moyen_par_enseignant' => $t['total_enseignants'] > 0 ? round($totalCours / $t['total_enseignants'], 2) : 0,
                'quiz_moyen_par_enseignant' => $t['total_enseignants'] > 0 ? round($totalQuiz / $t['total_enseignants'], 2) : 0,
            ],
            'cours' => [
                'total' => $totalCours, 'publies' => $publies, 'non_publies' => $totalCours - $publies,
                'publies_pourcentage' => $this->pct($publies, $totalCours),
                'quiz_authorise' => $qa, 'quiz_authorise_pourcentage' => $this->pct($qa, $totalCours),
                'avec_medias' => $avecMedias, 'avec_medias_pourcentage' => $this->pct($avecMedias, $totalCours),
                'medias_par_type' => ['video' => (int) ($parType['video'] ?? 0), 'image' => (int) ($parType['image'] ?? 0), 'audio' => (int) ($parType['audio'] ?? 0)],
            ],
            'quiz' => [
                'total' => $totalQuiz, 'total_questions' => $totalQuestions, 'total_reponses' => $totalReponses,
                'moyenne_questions_par_quiz' => $totalQuiz > 0 ? round($totalQuestions / $totalQuiz, 2) : 0,
                'moyenne_reponses_par_question' => $totalQuestions > 0 ? round($totalReponses / $totalQuestions, 2) : 0,
                'total_notes' => $totalNotes,
                'moyenne_notes' => $totalNotes > 0 ? round((clone $notesBase)->avg('note'), 2) : 0,
                'note_min' => $totalNotes > 0 ? (clone $notesBase)->min('note') : 0,
                'note_max' => $totalNotes > 0 ? (clone $notesBase)->max('note') : 0,
            ],
        ];
        return $data;
    }

    public function overview(Request $request)
    {
        $f = $this->filters($request);
        return $this->ok('Statistiques globales du tableau de bord.', $this->overviewData($f), $f);
    }

    public function etablissement(Request $request, string $id)
    {
        $v = Validator::make(['ecole_id' => $id], ['ecole_id' => 'required|integer|exists:ecoles,id']);
        if ($v->fails()) {
            return response()->json([
                'success' => false, 'status' => 'error',
                'message' => "Etablissement introuvable (id : {$id}).",
                'errors' => $v->errors(),
            ], 422);
        }
        $f = array_merge($this->filters($request), ['ecole_id' => (int) $id]);
        $ecole = Ecole::findOrFail((int) $id);
        $data = array_merge(
            ['etablissement' => ['ecole_id' => $ecole->id, 'nom' => $ecole->name, 'numero' => $ecole->numero, 'email' => $ecole->email]],
            $this->overviewData($f)
        );
        return $this->ok("Statistiques de l'établissement {$ecole->name}.", $data, $f);
    }

    public function eleves(Request $request)
    {
        $f = $this->filters($request);
        $cIds = $this->classeIds($f);
        $total = Eleve::when(!is_null($cIds), fn ($q) => $q->whereIn('classe_id', $cIds))
            ->when($f['handicap_id'], fn ($q) => $q->where('handicap_id', $f['handicap_id']))->count();
        return $this->ok('Statistiques des eleves.', [
            'total_eleves' => $total,
            'repartition_sexe' => ['disponible' => false, 'raison' => "Colonne sexe absente de eleves.", 'donnees_manquantes' => ['eleves.sexe']],
            'repartition_par_classe' => $this->repClasse($f, $total),
            'repartition_par_etablissement' => $this->repEcole($f),
            'repartition_par_handicap' => $this->repHandicap($f, $cIds, $total),
        ], $f);
    }

    public function enseignants(Request $request)
    {
        $f = $this->filters($request);
        $scope = Enseignant::when($f['ecole_id'], fn ($q) => $q->where('ecole_id', $f['ecole_id']))
            ->when($f['classe_id'], fn ($q) => $q->where('classe_id', $f['classe_id']));
        $total = (clone $scope)->count();
        $avec = (clone $scope)->whereNotNull('classe_id')->count();
        $sans = (clone $scope)->whereNull('classe_id')->count();
        $parEcole = Ecole::when($f['ecole_id'], fn ($q) => $q->where('id', $f['ecole_id']))->get()
            ->map(fn ($e) => ['id' => $e->id, 'name' => $e->name, 'total_enseignants' => Enseignant::where('ecole_id', $e->id)->count()])->all();
        return $this->ok('Statistiques des enseignants.', [
            'total_enseignants' => $total, 'avec_classe' => $avec, 'sans_classe' => $sans,
            'avec_classe_pourcentage' => $this->pct($avec, $total),
            'sans_classe_pourcentage' => $this->pct($sans, $total),
            'par_etablissement' => $parEcole,
        ], $f);
    }

    public function cours(Request $request)
    {
        $f = $this->filters($request);
        $t = $this->buildTotaux($f);
        $base = $this->coursQuery($f, $t['classeIds'], $t['enseignantIds']);
        $total = (clone $base)->count();
        $publies = (clone $base)->where('is_published', true)->count();
        $parMatiere = Matiere::when($f['ecole_id'], fn ($q) => $q->where('ecole_id', $f['ecole_id']))
            ->when($f['matiere_id'], fn ($q) => $q->where('id', $f['matiere_id']))->get()
            ->map(function ($m) use ($base, $total) {
                $c = (clone $base)->where('matiere_id', $m->id)->count();
                return ['id' => $m->id, 'name' => $m->name, 'total_cours' => $c, 'pourcentage' => $this->pct($c, $total)];
            })->all();
        return $this->ok('Statistiques des cours.', [
            'total_cours' => $total, 'publies' => $publies, 'non_publies' => $total - $publies,
            'publies_pourcentage' => $this->pct($publies, $total), 'par_matiere' => $parMatiere,
        ], $f);
    }

    public function quiz(Request $request)
    {
        $f = $this->filters($request);
        $t = $this->buildTotaux($f);
        $tq = $t['total_quiz'];
        $tquest = empty($t['quizIds']) ? 0 : Question::whereIn('quiz_id', $t['quizIds'])->count();
        $trep = empty($t['questionIds']) ? 0 : Reponse::whereIn('question_id', $t['questionIds'])->count();
        return $this->ok('Statistiques des quiz.', [
            'total_quiz' => $tq, 'total_questions' => $tquest, 'total_reponses' => $trep,
            'moyenne_questions_par_quiz' => $tq > 0 ? round($tquest / $tq, 2) : 0,
            'moyenne_reponses_par_question' => $tquest > 0 ? round($trep / $tquest, 2) : 0,
        ], $f);
    }

    public function handicaps(Request $request)
    {
        $f = $this->filters($request);
        $cIds = $this->classeIds($f);
        $total = Eleve::when(!is_null($cIds), fn ($q) => $q->whereIn('classe_id', $cIds))->count();
        return $this->ok('Statistiques des situations de handicap.', [
            'total_eleves' => $total, 'total_types_handicap' => Handicap::count(),
            'repartition' => $this->repHandicap($f, $cIds, $total),
        ], $f);
    }

    public function matieres(Request $request)
    {
        $f = $this->filters($request);
        $t = $this->buildTotaux($f);
        $base = $this->coursQuery($f, $t['classeIds'], $t['enseignantIds']);
        $total = (clone $base)->count();
        $rows = Matiere::when($f['ecole_id'], fn ($q) => $q->where('ecole_id', $f['ecole_id']))
            ->when($f['matiere_id'], fn ($q) => $q->where('id', $f['matiere_id']))->withCount('classes')->get()
            ->map(function ($m) use ($base, $total) {
                $c = (clone $base)->where('matiere_id', $m->id)->count();
                return ['id' => $m->id, 'name' => $m->name, 'ecole_id' => $m->ecole_id,
                    'total_cours' => $c, 'total_classes' => (int) $m->classes_count, 'pourcentage_cours' => $this->pct($c, $total)];
            })->all();
        return $this->ok('Statistiques des matieres.', [
            'total_matieres' => count($rows), 'total_cours' => $total, 'repartition' => $rows,
        ], $f);
    }

    public function listeEleves(Request $request)
    {
        $request->validate([
            'ecole_id' => 'nullable|integer|exists:ecoles,id',
            'classe_id' => 'nullable|integer|exists:classes,id',
            'handicap_id' => 'nullable|integer|exists:handicaps,id',
            'per_page' => 'nullable|integer|min:1|max:200',
        ]);
        $perPage = (int) $request->query('per_page', 50);
        $p = Eleve::with(['classe:id,name,ecole_id', 'classe.ecole:id,name', 'handicap:id,name'])
            ->when($request->query('classe_id'), fn ($q, $v) => $q->where('classe_id', $v))
            ->when($request->query('ecole_id'), fn ($q, $v) => $q->whereHas('classe', fn ($c) => $c->where('ecole_id', $v)))
            ->when($request->query('handicap_id'), fn ($q, $v) => $q->where('handicap_id', $v))
            ->select(['id', 'name', 'prenom', 'classe_id', 'handicap_id', 'dateOfNaissance', 'is_connect', 'created_at'])
            ->paginate($perPage);
        return response()->json([
            'success' => true, 'status' => 'success',
            'message' => 'Liste des eleves (donnees non sensibles).',
            'data' => $p->items(),
            'meta' => ['current_page' => $p->currentPage(), 'per_page' => $p->perPage(), 'total' => $p->total(), 'last_page' => $p->lastPage()],
        ]);
    }

    public function schema()
    {
        $hasSexe = Schema::hasColumn('eleves', 'sexe') || Schema::hasColumn('eleves', 'genre');
        $hasNiveau = Schema::hasColumn('classes', 'niveau');
        return response()->json([
            'success' => true, 'status' => 'success',
            'message' => 'Capacites statistiques du backend.',
            'data' => [
                'disponibles' => ['totaux', 'repartition_par_classe', 'repartition_par_etablissement', 'repartition_par_handicap', 'repartition_par_matiere', 'cours', 'quiz', 'notes', 'medias'],
                'indisponibles' => [
                    ['indicateur' => 'repartition_sexe', 'disponible' => $hasSexe,
                        'raison' => $hasSexe ? null : 'Colonne sexe absente (eleves, enseignants).',
                        'donnees_manquantes' => $hasSexe ? [] : ['eleves.sexe', 'enseignants.sexe']],
                    ['indicateur' => 'repartition_niveau', 'disponible' => $hasNiveau,
                        'raison' => $hasNiveau ? null : 'Colonne niveau absente (classes).',
                        'donnees_manquantes' => $hasNiveau ? [] : ['classes.niveau']],
                ],
            ],
        ]);
    }
}
