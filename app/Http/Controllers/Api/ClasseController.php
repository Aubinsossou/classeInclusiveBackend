<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Classe;
use Illuminate\Http\Request;
use Validator;

class ClasseController extends Controller
{

    public function index($ecole_id)
    {
        $classes = Classe::where("ecole_id",$ecole_id)->with("eleves","enseignant")->get();

            return response()->json([
                "status" => "Success",
                "message" => "listes des classes trouver",
                "data" => $classes,
            ]);

    }

    public function store(Request $request)
    {
        $validate = Validator::make($request->all(), [
            "name" => 'required|string',
            "ecole_id" => 'required|integer|exists:ecoles,id',
        ]);

        if ($validate->fails()) {
            return response()->json([
                "status" => "Echec",
                "message" => $validate->errors(),
            ], 400);
        }
        $classe = Classe::create([
            //dd($request->classe),
            "name" => $request->name,
            "ecole_id" => $request->ecole_id,
        ]);

        return response()->json([
            "status" => "Success",
            "message" => "Classe creer avec success",
            "data" => $classe,
        ]);
    }
    public function edit($id)
    {
        $classe = Classe::find($id);
        $classeEleves = Classe::with("eleves")->find($id);

        if ($classe) {
            return response()->json([
                "status" => "Success",
                "message" => "classe retrouver",
                "data" => $classe,
                "classeEleves" => $classeEleves
            ]);
        }
        return response()->json([
            "status" => "Echec",
            "message" => "Classe non retrouver",
        ]);
    }

    public function update(Request $request, $id)
    {
        $validate = Validator::make($request->all(), [
            "name" => 'required|string',
            "ecole_id" => 'required|integer|exists:ecoles,id',

        ]);
        if ($validate->fails()) {
            return response()->json([
                "status" => "Echoué",
                "message" => $validate->errors(),
            ]);
        }

        $classeUpdate = Classe::where("id", "=", $id)->get()->first();

        if (!$classeUpdate) {
            return response()->json([
                "status" => "Echoué",
                "message" => "Aucune Classe trouver avec cet id",
            ], 400);
        }

        if ($classeUpdate) {
            $classeUpdate->update([
                "name" => $request->name,
                "ecole_id" => $request->ecole_id,
            ]);

            return response()->json([
                "status" => "Success",
                "message" => " Classe modifier avec success",
                "data" => $classeUpdate,
            ]);
        }
    }
    public function destroy($id)
    {
        $classe = Classe::find($id);
        if ($id) {
            $classe->delete();

            return response()->json([
                "status" => "Success",
                "message" => " Classe supprimer avec success",
            ]);
        }
        return response()->json([
            "status" => "Echec",
            "message" => " Aucune Classe trouver avec cet id pour suppression",
        ]);
    }
}
