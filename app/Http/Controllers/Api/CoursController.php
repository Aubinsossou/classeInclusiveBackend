<?php

namespace App\Http\Controllers\Api;

use App\Models\Cours;
use App\Models\CoursMedias;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class CoursController extends Controller
{

    public function index()
    {
        $enseignant = Auth::guard('enseignant_api')->user();

        $cours = Cours::with('medias')
            ->where('enseignant_id', $enseignant->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Liste des cours',
            'data' => $cours,
        ]);
    }


    public function store(Request $request)
    {
        $enseignant = Auth::guard('enseignant_api')->user();

        $validate = validator($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'contenu' => 'nullable|string',
            'matiere_id' => 'required|exists:matieres,id',
            'classe_id' => 'required|exists:classes,id',
            'is_published' => 'nullable',
            'date_programmation' => 'nullable|date',
            'medias_files.*' => 'nullable|file|max:102400',
            'medias_types.*' => 'nullable|string|in:image,video,audio',
            'medias_ordres.*' => 'nullable|integer',
        ]);

        if ($validate->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validate->errors(),
                'message' => 'Validation échouée',
            ], 422);
        }

        $cours = Cours::create([
            'title' => $request->title,
            'description' => $request->description,
            'contenu' => $request->contenu,
            'matiere_id' => $request->matiere_id,
            'classe_id' => $request->classe_id,
            'is_published' => filter_var($request->is_published, FILTER_VALIDATE_BOOLEAN),
            'date_programmation' => $request->date_programmation,
            'enseignant_id' => $enseignant->id,
        ]);

        // Upload des médias
        if ($request->hasFile('medias_files')) {
            $this->handleMediasUpload($request, $cours->id);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Cours créé avec succès',
            'data' => $cours->load('medias'),
        ], 201);
    }


    public function edit($id)
    {
        $cours = Cours::with('medias')->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'message' => 'Cours trouvé',
            'data' => $cours,
        ]);
    }


    public function update(Request $request, $id)
    {
        $cours = Cours::findOrFail($id);

        $validate = validator($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'contenu' => 'nullable|string',
            'matiere_id' => 'required|exists:matieres,id',
            'classe_id' => 'required|exists:classes,id',
            'is_published' => 'nullable',
            'date_programmation' => 'nullable|date',
            'medias_files.*' => 'nullable|file|max:102400',
            'medias_types.*' => 'nullable|string|in:image,video,audio',
            'medias_ordres.*' => 'nullable|integer',
        ]);

        if ($validate->fails()) {
            return response()->json([
                'status' => 'errors',
                'errors' => $validate->errors(),
                'message' => 'Validation échouée',
            ], 422);
        }

        $cours->update([
            'title' => $request->title,
            'description' => $request->description,
            'contenu' => $request->contenu,
            'matiere_id' => $request->matiere_id,
            'classe_id' => $request->classe_id,
            'is_published' => filter_var($request->is_published, FILTER_VALIDATE_BOOLEAN),
            'date_programmation' => $request->date_programmation,
        ]);

        // Upload de nouveaux médias
        if ($request->hasFile('medias_files')) {
            $this->handleMediasUpload($request, $cours->id);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Cours mis à jour',
            'data' => $cours->load('medias'),
        ]);
    }

    public function destroy($id)
    {
        $cours = Cours::with('medias')->findOrFail($id);

        // Supprimer tous les fichiers médias du storage
        foreach ($cours->medias as $media) {
            $this->deleteMediaFile($media->path);
        }

        $cours->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Cours supprimé',
        ]);
    }


    public function destroyMedia($id)
    {
        $media = CoursMedias::findOrFail($id);

        $this->deleteMediaFile($media->path);
        $media->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Média supprimé',
        ]);
    }

    // ─────────────────────────────────────────────
    // HELPER — Upload des médias
    // ─────────────────────────────────────────────
    private function handleMediasUpload(Request $request, int $coursId): void
    {
        foreach ($request->file('medias_files') as $index => $file) {
            if (!$file || !$file->isValid())
                continue;

            $type = $request->input("medias_types.{$index}", 'image');
            $ordre = $request->input("medias_ordres.{$index}", $index);

            if ($type === 'image') {
                $filename = Str::uuid() . '.jpg';
                $path = 'cours/images/' . $filename;

                $manager = new ImageManager(new Driver());
                $compressed = $manager->read($file->getRealPath())
                    ->scaleDown(width: 1280)
                    ->toJpeg(quality: 80);

                Storage::disk('public')->put($path, $compressed);

            } else {
                $folder = $type === 'video' ? 'cours/videos' : 'cours/audios';
                $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs($folder, $filename, 'public');
            }

            $url = Storage::url($path);

            CoursMedias::create([
                'cours_id' => $coursId,
                'type' => $type,
                'url' => $url,
                'path' => $path,
                'ordre' => $ordre,
            ]);
        }
    }


    private function deleteMediaFile(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
