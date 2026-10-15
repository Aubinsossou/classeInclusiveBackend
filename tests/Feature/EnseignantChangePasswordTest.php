<?php

namespace Tests\Feature;

use App\Models\Ecole;
use App\Models\Enseignant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\Passport;
use Tests\TestCase;

/**
 * Contrat de l'endpoint de changement de mot de passe enseignant (middleware auth:enseignant_api).
 * Verifie l'authentification requise, la validation, la verification du mot de passe actuel
 * et la prise en compte effective du nouveau mot de passe.
 */
class EnseignantChangePasswordTest extends TestCase
{
    use RefreshDatabase;

    protected function enseignant(): Enseignant
    {
        return Enseignant::create([
            'name' => 'Dupont',
            'prenom' => 'Jean',
            'matricule' => 'ENS-001',
            'email' => 'jean.dupont@example.com',
            'numero' => '0700000000',
            'ecole_id' => Ecole::create([
                'name' => 'Ecole Test',
                'email' => 'ecole.test@example.com',
                'password' => Hash::make('test1234'),
            ])->id,
            'password' => Hash::make('AncienMotDePasse1'),
        ]);
    }

    public function test_le_changement_de_mot_de_passe_exige_un_token_enseignant(): void
    {
        $this->postJson('/api/v1/enseignant/change-password', [
            'current_password' => 'AncienMotDePasse1',
            'password' => 'NouveauMotDePasse1',
            'password_confirmation' => 'NouveauMotDePasse1',
        ])->assertStatus(401);
    }

    public function test_le_mot_de_passe_actuel_doit_etre_correct(): void
    {
        $enseignant = $this->enseignant();
        Passport::actingAs($enseignant, [], 'enseignant_api');

        $this->postJson('/api/v1/enseignant/change-password', [
            'current_password' => 'MauvaisMotDePasse1',
            'password' => 'NouveauMotDePasse1',
            'password_confirmation' => 'NouveauMotDePasse1',
        ])
            ->assertStatus(400)
            ->assertJsonPath('status', 'Echoué')
            ->assertJsonValidationErrors('current_password');

        $this->assertTrue(Hash::check('AncienMotDePasse1', $enseignant->fresh()->password));
    }

    public function test_la_confirmation_doit_correspondre_au_nouveau_mot_de_passe(): void
    {
        $enseignant = $this->enseignant();
        Passport::actingAs($enseignant, [], 'enseignant_api');

        $this->postJson('/api/v1/enseignant/change-password', [
            'current_password' => 'AncienMotDePasse1',
            'password' => 'NouveauMotDePasse1',
            'password_confirmation' => 'AutreMotDePasse1',
        ])
            ->assertStatus(400)
            ->assertJsonPath('status', 'Echoué')
            ->assertJsonValidationErrors('password');

        $this->assertTrue(Hash::check('AncienMotDePasse1', $enseignant->fresh()->password));
    }

    public function test_le_nouveau_mot_de_passe_est_trop_court(): void
    {
        $enseignant = $this->enseignant();
        Passport::actingAs($enseignant, [], 'enseignant_api');

        $this->postJson('/api/v1/enseignant/change-password', [
            'current_password' => 'AncienMotDePasse1',
            'password' => 'court',
            'password_confirmation' => 'court',
        ])
            ->assertStatus(400)
            ->assertJsonValidationErrors('password');
    }

    public function test_le_mot_de_passe_est_modifie_avec_succes(): void
    {
        $enseignant = $this->enseignant();
        Passport::actingAs($enseignant, [], 'enseignant_api');

        $this->postJson('/api/v1/enseignant/change-password', [
            'current_password' => 'AncienMotDePasse1',
            'password' => 'NouveauMotDePasse1',
            'password_confirmation' => 'NouveauMotDePasse1',
        ])
            ->assertOk()
            ->assertJsonPath('status', 'Success')
            ->assertJsonMissingPath('data.password');

        $enseignant->refresh();
        $this->assertTrue(Hash::check('NouveauMotDePasse1', $enseignant->password));
        $this->assertFalse(Hash::check('AncienMotDePasse1', $enseignant->password));
    }

    public function test_le_token_ecole_ne_peut_pas_changer_le_mot_de_passe_enseignant(): void
    {
        $this->postJson('/api/v1/enseignant/change-password', [
            'current_password' => 'AncienMotDePasse1',
            'password' => 'NouveauMotDePasse1',
            'password_confirmation' => 'NouveauMotDePasse1',
        ], ['Authorization' => 'Bearer token.ecole.invalide'])->assertStatus(401);
    }
}
