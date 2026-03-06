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

        return response()->json([
            'status' => 'success',
            'message' => 'Liste des élèves',
            'data' => $enseignants
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
        $enseignant = Auth::guard('enseignant_api')->user()->makeHidden(['password']);
        ;
        $enseignant->getRoleNames();

        if ($enseignant) {
            return response()->json([
                "status" => "Success",
                "message" => " enseignant trouver avec success",
                "data" => $enseignant,
            ]);
        }
        return response()->json([
            "status" => "Echec",
            "message" => "Aucune enseignant n'a ete trouver",
        ]);
    }

    public function edit($id)
    {
        $enseignant = Enseignant::with('roles')->find($id);

        if ($enseignant) {
            return response()->json([
                "status" => "Success",
                "message" => "Enseignant retrouver",
                "data" => $enseignant,
            ]);
        }
        return response()->json([
            "status" => "Echec",
            "message" => "Enseignant non retrouver",
        ]);
    }

    public function update(Request $request, $id)
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
                "status" => "Echoué",
                "message" => $validate->errors(),
            ]);
        }

        $enseignantUpdate = Enseignant::where("id", "=", $id)->get()->first();

        if (!$enseignantUpdate) {
            return response()->json([
                "status" => "Echoué",
                "message" => "Aucune Matiere trouver avec cet id",
            ], 400);
        }

        if ($enseignantUpdate) {
            $enseignantUpdate->update([
                "name" => $request->name,
                "prenom" => $request->prenom,
                "matricule" => $request->matricule,
                "email" => $request->email,
                "numero" => $request->numero,
                "ecole_id" => $request->ecole_id,
            ]);

            return response()->json([
                "status" => "Success",
                "message" => " Matiere modifier avec success",
                "data" => $enseignantUpdate,
            ]);
        }
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
