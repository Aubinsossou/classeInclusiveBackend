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
    public function registerEnseignant(Request $request, PasswordEnseignantService $passwordService)
    {
        $validate = Validator::make($request->all(), [
            'matricule' => 'required|string',
            'email' => 'required|string|email|max:255|unique:users',
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
            'password' => $password,
        ]);
        $enseignant->assignRole("enseignant");
        Mail::to($request->email)
            ->send(new EnseignantPasswordMail($enseignant, $password));

        return response()->json([
            'status' => 'success',
            'message' => 'Enseignant créé avec succès',
            'data' => $enseignant,
        ]);
    }

    public function loginEnseignant(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'email' => 'required|string|email:unique',
            'password' => 'required|string',
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
                'message' => 'Aucune enseignant trouver avec ce mail',
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
