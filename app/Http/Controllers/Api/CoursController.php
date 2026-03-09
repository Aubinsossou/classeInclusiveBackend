<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cours;
use App\Models\CoursMedias;
use Auth;
use Illuminate\Http\Request;
use Validator;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class CoursController extends Controller
{
    public function index(Request $request)
    {
        $enseignant = Auth::guard('enseignant_api')->user();
        $cours = Cours::where('enseignant_id', $enseignant->id)
            ->with(['medias', 'matiere', 'quizzes'])
            ->get();
        return response()->json([
            'status' => 'Success',
            'data'   => $cours,
        ]);
    }

    public function edit($id)
    {
        $enseignant = Auth::guard('enseignant_api')->user();
        $cours = Cours::where('id', $id)
            ->where('enseignant_id', $enseignant->id)
            ->with(['medias', 'matiere', 'quizzes.questions.reponses'])
            ->firstOrFail();
        return response()->json([
            'status' => 'Success',
            'data'   => $cours,
        ]);
    }

    public function store(Request $request)
    {
        $enseignant = Auth::guard('enseignant_api')->user();

        $validate = Validator::make($request->all(), [
            'title'        => 'required|string|max:255',
            'matiere_id'   => 'required|exists:matieres,id',
            'classe_id'    => 'nullable|exists:classes,id',
            'contenu'      => 'nullable|string',
            'description'  => 'nullable|string',
            'is_published' => 'nullable',
        ]);

        if ($validate->fails()) {
            return response()->json([
                'errors'  => $validate->errors(),
                'message' => 'Validation échoué',
            ], 422);
        }

        // Validation manuelle des médias (Laravel gère mal medias.*.file avec multipart)
        if ($request->hasFile('medias')) {
            foreach ($request->file('medias') as $index => $item) {
                if (!isset($item['file']) || !$item['file']->isValid()) {
                    return response()->json([
                        'errors'  => ["medias.$index.file" => ['Fichier invalide ou manquant.']],
                        'message' => 'Validation échoué',
                    ], 422);
                }
                if (!isset($item['type']) || !in_array($item['type'], ['video', 'image', 'audio'])) {
                    return response()->json([
                        'errors'  => ["medias.$index.type" => ['Type invalide.']],
                        'message' => 'Validation échoué',
                    ], 422);
                }
            }
        }

        $cours = Cours::create([
            'title'         => $request->title,
            'description'   => $request->description,
            'contenu'       => $request->contenu,
            'matiere_id'    => $request->matiere_id,
            'classe_id'     => $request->classe_id,
            'is_published'  => filter_var($request->is_published, FILTER_VALIDATE_BOOLEAN),
            'enseignant_id' => $enseignant->id,
        ]);

        // Upload médias sur Cloudinary
        if ($request->hasFile('medias')) {
            foreach ($request->file('medias') as $index => $item) {
                $type         = $request->input("medias.$index.type");
                $ordre        = $request->input("medias.$index.ordre", $index);
                $folder       = 'classe_inclusive/cours/' . $type . 's';
                $resourceType = $type === 'image' ? 'image' : 'video';

                $result = Cloudinary::upload($item['file']->getRealPath(), [
                    'folder'        => $folder,
                    'resource_type' => $resourceType,
                ]);

                CoursMedias::create([
                    'cours_id'  => $cours->id,
                    'type'      => $type,
                    'url'       => $result->getSecurePath(),
                    'public_id' => $result->getPublicId(),
                    'ordre'     => $ordre,
                ]);
            }
        }

        return response()->json([
            'status'  => 'Success',
            'message' => 'Cours créé avec succès',
            'data'    => $cours->load('medias'),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $enseignant = Auth::guard('enseignant_api')->user();
        $cours = Cours::where('id', $id)
            ->where('enseignant_id', $enseignant->id)
            ->firstOrFail();

        if ($request->hasFile('medias')) {
            foreach ($request->file('medias') as $index => $item) {
                if (!isset($item['file']) || !$item['file']->isValid()) {
                    return response()->json([
                        'errors'  => ["medias.$index.file" => ['Fichier invalide ou manquant.']],
                        'message' => 'Validation échoué',
                    ], 422);
                }
            }
        }

        $cours->update([
            'title'        => $request->title        ?? $cours->title,
            'description'  => $request->description  ?? $cours->description,
            'contenu'      => $request->contenu       ?? $cours->contenu,
            'matiere_id'   => $request->matiere_id   ?? $cours->matiere_id,
            'classe_id'    => $request->classe_id    ?? $cours->classe_id,
            'is_published' => $request->has('is_published')
                ? filter_var($request->is_published, FILTER_VALIDATE_BOOLEAN)
                : $cours->is_published,
        ]);

        if ($request->hasFile('medias')) {
            foreach ($request->file('medias') as $index => $item) {
                $type         = $request->input("medias.$index.type");
                $ordre        = $request->input("medias.$index.ordre", $cours->medias()->count() + $index);
                $folder       = 'classe_inclusive/cours/' . $type . 's';
                $resourceType = $type === 'image' ? 'image' : 'video';

                $result = Cloudinary::upload($item['file']->getRealPath(), [
                    'folder'        => $folder,
                    'resource_type' => $resourceType,
                ]);

                CoursMedias::create([
                    'cours_id'  => $cours->id,
                    'type'      => $type,
                    'url'       => $result->getSecurePath(),
                    'public_id' => $result->getPublicId(),
                    'ordre'     => $ordre,
                ]);
            }
        }

        return response()->json([
            'status'  => 'Success',
            'message' => 'Cours mis à jour',
            'data'    => $cours->load('medias'),
        ]);
    }

    public function destroy($id)
    {
        $enseignant = Auth::guard('enseignant_api')->user();
        $cours = Cours::where('id', $id)
            ->where('enseignant_id', $enseignant->id)
            ->with('medias')
            ->firstOrFail();

        foreach ($cours->medias as $media) {
            Cloudinary::destroy($media->public_id);
        }

        $cours->delete();

        return response()->json([
            'status'  => 'Success',
            'message' => 'Cours supprimé avec succès',
        ]);
    }

    public function destroyMedia($mediaId)
    {
        $media = CoursMedias::findOrFail($mediaId);
        Cloudinary::destroy($media->public_id);
        $media->delete();

        return response()->json([
            'status'  => 'Success',
            'message' => 'Média supprimé avec succès',
        ]);
    }
}
