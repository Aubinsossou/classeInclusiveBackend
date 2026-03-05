<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use App\Models\Enseignant;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Mail\EnseignantPasswordMail;
use Illuminate\Support\Facades\Mail;

use Spatie\Permission\Models\Role;
use App\Services\PasswordEnseignantService;
use Validator;

class EnseignantController extends Controller
{
    public function index()
    {
        $enseignants = Enseignant::all();
        if ($enseignants) {
            return response()->json([
                'status' => 'success',
                'message' => 'Liste des élèves',
                'data' => $enseignants
            ]);
        }
        return response()->json([
            'status' => 'echec',
            'messge' => 'Liste des enseignants non trouver'
        ]);
    }

    public function registerEnseignant(Request $request, PasswordEnseignantService $passwordService)
    {
        $validate = Validator::make($request->all(), [
            'name' => 'required|string',
            'prenom' => 'required|string',
            'matricule' => 'required|string',
            'numero' => 'string',
            'email' => 'required|string|email',
            'ecole_id' => 'required|integer|exists:ecoles,id'
        ]);
        if ($validate->fails()) {
            return response()->json([
                'errors' => $validate->errors(),
                'message' => 'Validation échoué',
            ], 400);
        }
        $password = $passwordService->generateSecurePassword();

        $enseignant = Enseignant::create([
            'matricule' => $request->matricule,
            'email' => $request->email,
            'numero' => $request->numero,
            'prenom' => $request->prenom,
            'name' => $request->name,
            'ecole_id' => $request->ecole_id,
            'mot de passe' => Hash::make($password),
        ]);
        $enseignant->assignRole("enseignant");
        Mail::to($request->email)
            ->send(new EnseignantPasswordMail($enseignant, $password));

        return response()->json([
            'status' => 'success',
            'message' => 'Enseignant créé avec succès',
            'data' => $enseignant,
            'password' => $password,

        ]);
    }

    public function loginEnseignant(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255|exists:enseignants',
            "mot de passe" => 'required|string'
        ]);
        if ($validate->fails()) {
            return response()->json([
                'errors' => $validate->errors(),
                'message' => 'Connexion failed',
            ], 400);
        }

        $enseignant = Enseignant::where('email', $request->email)->get()->first();
        $enseignant->getRoleNames();


        if ($enseignant && Hash::check($request->password, $enseignant->password)) {
            Auth::login($enseignant);
            $accessToken = $enseignant->createToken('enseignantToken')->accessToken;
            $refreshToken = $enseignant->createToken('refreshEnseignantToken')->accessToken;

            return response()->json([
                "status" => 'success',
                'message' => 'Connexion réussi',
                'data' => $enseignant,
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,

            ]);
        }
        if (!$enseignant) {
            return response()->json([
                'message' => 'Aucun enseignant trouver avec ce mail',
            ], 400);
        }
        return response()->json([
            'message' => 'Email ou mot de passe incorrect',
        ], 400);
    }

    public function getEnseignant()
    {
        $ecole = Auth::guard('ecole_api')->user()->makeHidden(['password']);
        ;
        $ecole->getRoleNames();

        if ($ecole) {
            return response()->json([
                "status" => "Success",
                "message" => " ecole trouver avec success",
                "data" => $ecole,
            ]);
        }
        return response()->json([
            "status" => "Echec",
            "message" => "Aucune ecole n'a ete trouver",
        ]);
    }


    public function logout(Request $request)
    {
        $request->user()->token()->revoke();

        return response()->json([
            "status" => "Success",
            "message" => "Logout is success"
        ]);
    }


}
