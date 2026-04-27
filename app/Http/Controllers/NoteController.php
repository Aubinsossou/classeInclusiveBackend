<?php

namespace App\Http\Controllers;

use App\Models\Note;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NoteController extends Controller
{
    public function index()
    {
        $notes = Note::all();
        return response()->json([
            "status" => "Success",
            "message" => "listes des notes trouver",
            "data" => $notes,
        ]);

    }

    public function store(Request $request)
    {
        $validate = Validator::make($request->all(), [
            "note" => 'required|integer',
            "eleve_id" => 'required|integer|exists:eleves,id',
            "quiz_id" => 'required|integer|exists:quizzes,id',
        ]);

        if ($validate->fails()) {
            return response()->json([
                "status" => "Echec",
                "message" => $validate->errors(),
            ], 400);
        }
        $note = Note::create([
            //dd($request->note),
            "note" => $request->note,
            "eleve_id" => $request->eleve_id,
            "quiz_id" => $request->quiz_id,
        ]);

        return response()->json([
            "status" => "Success",
            "message" => "Note creer avec success",
            "data" => $note,
        ]);
    }
    public function edit($id)
    {
        $note = Note::find($id);
        $noteEleves = Note::with("eleves")->find($id);

        if ($note) {
            return response()->json([
                "status" => "Success",
                "message" => "Note retrouver",
                "data" => $note,
                "noteEleves" => $noteEleves
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
            "note" => 'required|number',
            "eleve_id" => 'required|integer|exists:eleves',
            "quiz_id" => 'required|integerexists:quizzes',

        ]);
        if ($validate->fails()) {
            return response()->json([
                "status" => "Echoué",
                "message" => $validate->errors(),
            ]);
        }

        $noteUpdate = Note::where("id", "=", $id)->get()->first();

        if (!$noteUpdate) {
            return response()->json([
                "status" => "Echoué",
                "message" => "Aucune Note trouver avec cet id",
            ], 400);
        }

        if ($noteUpdate) {
            $noteUpdate->update([
                "note" => $request->note,
                "eleve_id" => $request->eleve_id,
                "quiz_id" => $request->quiz_id,
            ]);

            return response()->json([
                "status" => "Success",
                "message" => " Note modifier avec success",
                "data" => $noteUpdate,
            ]);
        }
    }
    public function destroy($id)
    {
        $note = Note::find($id);
        if ($id) {
            $note->delete();

            return response()->json([
                "status" => "Success",
                "message" => " Note supprimer avec success",
            ]);
        }
        return response()->json([
            "status" => "Echec",
            "message" => " Aucune Note trouver avec cet id pour suppression",
        ]);
    }
}
