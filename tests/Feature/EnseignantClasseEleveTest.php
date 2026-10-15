<?php

namespace Tests\Feature;

use App\Models\Classe;
use App\Models\Ecole;
use App\Models\Eleve;
use App\Models\Enseignant;
use App\Models\Handicap;
use App\Models\eleveHandicap;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\Passport;
use Tests\TestCase;

/**
 * Contrat des endpoints enseignant de gestion des élèves de sa classe :
 * ajout (POST /api/v1/enseignant/classe/eleve/store) et retrait
 * (DELETE /api/v1/enseignant/classe/eleve/destroy/{id}), protégés par
 * le middleware auth:enseignant_api.
 */
class EnseignantClasseEleveTest extends TestCase
{
    use RefreshDatabase;

    private function handicap(): Handicap
    {
        return Handicap::create(['name' => 'Aucun handicap']);
    }

    /**
     * @return array{ecole: Ecole, classe: Classe, autreClasse: Classe, enseignant: Enseignant}
     */
    private function contexte(bool $enseignantAvecClasse = true): array
    {
        $ecole = Ecole::create([
            'name' => 'Ecole Test',
            'email' => 'ecole.test@example.com',
            'password' => Hash::make('test1234'),
        ]);

        $classe = Classe::create(['name' => 'Classe 6e A', 'ecole_id' => $ecole->id]);
        $autreClasse = Classe::create(['name' => 'Classe 6e B', 'ecole_id' => $ecole->id]);

        $enseignant = Enseignant::create([
            'name' => 'Dupont',
            'prenom' => 'Jean',
            'matricule' => 'ENS-001',
            'email' => 'jean.dupont@example.com',
            'numero' => '0700000000',
            'ecole_id' => $ecole->id,
            'classe_id' => $enseignantAvecClasse ? $classe->id : null,
            'password' => Hash::make('MotDePasse1'),
        ]);

        return compact('ecole', 'classe', 'autreClasse', 'enseignant');
    }

    private function payloadValide(): array
    {
        return [
            'name' => 'Martin',
            'prenom' => 'Lucas',
            'numeroParent' => '0700112233',
            'dateOfNaissance' => '2015-03-10',
            'handicap_id' => $this->handicap()->id,
            'code' => 123456,
            // Doit être ignoré : la classe est imposée par le compte enseignant.
            'classe_id' => 999,
        ];
    }

    private function creerEleve(int $classeId, int $code): Eleve
    {
        return Eleve::create([
            'name' => 'Bernard',
            'prenom' => 'Emma',
            'numeroParent' => '0600000000',
            'dateOfNaissance' => '2014-07-21',
            'handicap_id' => $this->handicap()->id,
            'classe_id' => $classeId,
            'is_connect' => false,
            'code' => $code,
        ]);
    }

    // ─────────────────────────── Ajout d'un élève ────────────────────────────

    public function test_l_ajout_d_un_eleve_exige_un_token_enseignant(): void
    {
        $this->postJson('/api/v1/enseignant/classe/eleve/store', $this->payloadValide())
            ->assertStatus(401);

        $this->assertSame(0, Eleve::count());
    }

    public function test_l_ajout_echoue_si_des_champs_obligatoires_manquent(): void
    {
        $contexte = $this->contexte();
        Passport::actingAs($contexte['enseignant'], [], 'enseignant_api');

        $this->postJson('/api/v1/enseignant/classe/eleve/store', ['name' => 'Martin'])
            ->assertStatus(400)
            ->assertJsonPath('status', 'Echec')
            ->assertJsonValidationErrors(['prenom', 'numeroParent', 'handicap_id', 'dateOfNaissance', 'code']);

        $this->assertSame(0, Eleve::count());
    }

    public function test_un_enseignant_sans_classe_ne_peut_pas_ajouter_d_eleve(): void
    {
        $contexte = $this->contexte(enseignantAvecClasse: false);
        Passport::actingAs($contexte['enseignant'], [], 'enseignant_api');

        $this->postJson('/api/v1/enseignant/classe/eleve/store', $this->payloadValide())
            ->assertStatus(400)
            ->assertJsonPath('status', 'Echec')
            ->assertJsonPath('message', "Aucune classe n'est assignée à cet enseignant");

        $this->assertSame(0, Eleve::count());
    }

