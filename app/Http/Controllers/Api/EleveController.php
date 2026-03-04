<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Eleve;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

use Spatie\Permission\Models\Role;
use App\Services\PasswordEleveService;

use Validator;


class EleveController extends Controller
{
    public function index()
    {
        $eleves = Eleve::all();
        if ($eleves) {
            return response()->json([
                'status' => 'success',
                'message' => 'Liste des élèves',
                'data' => $eleves
            ]);
        }
        return response()->json([
            'status' => 'echec',
            'messge' => 'Liste des eleves non trouver'
        ]);
    }

    public function registerEleve(Request $request, PasswordEleveService $passwordService)
    {
        $validate = Validator::make($request->all(), [
            'nom' => 'required|string',
            'prenom' => 'required|string',
            'numeroParent' => 'required|integer',
            'id_handicape' => 'required|integer'
        ]);
        if ($validate->fails()) {
            return response()->json([
                'errors' => $validate->errors(),
                'message' => 'Validation échoué',
            ], 400);
        }
        $code = $passwordService->generateSecurePassword();

        $eleve = Eleve::create([
            'nom' => $request->nom,
            'prenom' => $request->prenom,
            'numeroParent' => $request->numeroParent,
            'id_handicape' => $request->id_handicape,
            'code' => $code,
        ]);


        $eleve->assignRole("eleve");

        return response()->json([
            'status' => 'success',
            'message' => 'Eleve créé avec succès',
            'data' => $eleve,
        ]);
    }
     public function loginEleve(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'nom' => 'required|string',
            'prenom' => 'required|string',
            'password' => 'required|string',
        ]);
        if ($validate->fails()) {
            return response()->json([
                'errors' => $validate->errors(),
                'message' => 'Connexion failed',
            ], 400);
        }

        $eleve = Eleve::where('nom', $request->nom && 'prenom', $request->prenom)->get()->first();
        $eleve->getRoleNames();


        if ($eleve && Hash::check($request->password, $eleve->password)) {
            Auth::login($eleve);
            $accessToken = $eleve->createToken('eleveToken')->accessToken;
            $refreshToken = $eleve->createToken('refreshEleveToken')->accessToken;

            return response()->json([
                "status" => 'success',
                'message' => 'Connexion réussi',
                'data' => $eleve,
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,

            ]);
        }
        if (!$eleve) {
            return response()->json([
                'message' => 'Aucun eleve trouver avec ce mail',
            ], 400);
        }
        return response()->json([
            'message' => 'Nom ou mot de passe incorrect',
        ], 400);
    }

     public function getEleve()
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
