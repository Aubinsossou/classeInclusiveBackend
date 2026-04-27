<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cours;
use App\Models\RetourProjection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RetourProjectionController extends Controller
{

    public function index($enseignant_id)
    {
        $retoursProjections = Cours::where("enseignant_id",$enseignant_id)->with(['retoursProjections.eleve'])
            ->whereHas('retoursProjections')
            ->get();

        return response()->json([
            "status" => "Success",
            "message" => "Cours avec leurs bilans d'élèves",
            "data" => ["cours" => $retoursProjections ],
        ]);
    }

    public function store(Request $request)
    {
        $validate = Validator::make($request->all(), [
            "apprentissage" => 'required|string',
            "methode_apprentissage" => 'required|string',
            "difficultes" => 'required|string',
            "application_future" => 'required|string',
            "eleve_id" => 'required|integer|exists:eleves,id',
            "cours_id" => 'required|integer|exists:cours,id',
        ]);

        if ($validate->fails()) {
            return response()->json([
                "status" => "Echec",
                "message" => $validate->errors(),
            ], 400);
        }
        $retouProjection = RetourProjection::create([
            //dd($request->classe),
            "apprentissage" => $request->apprentissage,
            "methode_apprentissage" => $request->methode_apprentissage,
            "difficultes" => $request->difficultes,
            "application_future" => $request->application_future,
            "eleve_id" => $request->eleve_id,
            "cours_id" => $request->cours_id,
        ]);

        return response()->json([
            "status" => "Success",
            "message" => "Retour et projection fait avec success",
            "data" => $retouProjection,
        ]);
    }
}
