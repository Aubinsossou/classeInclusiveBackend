<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Handicap;
use Illuminate\Http\Request;
use Validator;

class HandicapController extends Controller
{
    public function index()
    {
        $handicapes = Handicap::all();

        return response()->json([
            "status" => "Success",
            "message" => "listes des handicapes trouver",
            "data" => $handicapes,
        ]);

    }

    public function store(Request $request)
    {
        $validate = Validator::make($request->all(), [
            "name" => 'required|string',
            "ecole_id" => 'required|integer|exists:ecoles,id'
        ]);

        if ($validate->fails()) {
            return response()->json([
                "status" => "Echec",
                "message" => $validate->errors(),
            ], 400);
        }
        $handicape = Handicap::create([
            //dd($request->handicape),
            "name" => $request->name,
            "ecole_id" => $request->ecole_id,
        ]);

        return response()->json([
            "status" => "Success",
            "message" => "handicape creer avec success",
            "data" => $handicape,
        ]);
    }
    public function edit($id)
    {
        $handicape = Handicap::find($id);
        $handicapeEleve = Handicap::with("eleves")->find($id);

        if ($handicape) {
            return response()->json([
                "status" => "Success",
                "message" => "handicape retrouver",
                "data" => $handicape,
                "handicapeEleve" => $handicapeEleve
            ]);
        }
        return response()->json([
            "status" => "Echec",
            "message" => "Handicape non retrouver",
        ]);
    }

    public function update(Request $request, $id)
    {
        $validate = Validator::make($request->all(), [
            "name" => 'required|string',
            "ecole_id" => 'required|integer|exists:ecoles,id'


        ]);
        if ($validate->fails()) {
            return response()->json([
                "status" => "Echoué",
                "message" => $validate->errors(),
            ]);
        }

        $handicapeUpdate = Handicap::where("id", "=", $id)->get()->first();

        if (!$handicapeUpdate) {
            return response()->json([
                "status" => "Echoué",
                "message" => "Aucune Handicape trouver avec cet id",
            ], 400);
        }

        if ($handicapeUpdate) {
            $handicapeUpdate->update([
                "name" => $request->name,
                "ecoleèid" => $request->ecoleèid,
            ]);

            return response()->json([
                "status" => "Success",
                "message" => " Handicape modifier avec success",
                "data" => $handicapeUpdate,
            ]);
        }
    }
    public function destroy($id)
    {
        $handicape = Handicap::find($id);
        if ($id) {
            $handicape->delete();

            return response()->json([
                "status" => "Success",
                "message" => " Handicape supprimer avec success",
            ]);
        }
        return response()->json([
            "status" => "Echec",
            "message" => " Aucun Handicape trouver avec cet id pour suppression",
        ]);
    }
}
