<?php

namespace Database\Seeders;

use App\Models\Classe;
use App\Models\Client;
use App\Models\Ecole;
use App\Models\Eleve;
use App\Models\Enseignant;
use App\Models\Handicap;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/**
 * Comptes fictifs pour les tests manuels (espace école, enseignant, élève, client).
 *
 * Mot de passe commun : « password123 »
 * Codes de connexion élève : lisibles dans le tableau de bord enseignant.
 *
 * Ne jamais exécuter sur la base de développement ou de production.
 */
class ComptesFictifsSeeder extends Seeder
{
    use WithoutModelEvents;

    public const MOT_DE_PASSE = 'password123';

    public function run(): void
    {
        $this->creerRoles();

        // ── Profils d'accessibilité ──────────────────────────────────────────
        $visuel = Handicap::firstOrCreate(['name' => 'Visuel']);
        $auditif = Handicap::firstOrCreate(['name' => 'Auditif']);
        $moteur = Handicap::firstOrCreate(['name' => 'Moteur']);
        $aucun = Handicap::firstOrCreate(['name' => 'Aucun handicap']);

        // ── École + classes ──────────────────────────────────────────────────
        $ecole = Ecole::firstOrCreate(
            ['email' => 'ecole.demo@classeinclusive.test'],
            [
                'name' => 'Lycée Démo Classe Inclusive',
                'numero' => '0102030405',
                'password' => Hash::make(self::MOT_DE_PASSE),
            ],
        );
        $ecole->assignRole('ecole');

        $classe = Classe::firstOrCreate(
            ['name' => 'Classe 6e A', 'ecole_id' => $ecole->id],
        );
        $autreClasse = Classe::firstOrCreate(
            ['name' => 'Classe 6e B', 'ecole_id' => $ecole->id],
        );

        // ── Enseignant rattaché à une classe (peut gérer ses élèves) ─────────
        $enseignant = Enseignant::firstOrCreate(
            ['email' => 'enseignant.demo@classeinclusive.test'],
            [
                'name' => 'Dupont',
                'prenom' => 'Jean',
                'matricule' => 'ENS-DEMO-01',
                'numero' => '0601020304',
                'classe_id' => $classe->id,
                'ecole_id' => $ecole->id,
                'password' => Hash::make(self::MOT_DE_PASSE),
            ],
        );
        $enseignant->assignRole('enseignant');

        // Enseignant sans classe : pour vérifier que l'ajout d'élève est bien bloqué.
        $enseignantSansClasse = Enseignant::firstOrCreate(
            ['email' => 'enseignant.sans.classe@classeinclusive.test'],
            [
                'name' => 'Martin',
                'prenom' => 'Sophie',
                'matricule' => 'ENS-DEMO-02',
                'numero' => '0605060708',
                'classe_id' => null,
                'ecole_id' => $ecole->id,
                'password' => Hash::make(self::MOT_DE_PASSE),
            ],
        );
        $enseignantSansClasse->assignRole('enseignant');

        // ── Élèves de la classe de l'enseignant ──────────────────────────────
        $eleves = [
            ['name' => 'Bernard', 'prenom' => 'Emma', 'code' => '100001', 'handicap_id' => $auditif->id],
            ['name' => 'Petit', 'prenom' => 'Lucas', 'code' => '100002', 'handicap_id' => $visuel->id],
            ['name' => 'Roux', 'prenom' => 'Chloé', 'code' => '100003', 'handicap_id' => $moteur->id],
            ['name' => 'Moreau', 'prenom' => 'Nathan', 'code' => '100004', 'handicap_id' => $aucun->id],
        ];

        foreach ($eleves as $index => $eleve) {
            Eleve::firstOrCreate(
                ['code' => $eleve['code']],
                [
                    'name' => $eleve['name'],
                    'prenom' => $eleve['prenom'],
                    'numeroParent' => '070' . str_pad((string) $index, 8, '0', STR_PAD_LEFT),
                    'dateOfNaissance' => '2015-0' . ($index + 1) . '-15',
                    'handicap_id' => $eleve['handicap_id'],
                    'classe_id' => $classe->id,
                    'is_connect' => $index === 0,
                ],
            );
        }

        // Un élève dans l'autre classe (non supprimable par l'enseignant démo).
        Eleve::firstOrCreate(
            ['code' => '200001'],
            [
                'name' => 'Girard',
                'prenom' => 'Léa',
                'numeroParent' => '0709090909',
                'dateOfNaissance' => '2015-09-01',
                'handicap_id' => $aucun->id,
                'classe_id' => $autreClasse->id,
                'is_connect' => false,
            ],
        );

        // ── Client (espace analytique) ───────────────────────────────────────
        $client = Client::firstOrCreate(
            ['email' => 'client.demo@classeinclusive.test'],
            [
                'name' => 'Client Démo',
                'numero' => '0755667788',
                'password' => Hash::make(self::MOT_DE_PASSE),
            ],
        );
        $client->assignRole('client');

        $this->command?->info('Comptes fictifs créés (mot de passe : ' . self::MOT_DE_PASSE . ').');
        $this->command?->info('- École    : ecole.demo@classeinclusive.test');
        $this->command?->info('- Enseignant: enseignant.demo@classeinclusive.test');
        $this->command?->info('- Client   : client.demo@classeinclusive.test');
        $this->command?->info('- Élèves   : codes 100001 → 100004 (classe 6e A)');
    }

    /**
     * Rôles et guards utilisés par l'application.
     */
    private function creerRoles(): void
    {
        $roles = [
            'ecole' => 'ecole_api',
            'enseignant' => 'enseignant_api',
            'eleve' => 'eleve_api',
            'client' => 'client_api',
        ];

        foreach ($roles as $name => $guard) {
            Role::firstOrCreate(['name' => $name, 'guard_name' => $guard]);
        }
    }
}
