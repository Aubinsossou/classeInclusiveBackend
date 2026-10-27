<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Eleve;
use App\Models\Enseignant;
use App\Models\eleveHandicap;
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
            'numeroParent' => 'required|string',
            'handicap_id' => 'required|integer|exists:handicaps,id',
            'dateOfNaissance' => 'required|String',
            'is_connect' => 'String',
            'classe_id' => 'required|integer|exists:classes,id',
            'code' => 'required|string|max:6',
        ]);
        if ($validate->fails()) {
            return response()->json([
                'errors' => $validate->errors(),
                'message' => 'Validation échoué',
            ], 400);
        }

        $eleve = Eleve::create([
            'name' => $request->name,
            'prenom' => $request->prenom,
            'numeroParent' => $request->numeroParent,
            'dateOfNaissance' => $request->dateOfNaissance,
            'handicap_id' => $request->handicap_id,
            'classe_id' => $request->classe_id,
            'is_connect' => $request->is_connect,
            'code' => $request->code,
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

            'code' => 'required|string',
        ]);
        if ($validate->fails()) {
            return response()->json([
                'errors' => $validate->errors(),
                'message' => 'Connexion failed',
            ], 400);
        }

        $eleve = Eleve::where('code', $request->code)->get()->first();
        // $eleve->getRoleNames();


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
                'message' => 'Aucun eleve trouver avec ce code',
            ], 400);
        }
        return response()->json([
            'message' => 'code incorrect',
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
            'numeroParent' => 'required|string',
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

        $eleveConnect = Auth::guard('eleve_api')->user();

        if (!$eleveConnect) {
            return response()->json([
                "status" => "Echoué",
                "message" => "Aucun Eleve trouver avec cet id",
            ], 400);
        }


        $eleveConnect->update([
            'is_connect' => !$eleveConnect->is_connect
        ]);

        return response()->json([
            "status" => "Success",
            "message" => " Eleve Deconnecter",
            "data" => $eleveConnect,
        ]);

    }


    public function getEleve()
    {

        $eleve = Auth::guard('eleve_api')->user()->load([
            'handicap',
            'classe.enseignant.cours.matiere',
            'classe.enseignant.cours.medias',
            'classe.enseignant.cours.materiels',
            'classe.enseignant.cours.strategies',
            'classe.enseignant.cours.quizzes' => function ($query) {
                $query->with([
                    'questions.reponses',
                    'notes'
                ]);
            }
        ])->makeHidden(['password']);

        $eleve->getRoleNames();

        if ($eleve) {
            return response()->json([
                "status" => "Success",
                "message" => "Eleve trouvé avec succès",
                "data" => $eleve,
            ]);
        }
        return response()->json([
            "status" => "Echec",
            "message" => "Aucun élève trouvé",
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

    /**
     * Ajout d'un élève par l'enseignant, dans SA classe.
     * La classe de rattachement est toujours déduite du compte enseignant
     * authentifié (enseignants.classe_id) : un éventuel classe_id envoyé
     * dans la requête est volontairement ignoré.
     */
    public function storeForEnseignant(Request $request)
    {
        $enseignant = Auth::guard('enseignant_api')->user();

        if (!$enseignant) {
            return response()->json([
                "status" => "Echec",
                "message" => "Aucun enseignant authentifié",
            ], 401);
        }

        if (!$enseignant->classe_id) {
            return response()->json([
                "status" => "Echec",
                "message" => "Aucune classe n'est assignée à cet enseignant",
            ], 400);
        }

        $validate = Validator::make($request->all(), [
            'name' => 'required|string',
            'prenom' => 'required|string',
            'numeroParent' => 'required|string',
            'handicap_id' => 'required|integer|exists:handicaps,id',
            'dateOfNaissance' => 'required|string',
            'code' => 'required|string|max:6',
        ]);
        if ($validate->fails()) {
            return response()->json([
                "status" => "Echec",
                "message" => "Validation échouée",
                "errors" => $validate->errors(),
            ], 400);
        }

        if (Eleve::where('code', $request->code)->exists()) {
            return response()->json([
                "status" => "Echec",
                "message" => "Ce code est déjà utilisé par un autre élève",
            ], 409);
        }

        $eleve = Eleve::create([
            'name' => $request->name,
            'prenom' => $request->prenom,
            'numeroParent' => $request->numeroParent,
            'dateOfNaissance' => $request->dateOfNaissance,
            'handicap_id' => $request->handicap_id,
            'classe_id' => $enseignant->classe_id,
            'is_connect' => false,
            'code' => $request->code,
        ]);

        $exists = Role::where('name', 'eleve')
            ->where('guard_name', 'eleve_api')
            ->exists();

        if (!$exists) {
            Role::create(["name" => "eleve", "guard_name" => "eleve_api"]);
        }

        $eleve->assignRole("eleve");

        return response()->json([
            "status" => "Success",
            "message" => "Elève ajouté à votre classe avec succès",
            "data" => $eleve,
        ], 201);
    }

    /**
     * Modification d'un élève par l'enseignant, dans SA classe.
     * L'enseignant ne peut modifier que les élèves de SA classe.
     * Si le code est vide ou inchangé, il est conservé tel quel.
     */
    public function updateForEnseignant(Request $request, $id)
    {
        $enseignant = Auth::guard('enseignant_api')->user();

        if (!$enseignant) {
            return response()->json([
                "status" => "Echec",
                "message" => "Aucun enseignant authentifié",
            ], 401);
        }

        if (!$enseignant->classe_id) {
            return response()->json([
                "status" => "Echec",
                "message" => "Aucune classe n'est assignée à cet enseignant",
            ], 400);
        }

        $eleve = Eleve::find($id);

        if (!$eleve) {
            return response()->json([
                "status" => "Echec",
                "message" => "Aucun élève trouvé avec cet id",
            ], 400);
        }

        if ($eleve->classe_id != $enseignant->classe_id) {
            return response()->json([
                "status" => "Echec",
                "message" => "Cet élève n'appartient pas à votre classe",
            ], 403);
        }

        $validate = Validator::make($request->all(), [
            'name' => 'required|string',
            'prenom' => 'required|string',
            'numeroParent' => 'required|string',
            'handicap_id' => 'required|integer|exists:handicaps,id',
            'dateOfNaissance' => 'required|string',
            'code' => 'nullable|string|max:6',
        ]);
        if ($validate->fails()) {
            return response()->json([
                "status" => "Echec",
                "message" => "Validation échouée",
                "errors" => $validate->errors(),
            ], 400);
        }

        // Si un code est fourni, vérifier qu'il n'est pas déjà utilisé par un AUTRE élève.
        $newCode = $request->input('code');
        if ($newCode !== null && $newCode !== '' && (int)$newCode !== (int)$eleve->code) {
            if (Eleve::where('code', $newCode)->where('id', '!=', $id)->exists()) {
                return response()->json([
                    "status" => "Echec",
                    "message" => "Ce code est déjà utilisé par un autre élève",
                ], 409);
            }
        }

        $payload = [
            'name' => $request->name,
            'prenom' => $request->prenom,
            'numeroParent' => $request->numeroParent,
            'dateOfNaissance' => $request->dateOfNaissance,
            'handicap_id' => $request->handicap_id,
        ];

        // On ne met à jour le code que s'il est explicitement fourni et non vide.
        if ($newCode !== null && $newCode !== '') {
            $payload['code'] = $newCode;
        }

        $eleve->update($payload);
        $eleve->load(['classe', 'handicap']);

        return response()->json([
            "status" => "Success",
            "message" => "Élève modifié avec succès",
            "data" => $eleve,
        ], 200);
    }

    /**
     * Retrait d'un élève de la classe de l'enseignant connecté.
     * L'enseignant ne peut supprimer que les élèves de SA classe.
     * Les notes et retours de projection liés sont supprimés en cascade (FK).
     */
    public function destroyForEnseignant($id)
    {
        $enseignant = Auth::guard('enseignant_api')->user();

        if (!$enseignant) {
            return response()->json([
                "status" => "Echec",
                "message" => "Aucun enseignant authentifié",
            ], 401);
        }

        if (!$enseignant->classe_id) {
            return response()->json([
                "status" => "Echec",
                "message" => "Aucune classe n'est assignée à cet enseignant",
            ], 400);
        }

        $eleve = Eleve::find($id);

        if (!$eleve) {
            return response()->json([
                "status" => "Echec",
                "message" => "Aucun élève trouvé avec cet id",
            ], 400);
        }

        if ($eleve->classe_id != $enseignant->classe_id) {
            return response()->json([
                "status" => "Echec",
                "message" => "Cet élève n'appartient pas à votre classe",
            ], 403);
        }

        try {
            // La table eleve_handicaps n'a pas de cascadeOnDelete :
            // on supprime d'abord les liaisons pour éviter une erreur de FK.
            eleveHandicap::where('eleve_id', $eleve->id)->delete();
            $eleve->delete();
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                "status" => "Echec",
                "message" => "La suppression de l'élève a échoué",
            ], 500);
        }

        return response()->json([
            "status" => "Success",
            "message" => "Elève supprimé avec succès",
        ]);
    }

}
