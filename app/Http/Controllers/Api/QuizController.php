<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use Illuminate\Http\Request;
use Validator;

class QuizController extends Controller
{
    public function index()
    {
        $quiz = Quiz::all();
        if (count($quiz) !== 0) {
            return response()->json([
                "status" => "Success",
                "message" => "listes des quiz trouver",
                "data" => $quiz,
            ]);
        }
        return response()->json([
            "status" => "Echec",
            "message" => "listes des quiz non trouver",
        ]);
    }

    public function store(Request $request)
    {

        $validate = Validator::make($request->all(), [
            "name" => 'required|string',
            "cours_id" => 'required|integer',
        ]);

        if ($validate->fails()) {
            return response()->json([
                "status" => "Echec",
                "message" => $validate->errors(),
            ], 400);
        }

        Quiz::create([
            'name' => $request->name,
            'cours_id' => $request->cours_id,
        ]);

        return response()->json([
            "status" => "Success",
            "message" => "Réponses créées avec succès",
            "data" => $request->listReponse,
        ]);
    }
    public function edit($id)
    {
        $quiz = Quiz::find($id);
        if ($quiz) {
            return response()->json([
                "status" => "Success",
                "message" => "Quiz retrouver",
                "data" => $quiz,
            ]);
        }
        return response()->json([
            "status" => "Echec",
            "message" => "Quiz non retrouver",
        ]);
    }

    public function update(Request $request, $id)
    {
        $validate = Validator::make($request->all(), [
            "name" => 'required|string',
            "cours_id" => 'required|integer',
        ]);
        if ($validate->fails()) {
            return response()->json([
                "status" => "Echoué",
                "message" => $validate->errors(),
            ]);
        }

        $quizUpdate = Quiz::where("id", "=", $id)->get()->first();

        if (!$quizUpdate) {
            return response()->json([
                "status" => "Echoué",
                "message" => "Aucun Quiz trouver avec cet id",
            ], 400);
        }

        if ($quizUpdate) {
            $quizUpdate->update([
                "name" => $request->name,
                "cours_id" => $request->cours_id,
            ]);

            return response()->json([
                "status" => "Success",
                "message" => " Quiz modifier avec success",
                "data" => $quizUpdate,
            ]);
        }
    }
    public function destroy($id)
    {
        $quiz = Quiz::find($id);
        if ($id) {
            $quiz->delete();

            return response()->json([
                "status" => "Success",
                "message" => " Quiz supprimer avec success",
            ]);
        }
        return response()->json([
            "status" => "Echec",
            "message" => " Aucun quiz trouver avec cet id pour suppression",
        ], 400);
    }
}
