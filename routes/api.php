<?php

use App\Http\Controllers\Api\ClasseController;
use App\Http\Controllers\Api\CoursController;
use App\Http\Controllers\Api\EcoleController;
use App\Http\Controllers\Api\EleveController;
use App\Http\Controllers\Api\EnseignantController;
use App\Http\Controllers\Api\HandicapController;
use App\Http\Controllers\Api\MatiereController;
use App\Http\Controllers\Api\QuestionController;
use App\Http\Controllers\Api\QuizController;
use App\Http\Controllers\Api\ReponseController;
use App\Http\Controllers\NoteController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:api');

Route::post("/loginEleve", "loginEleve")->prefix("loginEleve")->controller(EleveController::class);
Route::post("/loginEleve", "loginEnseignant")->prefix("loginEnseignant")->controller(EnseignantController::class);
Route::post('/registerEcole',"registerEcole")->prefix("registerEnseignant")->controller(EnseignantController::class);
Route::post("/loginEcole", "loginEcole")->prefix("loginEcole")->controller(EcoleController::class);

Route::middleware('auth:ecole_api')->prefix("/v1/ecole")->controller(EcoleController::class)->group(function () {
    Route::get("/index", "index");
    Route::get("/getEcole", "getEcole");
    Route::delete("/logout", "logout");
});

Route::middleware("auth:ecole_api")->prefix("/v1/classe")->controller(ClasseController::class)->group(function () {
    Route::get("/index", "index");
    Route::post("/store", "store");
    Route::get("/edit", "edit");
    Route::post("/update", "update");
    Route::delete("/destroy", "destroy");
});

Route::middleware("auth:ecole_api")->prefix("/v1/matiere")->controller(MatiereController::class)->group(function () {
    Route::get("/index", "index");
    Route::post("/store", "store");
    Route::get("/edit", "edit");
    Route::post("/update", "update");
    Route::delete("/destroy", "destroy");
});

Route::middleware("auth:ecole_api")->prefix("/v1/eleve")->controller(EleveController::class)->group(function () {
    Route::get("/index", "index");
    Route::post('/registerEleve',"registerEleve");
    Route::get("/getEleve", "getEleve");
    Route::delete("/logout", "logout");
});

Route::middleware("auth:ecole_api")->prefix("/v1/enseignant")->controller(EnseignantController::class)->group(function () {
    Route::get("/index", "index");
    Route::post('/registerEnseignant',"registerEnseignant");
    Route::post("/loginEnseignant", "loginEnseignant");
    Route::get("/getEnseignant", "getEnseignant");
    Route::delete("/logout", "logout");
});

Route::middleware("auth:ecole_api")->prefix("/v1/handicap")->controller(HandicapController::class)->group(function () {
    Route::get("/index", "index");
    Route::post("/store", "store");
    Route::get("/edit", "edit");
    Route::post("/update", "update");
    Route::delete("/destroy", "destroy");
});

Route::middleware("auth:enseignant_api")->prefix("/v1/cours")->controller(CoursController::class)->group(function () {
    Route::get("/index", "index");
    Route::post("/store", "store");
    Route::get("/edit", "edit");
    Route::post("/update", "update");
    Route::delete("/destroy", "destroy");
});

Route::middleware("auth:enseignant_api")->prefix("/v1/question")->controller(QuestionController::class)->group(function () {
    Route::get("/index", "index");
    Route::post("/store", "store");
    Route::get("/edit", "edit");
    Route::post("/update", "update");
    Route::delete("/destroy", "destroy");
});

Route::middleware("auth:enseignant_api")->prefix("/v1/quiz")->controller(QuizController::class)->group(function () {
    Route::get("/index", "index");
    Route::post("/store", "store");
    Route::get("/edit", "edit");
    Route::post("/update", "update");
    Route::delete("/destroy", "destroy");
});

Route::middleware(['auth:enseignant_api'])->group(function () {
    Route::get("/indexNote", 'index')->controller(NoteController::class);
    Route::get("/indexEleve", 'index')->controller(EleveController::class);
});

Route::middleware("auth:eleve_api")->prefix("/v1/note")->controller(NoteController::class)->group(function () {
    Route::post("/store", "store");
    Route::get("/edit", "edit");
    Route::post("/update", "update");
    Route::delete("/destroy", "destroy");
});