    public function test_le_code_de_l_eleve_doit_etre_unique(): void
    {
        $contexte = $this->contexte();
        $this->creerEleve($contexte['classe']->id, 123456);
        Passport::actingAs($contexte['enseignant'], [], 'enseignant_api');

        $this->postJson('/api/v1/enseignant/classe/eleve/store', $this->payloadValide())
            ->assertStatus(409)
            ->assertJsonPath('status', 'Echec');

        $this->assertSame(1, Eleve::count());
    }

    public function test_l_eleve_est_cree_dans_la_classe_de_l_enseignant_avec_le_role_eleve(): void
    {
        $contexte = $this->contexte();
        Passport::actingAs($contexte['enseignant'], [], 'enseignant_api');

        $this->postJson('/api/v1/enseignant/classe/eleve/store', $this->payloadValide())
            ->assertStatus(201)
            ->assertJsonPath('status', 'Success')
            ->assertJsonPath('data.name', 'Martin')
            ->assertJsonPath('data.classe_id', $contexte['classe']->id)
            ->assertJsonMissingPath('data.password');

        $eleve = Eleve::first();
        $this->assertSame($contexte['classe']->id, (int) $eleve->classe_id);
        $this->assertSame(123456, (int) $eleve->code);
        $this->assertFalse((bool) $eleve->is_connect);
        $this->assertTrue($eleve->fresh()->hasRole('eleve'));
    }

    // ────────────────────────── Retrait d'un élève ───────────────────────────

    public function test_le_retrait_exige_un_token_enseignant(): void
    {
        $contexte = $this->contexte();
        $eleve = $this->creerEleve($contexte['classe']->id, 111111);

        $this->deleteJson("/api/v1/enseignant/classe/eleve/destroy/{$eleve->id}")
            ->assertStatus(401);

        $this->assertTrue(Eleve::where('id', $eleve->id)->exists());
    }

    public function test_le_retrait_d_un_eleve_inexistant_echoue(): void
    {
        $contexte = $this->contexte();
        Passport::actingAs($contexte['enseignant'], [], 'enseignant_api');

        $this->deleteJson('/api/v1/enseignant/classe/eleve/destroy/9999')
            ->assertStatus(400)
            ->assertJsonPath('status', 'Echec');
    }

    public function test_un_enseignant_sans_classe_ne_peut_pas_retirer_d_eleve(): void
    {
        $contexte = $this->contexte(enseignantAvecClasse: false);
        $eleve = $this->creerEleve($contexte['classe']->id, 111111);
        Passport::actingAs($contexte['enseignant'], [], 'enseignant_api');

        $this->deleteJson("/api/v1/enseignant/classe/eleve/destroy/{$eleve->id}")
            ->assertStatus(400)
            ->assertJsonPath('status', 'Echec');

        $this->assertTrue(Eleve::where('id', $eleve->id)->exists());
    }

    public function test_le_retrait_d_un_eleve_d_une_autre_classe_est_refuse(): void
    {
        $contexte = $this->contexte();
        $eleve = $this->creerEleve($contexte['autreClasse']->id, 222222);
        Passport::actingAs($contexte['enseignant'], [], 'enseignant_api');

        $this->deleteJson("/api/v1/enseignant/classe/eleve/destroy/{$eleve->id}")
            ->assertStatus(403)
            ->assertJsonPath('status', 'Echec')
            ->assertJsonPath('message', "Cet élève n'appartient pas à votre classe");

        $this->assertTrue(Eleve::where('id', $eleve->id)->exists());
    }

    public function test_l_enseignant_peut_supprimer_un_eleve_de_sa_classe(): void
    {
        $contexte = $this->contexte();
        $eleve = $this->creerEleve($contexte['classe']->id, 333333);
        // Liaison sans cascadeOnDelete : doit être nettoyée par l'endpoint.
        eleveHandicap::create(['eleve_id' => $eleve->id, 'handicap_id' => $this->handicap()->id]);

        Passport::actingAs($contexte['enseignant'], [], 'enseignant_api');

        $this->deleteJson("/api/v1/enseignant/classe/eleve/destroy/{$eleve->id}")
            ->assertOk()
            ->assertJsonPath('status', 'Success')
            ->assertJsonPath('message', 'Elève supprimé avec succès');

        $this->assertDatabaseMissing('eleves', ['id' => $eleve->id]);
        $this->assertDatabaseMissing('eleve_handicaps', ['eleve_id' => $eleve->id]);
    }
}

