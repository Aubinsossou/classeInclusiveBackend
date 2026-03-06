<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Question;
use Illuminate\Http\Request;
use Validator;

class QuestionController extends Controller
{
    public function index()
    {
        $questions = Question::all();
        return response()->json([
            "status" => "Success",
            "message" => "listes des Questions trouver",
            "data" => $questions,
        ]);
    }

    public function store(Request $request)
    {
        $validate = Validator::make($request->all(), [
            "question" => "required|string|max:1000",
            "quiz_id" => "required|integer|exists:quizzes,id",
        ]);

        if ($validate->fails()) {
            return response()->json([
                "status" => "Echec",
                "message" => $validate->errors(),
            ], 400);
        }
        $question = Question::create([
            //dd($request->question),
            "question" => $request->question,
            "quiz_id" => $request->quiz_id,
        ]);

        return response()->json([
            "status" => "Success",
            "message" => "Question creer avec success",
            "data" => $question,
        ]);
    }
    public function edit($id)
    {
        $question = Question::find($id);
        $questionReponse = Question::with("reponses")->find($id);

        if ($question) {
            return response()->json([
                "status" => "Success",
                "message" => "Question retrouver",
                "data" => $question,
                "questionReponse" => $questionReponse
            ]);
        }
        return response()->json([
            "status" => "Echec",
            "message" => "Question non retrouver",
        ]);
    }

    public function update(Request $request, $id)
    {
        $validate = Validator::make($request->all(), [
            "question" => "required|string|max:1000",
            "quiz_id" => "required|integer",
        ]);
        if ($validate->fails()) {
            return response()->json([
                "status" => "Echoué",
                "message" => $validate->errors(),
            ]);
        }

        $questionUpdate = Question::where("id", "=", $id)->get()->first();

        if (!$questionUpdate) {
            return response()->json([
                "status" => "Echoué",
                "message" => "Aucune Question trouver avec cet id",
            ], 400);
        }

        if ($questionUpdate) {
            $questionUpdate->update([
                "question" => $request->name,
                "quiz_id" => $request->quiz_id,
            ]);

            return response()->json([
                "status" => "Success",
                "message" => " Question modifier avec success",
                "data" => $questionUpdate,
            ]);
        }
    }
    public function destroy($id)
    {
        $question = Question::find($id);
        if ($id) {
            $question->delete();

            return response()->json([
                "status" => "Success",
                "message" => " Question supprimer avec success",
            ]);
        }
        return response()->json([
            "status" => "Echec",
            "message" => " Aucune Question trouver avec cet id pour suppression",
        ]);
    }

}
