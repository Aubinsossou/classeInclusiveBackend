<?php

namespace App\Http\Controllers;

use App\Models\ClasseMatiere;
use DB;
use Illuminate\Http\Request;
use Validator;

class ClasseMatiereController extends Controller
{
    public function index()
    {
        $classesMatiere = ClasseMatiere::all();

        return response()->json([
            "status" => "Success",
            "message" => "listes des classes et leurs matiere trouver",
            "data" => $classesMatiere,
        ]);

    }

    public function store(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'classe_id' => 'required|exists:classes,id',
            'matiere_id' => 'required|array',
            'matiere_id.*' => 'exists:matieres,id',
            'ecole_id' => 'required|exists:ecoles,id',
        ]);

        if ($validate->fails()) {
            return response()->json([
                "status" => "Echec",
                "message" => $validate->errors(),
            ], 422);
        }


        DB::beginTransaction();

        $classeMatieres = [];

        foreach ($request->matiere_id as $matiere_id) {
            $classeMatieres[] = ClasseMatiere::updateOrCreate([
                'classe_id' => $request->classe_id,
                'matiere_id' => $matiere_id,
                'ecole_id' => $request->ecole_id,
            ]);
        }

        DB::commit();


        return response()->json([
            "status" => "Success",
            "message" => "Matiere assigner a la classe avec success",
            "data" => $classeMatieres,
        ]);
    }
    public function edit($id)
    {
        $classe = ClasseMatiere::find($id);

        if ($classe) {
            return response()->json([
                "status" => "Success",
                "message" => "classe retrouver",
                "data" => $classe,
            ]);
        }
        return response()->json([
            "status" => "Echec",
            "message" => "Classe non retrouver",
        ]);
    }

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
