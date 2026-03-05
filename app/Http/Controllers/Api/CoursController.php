<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cours;
use Illuminate\Http\Request;
use Validator;

class CoursController extends Controller
{
    public function index()
    {
        $cours = Cours::all();
        if (count($cours) !== 0) {
            return response()->json([
                "status" => "Success",
                "message" => "listes des cours trouver",
                "data" => $cours,
            ]);
        }
        return response()->json([
            "status" => "Echec",
            "message" => "listes des cours non trouver",
        ]);
    }

    public function store(Request $request)
    {
        $validate = Validator::make($request->all(), [
            "titre" => 'required|String',
            "description" => 'required|String',
            "contenu" => 'required|String',
            "matiere_id" => 'required|integerexists:matieres',
            "classe_id" => 'required|integerexists:classes',
            "enseignant_id" => 'required|integerexists:enseignants',
        ]);

        if ($validate->fails()) {
            return response()->json([
                "status" => "Echec",
                "message" => $validate->errors(),
            ], 400);
        }
        $cours = Cours::create([
            //dd($request->cours),
            "titre" => $request->titre,
            "description" => $request->description,
            "contenu" => $request->contenu,
            "matiere_id" => $request->matiere_id,
            "classe_id" => $request->classe_id,
            "enseignant_id" => $request->enseignant_id,

        ]);

        return response()->json([
            "status" => "Success",
            "message" => "Cours creer avec success",
            "data" => $cours,
        ]);
    }
    public function edit($id)
    {
        $cours = Cours::find($id);
        $coursEnseignant = Cours::with("enseignants")->find($id);

        if ($cours) {
            return response()->json([
                "status" => "Success",
                "message" => "Cours retrouver",
                "data" => $cours,
                "coursEnseignant" => $coursEnseignant
            ]);
        }
        return response()->json([
            "status" => "Echec",
            "message" => "Note non retrouver",
        ]);
    }

    public function update(Request $request, $id)
    {
        $validate = Validator::make($request->all(), [
            "titre" => 'required|String',
            "description" => 'required|String',
            "contenu" => 'required|String',
            "matiere_id" => 'required|integerexists:matieres',
            "classe_id" => 'required|integerexists:classes',
            "enseignant_id" => 'required|integerexists:enseignants',

        ]);
        if ($validate->fails()) {
            return response()->json([
                "status" => "Echoué",
                "message" => $validate->errors(),
            ]);
        }

        $coursUpdate = Cours::where("id", "=", $id)->get()->first();

        if (!$coursUpdate) {
            return response()->json([
                "status" => "Echoué",
                "message" => "Aucun cours trouver avec cet id",
            ], 400);
        }

        if ($coursUpdate) {
            $coursUpdate->update([
                "titre" => $request->titre,
                "description" => $request->description,
                "contenu" => $request->contenu,
                "matiere_id" => $request->matiere_id,
                "classe_id" => $request->classe_id,
                "enseignant_id" => $request->enseignant_id,
            ]);

            return response()->json([
                "status" => "Success",
                "message" => " Cours modifier avec success",
                "data" => $coursUpdate,
            ]);
        }
    }
    public function destroy($id)
    {
        $cours = Cours::find($id);
        if ($id) {
            $cours->delete();

            return response()->json([
                "status" => "Success",
                "message" => " Cours supprimer avec success",
            ]);
        }
        return response()->json([
            "status" => "Echec",
            "message" => " Aucun Cours trouver avec cet id pour suppression",
        ]);
    }
}
