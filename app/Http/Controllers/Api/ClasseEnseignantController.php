<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\classe_enseignant;
use Illuminate\Http\Request;
use Validator;

class ClasseEnseignantController extends Controller
{
    public function index()
    {
        $classesEnseignant = classe_enseignant::all();
        if (count($classesEnseignant) !== 0) {
            return response()->json([
                "status" => "Success",
                "message" => "listes des classes et Enseignants trouver",
                "data" => $classesEnseignant,
            ]);
        }
        return response()->json([
            "status" => "Echec",
            "message" => "listes des classes Enseignants non trouver",
        ]);
    }

    public function store(Request $request)
    {
        $validate = Validator::make($request->all(), [
            "classe_id" => 'required|integer|exists:classes',
            "enseignant_id" => 'required|integer|exists:enseignants',
        ]);

        if ($validate->fails()) {
            return response()->json([
                "status" => "Echec",
                "message" => $validate->errors(),
            ], 400);
        }

        try {
          $classeEnsignant =  classe_enseignant::create([
                'classe_id' => $request->classe_id,
                'enseignant_id' => $request->enseignant_id,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'message' => 'Déjà existant'
            ], 409);
        }

        return response()->json([
            "status" => "Success",
            "message" => "Classe creer avec success",
            "data" => $classeEnsignant,
        ]);
    }
    // public function edit($id)
    // {
    //     $classe = classe_enseignant::find($id);

    //     if ($classe) {
    //         return response()->json([
    //             "status" => "Success",
    //             "message" => "classe retrouver",
    //             "data" => $classe,
    //         ]);
    //     }
    //     return response()->json([
    //         "status" => "Echec",
    //         "message" => "Classe non retrouver",
    //     ]);
    // }

    // public function update(Request $request, $id)
    // {
    //     $validate = Validator::make($request->all(), [
    //         "name" => 'required|string'

    //     ]);
    //     if ($validate->fails()) {
    //         return response()->json([
    //             "status" => "Echoué",
    //             "message" => $validate->errors(),
    //         ]);
    //     }

    //     $classeUpdate = Classe::where("id", "=", $id)->get()->first();

    //     if (!$classeUpdate) {
    //         return response()->json([
    //             "status" => "Echoué",
    //             "message" => "Aucune Classe trouver avec cet id",
    //         ], 400);
    //     }

    //     if ($classeUpdate) {
    //         $classeUpdate->update([
    //             "name" => $request->name,
    //         ]);

    //         return response()->json([
    //             "status" => "Success",
    //             "message" => " Classe modifier avec success",
    //             "data" => $classeUpdate,
    //         ]);
    //     }
    // }
    // public function destroy($id)
    // {
    //     $classe = Classe::find($id);
    //     if ($id) {
    //         $classe->delete();

    //         return response()->json([
    //             "status" => "Success",
    //             "message" => " Classe supprimer avec success",
    //         ]);
    //     }
    //     return response()->json([
    //         "status" => "Echec",
    //         "message" => " Aucune Classe trouver avec cet id pour suppression",
    //     ]);
    // }
}
