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
     $eleves =  Eleve::with(['classe', 'handicap'])->get();
        return response()->json([
            'status' => 'success',
            'message' => 'Liste des élèves',
            'data' => $eleves
        ]);
    }

    public function registerEleve(Request $request, PasswordEleveService $passwordService)
    {
        $validate = Validator::make($request->all(), [
            'name' => 'required|string',
            'prenom' => 'required|string',
            'numeroParent' => 'required|integer',
            'handicap_id' => 'required|integer|exists:handicaps,id',
            'dateOfNaissance' => 'required|String',
            'classe_id' => 'required|integer|exists:classes,id',
        ]);
        if ($validate->fails()) {
            return response()->json([
                'errors' => $validate->errors(),
                'message' => 'Validation échoué',
            ], 400);
        }
        $code = $passwordService->generateSecurePassword();

        $eleve = Eleve::create([
            'name' => $request->name,
            'prenom' => $request->prenom,
            'numeroParent' => $request->numeroParent,
            'dateOfNaissance' => $request->dateOfNaissance,
            'handicap_id' => $request->handicap_id,
            'classe_id' => $request->classe_id,
            'code' => $code,
        ]);
        $exists = Role::where('name', 'eleve')
            ->where('guard_name', 'eleve_api')
            ->exists();

        if (!$exists) {
            Role::create(["name" => "eleve", "guard_name" => "eleve_api"]);
        }

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
            'code' => 'required|string',
        ]);
        if ($validate->fails()) {
            return response()->json([
                'errors' => $validate->errors(),
                'message' => 'Connexion failed',
            ], 400);
        }

        $eleve = Eleve::where('code', $request->code)->get()->first();
        $eleve->getRoleNames();


        if ($eleve && Hash::check($request->code, $eleve->code)) {
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
