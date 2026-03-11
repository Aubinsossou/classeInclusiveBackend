<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Eleve;
use App\Models\Enseignant;
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
        $eleves = Eleve::with(['classe', 'handicap'])->get();
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
            'is_connect' => 'String',
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
            'is_connect' => $request->is_connect,
            'code' => $code,
        ]);
        // $exists = Role::where('name', 'eleve')
        //     ->where('guard_name', 'eleve_api')
        //     ->exists();

        // if (!$exists) {
        //     Role::create(["name" => "eleve", "guard_name" => "eleve_api"]);
        // }

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


        if ($eleve && $request->code == $eleve->code) {
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

    public function edit($id)
    {
        $eleve = Eleve::find($id);

        if ($eleve) {
            return response()->json([
                "status" => "Success",
                "message" => "Eleve retrouver",
                "data" => $eleve,
            ]);
        }
        return response()->json([
            "status" => "Echec",
            "message" => "Eleve non retrouver",
        ]);
    }

    public function update(Request $request, $id)
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
                "status" => "Echoué",
                "message" => $validate->errors(),
            ]);
        }

        $eleveUpdate = Eleve::where("id", "=", $id)->get()->first();

        if (!$eleveUpdate) {
            return response()->json([
                "status" => "Echoué",
                "message" => "Aucune Matiere trouver avec cet id",
            ], 400);
        }

        if ($eleveUpdate) {
            $eleveUpdate->update([
                'name' => $request->name,
                'prenom' => $request->prenom,
                'numeroParent' => $request->numeroParent,
                'dateOfNaissance' => $request->dateOfNaissance,
                'handicap_id' => $request->handicap_id,
                'classe_id' => $request->classe_id,
            ]);

            return response()->json([
                "status" => "Success",
                "message" => " Matiere modifier avec success",
                "data" => $eleveUpdate,
            ]);
        }
    }

    public function connect()
    {

        $eleveUpdate = Auth::guard('eleve_api')->user()->makeHidden(['password']);

        if (!$eleveUpdate) {
            return response()->json([
                "status" => "Echoué",
                "message" => "Aucun Eleve trouver avec cet id",
            ], 400);
        }

        if ($eleveUpdate) {
            if ($eleveUpdate->is_connect === "false") {
                $eleveUpdate->update([
                    'is_connect' => "true",
                ]);
                return response()->json([
                    "status" => "Success",
                    "message" => " Eleve connecter",
                    "data" => $eleveUpdate,
                ]);
            }
            $eleveUpdate->update([
                'is_connect' => "false",
            ]);
            return response()->json([
                "status" => "Success",
                "message" => " Eleve Deconnecter",
                "data" => $eleveUpdate,
            ]);
        }
    }


    public function getEleve()
    {

        $ecole = Auth::guard('eleve_api')->user()->load([
            'classe.enseignant.cours.medias',
            'classe.enseignant.cours.quizzes' => function ($query) {
                $query->with([
                    'questions.reponses',
                    'notes'
                ]);
            }
        ])->makeHidden(['password']);
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
