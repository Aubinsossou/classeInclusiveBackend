<?php

namespace App\Http\Controllers\Api;

use App\Models\Cours;
use App\Models\CoursMedias;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
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
            'status'  => 'success',
            'message' => 'Liste des cours',
            'data'    => $cours,
        ]);
    }

    public function store(Request $request)
    {
        $enseignant = Auth::guard('enseignant_api')->user();

        $validate = validator($request->all(), [
            'title'              => 'required|string|max:255',
            'description'        => 'nullable|string',
            'contenu'            => 'nullable|string',
            'matiere_id'         => 'required|exists:matieres,id',
            'classe_id'          => 'required|exists:classes,id',
            'is_published'       => 'nullable',
            'date_programmation' => 'nullable|date',
            'medias_files.*'     => 'nullable|mimes:jpg,jpeg,png,gif,webp,mp4,mov,avi,webm,mkv,mp3,wav,ogg,m4a,aac,flac|max:102400',
            'medias_types.*'     => 'nullable|string|in:image,video,audio',
            'medias_ordres.*'    => 'nullable|integer',
        ]);

        if ($validate->fails()) {
            return response()->json([
                'status'  => 'error',
                'errors'  => $validate->errors(),
                'message' => 'Validation échouée',
            ], 422);
        }

        $cours = Cours::create([
            'title'              => $request->title,
            'description'        => $request->description,
            'contenu'            => $request->contenu,
            'matiere_id'         => $request->matiere_id,
            'classe_id'          => $request->classe_id,
            'is_published'       => filter_var($request->is_published, FILTER_VALIDATE_BOOLEAN),
            'date_programmation' => $request->date_programmation,
            'enseignant_id'      => $enseignant->id,
        ]);

        if ($request->hasFile('medias_files')) {
            $this->handleMediasUpload($request, $cours->id);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Cours créé avec succès',
            'data'    => $cours->load('medias'),
        ], 201);
    }

    public function edit($id)
    {
        $cours = Cours::with('medias')->findOrFail($id);

        return response()->json([
            'status'  => 'success',
            'message' => 'Cours trouvé',
            'data'    => $cours,
        ]);
    }

    public function update(Request $request, $id)
    {
        $cours = Cours::findOrFail($id);

        $validate = validator($request->all(), [
            'title'              => 'required|string|max:255',
            'description'        => 'nullable|string',
            'contenu'            => 'nullable|string',
            'matiere_id'         => 'required|exists:matieres,id',
            'classe_id'          => 'required|exists:classes,id',
            'is_published'       => 'nullable',
            'date_programmation' => 'nullable|date',
            'medias_files.*'     => 'nullable|mimes:jpg,jpeg,png,gif,webp,mp4,mov,avi,webm,mkv,mp3,wav,ogg,m4a,aac,flac|max:102400',
            'medias_types.*'     => 'nullable|string|in:image,video,audio',
            'medias_ordres.*'    => 'nullable|integer',
        ]);

        if ($validate->fails()) {
            return response()->json([
                'status'  => 'errors',
                'errors'  => $validate->errors(),
                'message' => 'Validation échouée',
            ], 422);
        }

        $cours->update([
            'title'              => $request->title,
            'description'        => $request->description,
            'contenu'            => $request->contenu,
            'matiere_id'         => $request->matiere_id,
            'classe_id'          => $request->classe_id,
            'is_published'       => filter_var($request->is_published, FILTER_VALIDATE_BOOLEAN),
            'date_programmation' => $request->date_programmation,
        ]);

        if ($request->hasFile('medias_files')) {
            $this->handleMediasUpload($request, $cours->id);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Cours mis à jour',
            'data'    => $cours->load('medias'),
        ]);
    }

    public function destroy($id)
    {
        $cours = Cours::with('medias')->findOrFail($id);

        foreach ($cours->medias as $media) {
            $this->deleteMediaFile($media->path);
        }

        $cours->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Cours supprimé',
        ]);
    }

    public function destroyMedia($id)
    {
        $media = CoursMedias::findOrFail($id);

        $this->deleteMediaFile($media->path);
        $media->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Média supprimé',
        ]);
    }

    // ─────────────────────────────────────────────
    // HELPER — Upload des médias
    // ─────────────────────────────────────────────
    private function handleMediasUpload(Request $request, int $coursId): void
    {
        foreach ($request->file('medias_files') as $index => $file) {
            if (!$file || !$file->isValid()) continue;

            $type  = $request->input("medias_types.{$index}", 'image');
            $ordre = $request->input("medias_ordres.{$index}", $index);

            if ($type === 'image') {
                // ── Image : compression Intervention Image ──
                $filename = Str::uuid() . '.jpg';
                $path     = 'cours/images/' . $filename;

                $manager    = new ImageManager(new Driver());
                $compressed = $manager->read($file->getRealPath())
                    ->scaleDown(width: 1280)
                    ->toJpeg(quality: 80);

                Storage::disk('public')->put($path, $compressed);

            } elseif ($type === 'video') {
                // ── Vidéo : conversion FFmpeg en MP4 ──
                $ext         = strtolower($file->getClientOriginalExtension());
                $tempDir     = storage_path('app/temp');
                $videosDir   = storage_path('app/public/cours/videos');

                // Créer les dossiers si nécessaire
                if (!file_exists($tempDir))   mkdir($tempDir,   0755, true);
                if (!file_exists($videosDir)) mkdir($videosDir, 0755, true);

                $tempFilename = Str::uuid() . '.' . $ext;
                $tempPath     = $tempDir . '/' . $tempFilename;

                // Déplacer le fichier uploadé vers le dossier temp
                $file->move($tempDir, $tempFilename);

                $mp4Filename = Str::uuid() . '.mp4';
                $mp4FullPath = $videosDir . '/' . $mp4Filename;
                $path        = 'cours/videos/' . $mp4Filename;

                if ($ext === 'mp4') {
                    // Déjà en MP4 — copie directe sans conversion
                    rename($tempPath, $mp4FullPath);
                } else {
                    // Conversion AVI/MOV/MKV/WEBM → MP4
                    $cmd        = "ffmpeg -i {$tempPath} -c:v libx264 -c:a aac -movflags faststart -y {$mp4FullPath} 2>&1";
                    $returnCode = 0;
                    exec($cmd, $output, $returnCode);

                    // Supprimer le fichier temporaire
                    if (file_exists($tempPath)) unlink($tempPath);

                    if ($returnCode !== 0) {
                        Log::error('[FFmpeg] Conversion échouée', [
                            'fichier' => $tempFilename,
                            'output'  => implode("\n", $output),
                        ]);
                        continue; // Skip ce média
                    }
                }

            } else {
                // ── Audio : stockage direct ──
                $folder   = 'cours/audios';
                $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
                $path     = $file->storeAs($folder, $filename, 'public');
            }

            $url = Storage::url($path);

            CoursMedias::create([
                'cours_id' => $coursId,
                'type'     => $type,
                'url'      => $url,
                'path'     => $path,
                'ordre'    => $ordre,
            ]);
        }
    }

    // ─────────────────────────────────────────────
    // HELPER — Suppression fichier storage
    // ─────────────────────────────────────────────
    private function deleteMediaFile(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
