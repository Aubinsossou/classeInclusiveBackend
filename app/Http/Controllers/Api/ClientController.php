<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Classe;
use App\Models\Client;
use App\Models\Cours;
use App\Models\Ecole;
use App\Models\Enseignant;
use App\Models\Handicap;
use App\Models\Matiere;
use App\Models\Quiz;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Validator;

class ClientController extends Controller
{
    public function registerClient(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:clients,email',
            'numero' => 'nullable|string',
            'password' => 'required|string|min:4',
        ]);

        if ($validate->fails()) {
            return response()->json([
                'errors' => $validate->errors(),
                'message' => 'Validation échoué',
            ], 400);
        }

        $client = Client::create([
            'name' => $request->name,
            'email' => $request->email,
            'numero' => $request->numero,
            'password' => Hash::make($request->password),
        ]);

        $exists = Role::where('name', 'client')
            ->where('guard_name', 'client_api')
            ->exists();

        if (!$exists) {
            Role::create(['name' => 'client', 'guard_name' => 'client_api']);
        }

        $client->assignRole('client');

        return response()->json([
            'status' => 'success',
            'message' => 'Client créé avec succès',
            'data' => $client,
        ]);
    }

    public function loginClient(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validate->fails()) {
            return response()->json([
                'errors' => $validate->errors(),
                'message' => 'Connexion failed',
            ], 400);
        }

        $client = Client::where('email', $request->email)->first();

        if (!$client) {
            return response()->json([
                'message' => 'Aucun client trouvé avec ce mail',
            ], 400);
        }

        $client->getRoleNames();

        if ($client && Hash::check($request->password, $client->password)) {
            Auth::login($client);
            $accessToken = $client->createToken('clientToken')->accessToken;
            $refreshToken = $client->createToken('refreshClientToken')->accessToken;

            return response()->json([
                'status' => 'success',
                'message' => 'Connexion réussie',
                'data' => $client,
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
            ]);
        }

        return response()->json([
            'message' => 'Email ou mot de passe incorrect',
        ], 400);
    }

    public function getClient()
    {
        $client = Auth::guard('client_api')->user()->makeHidden(['password']);

        $client->getRoleNames();

        if ($client) {
            return response()->json([
                'status' => 'Success',
                'message' => 'Client trouvé avec succès',
                'data' => $client,
            ]);
        }

        return response()->json([
            'status' => 'Echec',
            'message' => "Aucun client n'a été trouvé",
        ]);
    }

    public function ecoles()
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Liste des écoles',
            'data' => Ecole::all(),
        ]);
    }

    public function classes()
    {
        return response()->json([
            'status' => 'Success',
            'message' => 'Liste des classes',
            'data' => Classe::with(['ecole', 'eleves', 'enseignant', 'matieres'])->get(),
        ]);
    }

    public function matieres()
    {
        return response()->json([
            'status' => 'Success',
            'message' => 'Liste des matières',
            'data' => Matiere::with(['ecole', 'cours'])->get(),
        ]);
    }

    public function enseignants()
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Liste des enseignants',
            'data' => Enseignant::with(['ecole', 'classe'])->get()->map(function ($enseignant) {
                $enseignant->makeHidden(['password']);
                return $enseignant;
            }),
        ]);
    }

    public function cours()
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Liste des cours',
            'data' => Cours::with(['medias', 'matiere', 'classe', 'enseignant'])->get()->map(function ($cours) {
                if ($cours->relationLoaded('enseignant') && $cours->enseignant) {
                    $cours->enseignant->makeHidden(['password']);
                }
                return $cours;
            }),
        ]);
    }

    public function quizzes()
    {
        return response()->json([
            'status' => 'Success',
            'message' => 'Liste des quiz',
            'data' => Quiz::with(['cours', 'questions.reponses'])->get(),
        ]);
    }

    public function handicaps()
    {
        return response()->json([
            'status' => 'Success',
            'message' => 'Liste des handicaps',
            'data' => Handicap::all(),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->token()->revoke();

        return response()->json([
            'status' => 'Success',
            'message' => 'Logout is success',
        ]);
    }
}
