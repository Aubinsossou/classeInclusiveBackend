<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\eleveHandicap;
use Illuminate\Http\Request;
use Validator;

class CleveHandicapController extends Controller
{
     public function index()
    {
        $eleveHandicap = eleveHandicap::all();
        if (count($eleveHandicap) !== 0) {
            return response()->json([
                "status" => "Success",
                "message" => "listes des eleves et leurs handicapes trouver",
                "data" => $eleveHandicap,
            ]);
        }
        return response()->json([
            "status" => "Echec",
            "message" => "listes des eleves et leurs handicapes trouver",
        ]);
    }

    public function store(Request $request)
    {
        $validate = Validator::make($request->all(), [
            "eleve_id" => 'required|integer|exists:eleves',
            "handicap_id" => 'required|integer|exists:handicaps',
        ]);

        if ($validate->fails()) {
            return response()->json([
                "status" => "Echec",
                "message" => $validate->errors(),
            ], 400);
        }

        try {
          $elevesHandicap =  eleveHandicap::create([
                'eleve_id' => $request->eleve_id,
                'handicap_id' => $request->handicap_id,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'message' => 'Déjà existant'
            ], 409);
        }

        return response()->json([
            "status" => "Success",
            "message" => "eleveHandicape creer avec success",
            "data" => $elevesHandicap,
        ]);
    }
}
