<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reponse;
use Illuminate\Http\Request;
use Validator;

class ReponseController extends Controller
{
     public function index()
    {
        $reponses = Reponse::all();
        if (count($reponses) !== 0) {
            return response()->json([
                "status" => "Success",
                "message" => "listes des Reponses trouver",
                "data" => $reponses,
            ]);
        }
        return response()->json([
            "status" => "Echec",
            "message" => "listes des Reponses non trouver",
        ]);
    }

public function store(Request $request)
{

    $validate = Validator::make($request->all(), [
        "listReponse" => 'required|array',
        "listReponse.*.name" => 'required|string',
        "listReponse.*.status" => 'required|string',
        "question_id" => 'required|integer'
    ]);

    if ($validate->fails()) {
        return response()->json([
            "status" => "Echec",
            "message" => $validate->errors(),
        ], 400);
    }

    foreach ($request->listReponse as $item) {
        Reponse::create([
            'name' => $item['name'],
            'status' => $item['status'],
            'question_id' => $request->question_id,
        ]);
    }

    return response()->json([
        "status" => "Success",
        "message" => "Réponses créées avec succès",
        "data" => $request->listReponse,
    ]);
}    public function edit($id)
    {
        $reponse = Reponse::find($id);
        if ($reponse) {
            return response()->json([
                "status" => "Success",
                "message" => "Reponse retrouver",
                "data" => $reponse,
            ]);
        }
        return response()->json([
            "status" => "Echec",
            "message" => "Reponse non retrouver",
        ]);
    }

    public function update(Request $request, $id)
    {
        $validate = Validator::make($request->all(), [
            "name" => "required|string|max:1000",
            "question_id" => "required|integer",
        ]);
        if ($validate->fails()) {
            return response()->json([
                "status" => "Echoué",
                "message" => $validate->errors(),
            ]);
        }

        $reponseUpdate = Reponse::where("id", "=", $id)->get()->first();

        if (!$reponseUpdate) {
            return response()->json([
                "status" => "Echoué",
                "message" => "Aucune Reponse trouver avec cet id",
            ], 400);
        }

        if ($reponseUpdate) {
            $reponseUpdate->update([
                "name" => $request->name,
                "status" => $request->status,
                "question_id" => $request->question_id,
            ]);

            return response()->json([
                "status" => "Success",
                "message" => " Reponse modifier avec success",
                "data" => $reponseUpdate,
            ]);
        }
    }
    public function destroy($id)
    {
        $reponse = Reponse::find($id);
        if ($id) {
            $reponse->delete();

            return response()->json([
                "status" => "Success",
                "message" => " Reponse supprimer avec success",
            ]);
        }
        return response()->json([
            "status" => "Echec",
            "message" => " Aucune Reponse trouver avec cet id pour suppression",
        ], 400);
    }
}
