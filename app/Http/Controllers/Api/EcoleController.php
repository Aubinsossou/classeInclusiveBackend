<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ecole;
use App\Models\Eleve;
use App\Models\Enseignant;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Mail\EnseignantPasswordMail;
use Illuminate\Support\Facades\Mail;

use Spatie\Permission\Models\Role;
use App\Services\PasswordEnseignantService;
use App\Services\PasswordEleveService;

use Validator;


class EcoleController extends Controller
{

    public function index()
    {
        $ecole = Ecole::all();

        if ($ecole) {

            return response()->json([
                'status' => 'success',
                'message' => 'Liste des ecoles',
                'data' => $ecole,
            ]);
        }
        return response()->json([
            'status' => 'Echec',
            'message' => 'Liste des ecoles non trouver',
            'data' => $ecole,
        ]);

    }
    public function registerEcole(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'telephone' => 'required|string',
            'password' => 'required|string|min:4',

        ]);
        if ($validate->fails()) {
            return response()->json([
                'errors' => $validate->errors(),
                'message' => 'Validation échoué',
            ], 400);
        }
        $ecole = Ecole::create([
            'name' => $request->name,
            'email' => $request->email,
            'telephone' => $request->telephone,
            'password' => Hash::make($request->password),
        ]);
        $ecole->assignRole("ecole");


        return response()->json([
            'status' => 'success',
            'message' => 'Mentor créé avec succès',
            'data' => $ecole,
        ]);
    }


    public function loginEcole(Request $request)
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

        $ecole = Ecole::where('email', $request->email)->get()->first();
        $ecole->getRoleNames();


        if ($ecole && Hash::check($request->password, $ecole->password)) {
            Auth::login($ecole);
            $accessToken = $ecole->createToken('ecoleToken')->accessToken;
            $refreshToken = $ecole->createToken('refreshEcoleToken')->accessToken;

            return response()->json([
                "status" => 'success',
                'message' => 'Connexion réussi',
                'data' => $ecole,
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,

            ]);
        }
        if (!$ecole) {
            return response()->json([
                'message' => 'Aucune ecole trouver avec ce mail',
            ], 400);
        }
        return response()->json([
            'message' => 'Email ou mot de passe incorrect',
        ], 400);
    }





    public function getEcole()
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
