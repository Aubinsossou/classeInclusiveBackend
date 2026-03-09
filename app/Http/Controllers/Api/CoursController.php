<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cours;
use App\Models\CoursMedias;
use Auth;
use Illuminate\Http\Request;
use Validator;

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

        $cours = Cours::create([
            'title'         => $request->title,
            'description'   => $request->description,
            'contenu'       => $request->contenu,
            'matiere_id'    => $request->matiere_id,
            'classe_id'     => $request->classe_id,
            'is_published'  => filter_var($request->is_published, FILTER_VALIDATE_BOOLEAN),
            'enseignant_id' => $enseignant->id,
        ]);

        if ($request->hasFile('medias_files')) {
            foreach ($request->file('medias_files') as $index => $file) {
                $type  = $request->input("medias_types.{$index}");
                $ordre = $request->input("medias_ordres.{$index}", $index);

                if (!$file || !$file->isValid()) continue;

                $folder       = 'classe_inclusive/cours/' . $type . 's';
                $resourceType = $type === 'image' ? 'image' : 'video';

                $result = app(\Cloudinary\Cloudinary::class)->uploadApi()->upload(
                    $file->getRealPath(),
                    [
                        'folder'        => $folder,
                        'resource_type' => $resourceType,
                    ]
                );

                CoursMedias::create([
                    'cours_id'  => $cours->id,
                    'type'      => $type,
                    'url'       => $result['secure_url'],
                    'public_id' => $result['public_id'],
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

        if ($request->hasFile('medias_files')) {
            foreach ($request->file('medias_files') as $index => $file) {
                $type  = $request->input("medias_types.{$index}");
                $ordre = $request->input("medias_ordres.{$index}", $cours->medias()->count() + $index);

                if (!$file || !$file->isValid()) continue;

                $folder       = 'classe_inclusive/cours/' . $type . 's';
                $resourceType = $type === 'image' ? 'image' : 'video';

                $result = app(\Cloudinary\Cloudinary::class)->uploadApi()->upload(
                    $file->getRealPath(),
                    [
                        'folder'        => $folder,
                        'resource_type' => $resourceType,
                    ]
                );

                CoursMedias::create([
                    'cours_id'  => $cours->id,
                    'type'      => $type,
                    'url'       => $result['secure_url'],
                    'public_id' => $result['public_id'],
                    'ordre'     => $cours->medias()->count() + $index,
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
            app(\Cloudinary\Cloudinary::class)->uploadApi()->destroy($media->public_id);
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
        app(\Cloudinary\Cloudinary::class)->uploadApi()->destroy($media->public_id);
        $media->delete();

        return response()->json([
            'status'  => 'Success',
            'message' => 'Média supprimé avec succès',
        ]);
    }
}
