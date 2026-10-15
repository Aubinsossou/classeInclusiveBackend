<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Classe;
use App\Models\Ecole;
use App\Models\Eleve;
use App\Models\Enseignant;
use App\Models\Handicap;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\Passport;
use Tests\TestCase;

/**
 * Matrice d'autorisation cross-rôle (backend) :
 * chaque guard Passport (ecole_api, enseignant_api, client_api, eleve_api)
 * doit accepter le token de SON rôle et rejeter avec un 401 les tokens des
 * trois autres rôles. Les guards étant liés à des providers distincts dans
 * config/auth.php, un token émis pour un provider ne peut jamais
 * authentifier une route protégée par un autre guard.
 */
class CrossRoleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{ecole: Ecole, classe: Classe, enseignant: Enseignant, client: Client, eleve: Eleve} */
    private function contexte(): array
    {
        $ecole = Ecole::create([
            'name' => 'Ecole Test',
            'email' => 'ecole.test@example.com',
            'password' => Hash::make('test1234'),
        ]);

        $classe = Classe::create(['name' => 'CM2', 'ecole_id' => $ecole->id]);

        $enseignant = Enseignant::create([
            'name' => 'Dupont',
            'prenom' => 'Jean',
            'matricule' => 'ENS-001',
            'email' => 'jean.dupont@example.com',
            'numero' => '0700000001',
            'ecole_id' => $ecole->id,
            'classe_id' => $classe->id,
            'password' => Hash::make('MotDePasse1'),
        ]);

        $client = Client::create([
            'name' => 'Client Test',
            'email' => 'client.test@example.com',
            'numero' => '0700000002',
            'password' => Hash::make('test1234'),
        ]);

        $handicap = Handicap::create(['name' => 'Aucun handicap']);

        $eleve = Eleve::create([
            'name' => 'Bernard',
            'prenom' => 'Emma',
            'numeroParent' => '0600000000',
            'dateOfNaissance' => '2014-07-21',
            'handicap_id' => $handicap->id,
            'classe_id' => $classe->id,
            'is_connect' => false,
            'code' => 123456,
        ]);

        return compact('ecole', 'classe', 'enseignant', 'client', 'eleve');
    }

    public function test_chaque_route_protegee_rejette_les_tokens_des_trois_autres_roles(): void
    {
        $c = $this->contexte();

        // Une route représentative par guard Passport.
        $routes = [
            'ecole' => ['ecole_api', '/api/v1/ecole/getEcole'],
            'enseignant' => ['enseignant_api', '/api/v1/enseignant/cours/index'],
            'client' => ['client_api', '/api/v1/client/getClient'],
            'eleve' => ['eleve_api', '/api/v1/eleve/getEleve'],
        ];

        // Un utilisateur de chaque rôle, authentifié sur SON guard.
        $acteurs = [
            'ecole' => ['ecole_api', $c['ecole']],
            'enseignant' => ['enseignant_api', $c['enseignant']],
            'client' => ['client_api', $c['client']],
            'eleve' => ['eleve_api', $c['eleve']],
        ];

        foreach ($routes as $roleCible => [$guardCible, $uri]) {
            foreach ($acteurs as $roleActeur => [$guardActeur, $user]) {
                // Les instances de guards sont mises en cache dans l'application
                // de test : sans oubli, le setUser() d'une itération précédenle
                // ferait passer les tokens suivants sur ce même guard.
                Auth::forgetGuards();
                Passport::actingAs($user, [], $guardActeur);

                $reponse = $this->getJson($uri);

                if ($roleActeur === $roleCible) {
                    // Contrôle : le token du bon rôle traverse le middleware.
                    $this->assertNotSame(
                        401,
                        $reponse->getStatusCode(),
                        "La route {$uri} a rejeté le token de son propre rôle ({$roleActeur})."
                    );
                } else {
                    $reponse->assertStatus(401);
                }
            }
        }
    }

    public function test_les_routes_partagees_ecole_enseignant_autorisent_uniquement_ces_deux_roles(): void
    {
        $c = $this->contexte();
        // Protégée par auth:ecole_api,enseignant_api.
        $uri = '/api/v1/ecole/handicap/index';

        Passport::actingAs($c['ecole'], [], 'ecole_api');
        $this->assertNotSame(401, $this->getJson($uri)->getStatusCode());

        Auth::forgetGuards();
        Passport::actingAs($c['enseignant'], [], 'enseignant_api');
        $this->assertNotSame(401, $this->getJson($uri)->getStatusCode());

        Auth::forgetGuards();
        Passport::actingAs($c['client'], [], 'client_api');
        $this->getJson($uri)->assertStatus(401);

        Auth::forgetGuards();
        Passport::actingAs($c['eleve'], [], 'eleve_api');
        $this->getJson($uri)->assertStatus(401);
    }

    public function test_sans_token_aucune_route_protegee_n_est_accessible(): void
    {
        foreach ([
            '/api/v1/ecole/getEcole',
            '/api/v1/enseignant/cours/index',
            '/api/v1/client/getClient',
            '/api/v1/eleve/getEleve',
            '/api/v1/ecole/handicap/index',
        ] as $uri) {
            $this->getJson($uri)->assertStatus(401);
        }
    }

    public function test_un_bearer_token_invalide_est_rejete_par_tous_les_guards(): void
    {
        foreach ([
            '/api/v1/ecole/getEcole',
            '/api/v1/enseignant/cours/index',
            '/api/v1/client/getClient',
            '/api/v1/eleve/getEleve',
        ] as $uri) {
            $this->getJson($uri, ['Authorization' => 'Bearer jeton-absolument-faux'])
                ->assertStatus(401);
        }
    }
}
