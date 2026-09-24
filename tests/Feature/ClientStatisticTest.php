<?php

namespace Tests\Feature;

use App\Models\Classe;
use App\Models\Client;
use App\Models\Cours;
use App\Models\Ecole;
use App\Models\Enseignant;
use App\Models\Matiere;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\Passport;
use Tests\TestCase;

/**
 * Contrat des endpoints statistiques de l'espace Client (middleware auth:client_api).
 * Verifie l'authentification requise et la presence des champs consommes par le frontend.
 */
class ClientStatisticTest extends TestCase
{
    use RefreshDatabase;

    protected function client(): Client
    {
        return Client::create([
            'name' => 'Client Test',
            'email' => 'client.test@example.com',
            'numero' => '0700000000',
            'password' => Hash::make('test1234'),
        ]);
    }

    public function test_les_statistiques_client_exigent_un_token_client(): void
    {
        $this->getJson('/api/v1/client/statistics/overview')->assertStatus(401);
        $this->getJson('/api/v1/client/statistics/cours')->assertStatus(401);
        $this->getJson('/api/v1/client/statistics/enseignants')->assertStatus(401);
    }

    public function test_overview_contient_les_totaux_globaux(): void
    {
        Passport::actingAs($this->client(), [], 'client_api');

        $this->getJson('/api/v1/client/statistics/overview')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure(['data' => [
                'total_etablissements', 'total_classes', 'total_enseignants', 'total_eleves',
                'total_matieres', 'total_cours', 'total_cours_publies', 'total_quiz',
                'total_questions', 'total_reponses', 'total_notes', 'total_handicaps', 'total_medias',
                'repartition_par_classe', 'repartition_par_etablissement', 'repartition_par_handicap',
                'enseignants', 'cours', 'quiz',
            ]]);
    }

    public function test_cours_expose_quiz_authorise(): void
    {
        Passport::actingAs($this->client(), [], 'client_api');

        $this->getJson('/api/v1/client/statistics/cours')
            ->assertOk()
            ->assertJsonStructure(['data' => [
                'total_cours', 'publies', 'non_publies', 'publies_pourcentage',
                'quiz_authorise', 'quiz_authorise_pourcentage', 'par_matiere',
            ]])
            ->assertJsonPath('data.quiz_authorise', 0)
            ->assertJsonPath('data.total_cours', 0);
    }

    public function test_enseignants_exposent_les_moyennes(): void
    {
        Passport::actingAs($this->client(), [], 'client_api');

        $this->getJson('/api/v1/client/statistics/enseignants')
            ->assertOk()
            ->assertJsonStructure(['data' => [
                'total_enseignants', 'avec_classe', 'sans_classe',
                'avec_classe_pourcentage', 'sans_classe_pourcentage',
                'cours_moyen_par_enseignant', 'quiz_moyen_par_enseignant',
                'par_etablissement',
            ]])
            ->assertJsonPath('data.cours_moyen_par_enseignant', 0)
            ->assertJsonPath('data.quiz_moyen_par_enseignant', 0);
    }

    public function test_etablissement_inexistant_renvoie_422(): void
    {
        Passport::actingAs($this->client(), [], 'client_api');

        $this->getJson('/api/v1/client/statistics/etablissements/999999')
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_liste_eleves_est_paginee_et_sans_donnee_sensible(): void
    {
        Passport::actingAs($this->client(), [], 'client_api');

        $this->getJson('/api/v1/client/eleves?per_page=10')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta' => ['current_page', 'per_page', 'total', 'last_page']])
            ->assertJsonPath('meta.total', 0);
    }

    /**
     * Jeu de donnees minimal : 1 ecole, 1 classe, 1 enseignant, 1 matiere, 2 cours.
     */
    protected function seedParcours(): void
    {
        $ecole = Ecole::create(['name' => 'Ecole Test', 'email' => 'ecole.test@example.com', 'password' => 'secret']);

        $classe = Classe::create(['name' => 'CM2', 'ecole_id' => $ecole->id]);

        $enseignant = Enseignant::create([
            'name' => 'Dupont', 'prenom' => 'Marie', 'matricule' => 'ENS-001',
            'ecole_id' => $ecole->id, 'classe_id' => $classe->id, 'password' => 'secret',
        ]);

        $matiere = Matiere::create(['name' => 'Mathematiques', 'ecole_id' => $ecole->id]);

        Cours::create([
            'title' => 'Cours publie', 'enseignant_id' => $enseignant->id, 'classe_id' => $classe->id,
            'matiere_id' => $matiere->id, 'is_published' => true, 'quiz_authorise' => true,
        ]);

        Cours::create([
            'title' => 'Cours brouillon', 'enseignant_id' => $enseignant->id, 'classe_id' => $classe->id,
            'matiere_id' => $matiere->id, 'is_published' => false, 'quiz_authorise' => false,
        ]);
    }

    public function test_quiz_authorise_et_moyennes_refletent_les_donnees_reelles(): void
    {
        $this->seedParcours();
        Passport::actingAs($this->client(), [], 'client_api');

        $this->getJson('/api/v1/client/statistics/cours')
            ->assertOk()
            ->assertJsonPath('data.total_cours', 2)
            ->assertJsonPath('data.publies', 1)
            ->assertJsonPath('data.quiz_authorise', 1)
            ->assertJsonPath('data.quiz_authorise_pourcentage', 50);

        $this->getJson('/api/v1/client/statistics/enseignants')
            ->assertOk()
            ->assertJsonPath('data.total_enseignants', 1)
            ->assertJsonPath('data.avec_classe', 1)
            ->assertJsonPath('data.cours_moyen_par_enseignant', 2)
            ->assertJsonPath('data.quiz_moyen_par_enseignant', 0);
    }
}
