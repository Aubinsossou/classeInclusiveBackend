<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Matiere;
use Illuminate\Http\Request;
use Validator;

class MatiereController extends Controller
{
    public function index()
    {
        $matieres = Matiere::all();
        if (count($matieres) !== 0) {
            return response()->json([
                "status" => "Success",
                "message" => "listes des matieres trouver",
                "data" => $matieres,
            ]);
        }
        return response()->json([
            "status" => "Echec",
            "message" => "listes des matiere non trouver",
        ]);
    }

    public function store(Request $request)
    {
        $validate = Validator::make($request->all(), [
           "name" => 'required|string',
           "ecole_id" => 'required|integer|exists:ecoles'
        ]);

        if ($validate->fails()) {
            return response()->json([
                "status" => "Echec",
                "message" => $validate->errors(),
            ], 400);
        }
        $matiere = Matiere::create([
            //dd($request->matiere),
            "name" => $request->name,
            "ecole_id" => $request->ecole_id
        ]);

        return response()->json([
            "status" => "Success",
            "message" => "Matiere creer avec success",
            "data" => $matiere,
        ]);
    }
    public function edit($id)
    {
        $matiere = Matiere::find($id);
        $matiereCours = Matiere::with("cours")->find($id);

        if ($matiere) {
            return response()->json([
                "status" => "Success",
                "message" => "Matiere retrouver",
                "data" => $matiere,
                "matiereCours" => $matiereCours
            ]);
        }
        return response()->json([
            "status" => "Echec",
            "message" => "matiere non retrouver",
        ]);
    }

    public function update(Request $request, $id)
    {
        $validate = Validator::make($request->all(), [
                      "name" => 'required|string',
           "ecole_id" => 'required|integer|exists:ecoles'


        ]);
        if ($validate->fails()) {
            return response()->json([
                "status" => "Echoué",
                "message" => $validate->errors(),
            ]);
        }

        $matiereUpdate = Matiere::where("id", "=", $id)->get()->first();

        if (!$matiereUpdate) {
            return response()->json([
                "status" => "Echoué",
                "message" => "Aucune Matiere trouver avec cet id",
            ], 400);
        }

        if ($matiereUpdate) {
            $matiereUpdate->update([
                "name" => $request->name,
                "ecole_id" => $request->ecole_id,
            ]);

            return response()->json([
                "status" => "Success",
                "message" => " Matiere modifier avec success",
                "data" => $matiereUpdate,
            ]);
        }
    }
    public function destroy($id)
    {
        $matiere = Matiere::find($id);
        if ($id) {
            $matiere->delete();

            return response()->json([
                "status" => "Success",
                "message" => " Matiere supprimer avec success",
            ]);
        }
        return response()->json([
            "status" => "Echec",
            "message" => " Aucune Matiere trouver avec cet id pour suppression",
        ]);
    }
}
