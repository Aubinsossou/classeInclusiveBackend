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

Route::prefix('/v1/eleve')->group(function () {
    Route::post('/login', [EleveController::class, 'loginEleve']);
});

Route::prefix('/v1/enseignant')->group(function () {
    Route::post('/login', [EnseignantController::class, 'loginEnseignant']);
    Route::post('/register', [EnseignantController::class, 'registerEnseignant']);
});


Route::prefix('/v1/ecole')->group(function () {
    Route::post('/login', [EcoleController::class, 'loginEcole']);
    Route::post('/register', [EcoleController::class, 'registerEcole']);
});

Route::middleware('auth:ecole_api')->prefix("/v1/ecole")->controller(EcoleController::class)->group(function () {
    Route::get("/index", "index");
    Route::get("/getEcole", "getEcole");
    Route::delete("/logout", "logout");
});

Route::middleware("auth:ecole_api")->prefix("/v1/ecole/classe")->controller(ClasseController::class)->group(function () {
    Route::get("/index", "index");
    Route::post("/store", "store");
    Route::get("/edit/{id}", "edit");
    Route::put("/update/{id}", "update");
    Route::delete("/destroy/{id}", "destroy");
});

Route::middleware("auth:ecole_api")->prefix("/v1/matiere")->controller(MatiereController::class)->group(function () {
    Route::get("/index", "index");
    Route::post("/store", "store");
    Route::get("/edit/{id}", "edit");
    Route::post("/update/{id}", "update");
    Route::delete("/destroy/{id}", "destroy");
});

Route::middleware("auth:ecole_api")->prefix("/v1/ecole/eleve")->controller(EleveController::class)->group(function () {
    Route::get("/index", "index");
    Route::post('/registerEleve', "registerEleve");
    Route::get("/getEleve", "getEleve");
    Route::delete("/logout", "logout");
});

Route::middleware("auth:ecole_api")->prefix("/v1/ecole/enseignant")->controller(EnseignantController::class)->group(function () {
    Route::get("/index", "index");
    Route::get("/edit/{id}", "edit");
    Route::get("/update/{id}", "update");
    Route::post('/registerEnseignant', "registerEnseignant");
    Route::post("/loginEnseignant", "loginEnseignant");
    Route::get("/getEnseignant", "getEnseignant");
    Route::delete("/logout", "logout");
});

Route::middleware("auth:ecole_api")->prefix("/v1/ecole/handicap")->controller(HandicapController::class)->group(function () {
    Route::get("/index", "index");
    Route::post("/store", "store");
    Route::get("/edit/{id}", "edit");
    Route::post("/update/{id}", "update");
    Route::delete("/destroy/{id}", "destroy");
});

Route::middleware("auth:enseignant_api")->prefix("/v1/cours")->controller(CoursController::class)->group(function () {
    Route::get("/index", "index");
    Route::post("/store", "store");
    Route::get("/edit/{id}", "edit");
    Route::post("/update/{id}", "update");
    Route::delete("/destroy/{id}", "destroy");
});

Route::middleware("auth:enseignant_api")->prefix("/v1/quiz")->controller(QuizController::class)->group(function () {
    Route::get("/index", "index");
    Route::post("/store", "store");
    Route::get("/edit/{id}", "edit");
    Route::post("/update/{id}", "update");
    Route::delete("/destroy/{id}", "destroy");
});

Route::middleware("auth:enseignant_api")->prefix("/v1/question")->controller(QuestionController::class)->group(function () {
    Route::get("/index", "index");
    Route::post("/store", "store");
    Route::get("/edit/{id}", "edit");
    Route::post("/update/{id}", "update");
    Route::delete("/destroy/{id}", "destroy");
});

Route::middleware("auth:enseignant_api")->prefix("/v1/reponse")->controller(ReponseController::class)->group(function () {
    Route::get("/index", "index");
    Route::post("/store", "store");
    Route::get("/edit/{id}", "edit");
    Route::post("/update/{id}", "update");
    Route::delete("/destroy/{id}", "destroy");
});


Route::middleware(['auth:enseignant_api'])->group(function () {
    Route::get("/indexNote", [NoteController::class, 'index']);
    Route::get("/indexEleve", [EleveController::class, 'index']);
});

Route::middleware("auth:eleve_api")->prefix("/v1/note")->controller(NoteController::class)->group(function () {
    Route::post("/store", "store");
    Route::get("/edit/{id}", "edit");
    Route::post("/update/{id}", "update");
    Route::delete("/destroy/{id}", "destroy");
});


