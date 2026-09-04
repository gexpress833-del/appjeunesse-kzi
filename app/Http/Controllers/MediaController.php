<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\HomeContent;
use App\Models\Photo;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use ZipArchive;

class MediaController extends Controller
{
    /**
     * Garde-fou : galerie & direct réservés au responsable DCC/Médias ou admin.
     */
    protected function authorizeMedia(): void
    {
        abort_unless(auth()->user()->managesMedia(), 403,
            'Seul le responsable DCC/Médias (ou un administrateur) gère les médias.');
    }

    /**
     * Galerie consultable par tous les comptes actifs.
     */
    public function gallery(Request $request)
    {
        $query = Photo::with('event')->orderByDesc('created_at');

        if ($request->filled('event_id')) {
            $query->where('event_id', $request->input('event_id'));
        }

        if ($request->filled('search')) {
            $term = trim($request->input('search'));
            $query->where(function ($q) use ($term) {
                $q->where('event_name', 'like', "%{$term}%")
                    ->orWhere('title', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%");
            });
        }

        return view('gallery.index', [
            'photos' => $query->paginate(24)->appends($request->only(['event_id', 'search'])),
            'events' => Event::orderByDesc('date')->get(),
            'selectedEventId' => $request->input('event_id'),
            'search' => $request->input('search'),
            'canManage' => auth()->user()->managesMedia(),
        ]);
    }

    public function uploadForm()
    {
        $this->authorizeMedia();

        return view('gallery.upload', [
            'events' => Event::orderByDesc('date')->take(30)->get(),
        ]);
    }

    /**
     * Téléversement multiple vers Cloudinary + enregistrement dans PostgreSQL.
     */
    public function storePhotos(Request $request, CloudinaryService $cloudinary)
    {
        $this->authorizeMedia();

        $data = $request->validate([
            'photos' => ['required', 'array', 'min:1', 'max:20'],
            'photos.*' => ['image', 'max:8192'],
            'title' => ['nullable', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'event_id' => ['required', 'exists:events,id'],
        ]);

        if (! $cloudinary->isConfigured()) {
            return back()->withErrors(['photos' => 'Cloudinary n\'est pas configuré (CLOUDINARY_URL manquant dans le .env).']);
        }

        $event = Event::findOrFail($data['event_id']);
        $count = 0;

        foreach ($request->file('photos', []) as $file) {
            if (! $file->isValid()) {
                continue;
            }

            $uploaded = $cloudinary->upload($file, 'appjeune-kzi/gallery');

            Photo::create([
                'title' => $data['title'] ?? $event->name,
                'description' => $data['description'] ?? null,
                'image_url' => $uploaded['url'],
                'cloudinary_public_id' => $uploaded['public_id'],
                'event_id' => $event->id,
                'event_name' => $event->name,
                'uploaded_by' => auth()->user()->username,
            ]);

            $count++;
        }

        return redirect()->route('gallery.index')
            ->with('success', $count.' photo(s) publiée(s) pour l\'événement \"'.$event->name.'\".');
    }

    public function downloadSelected(Request $request)
    {
        $data = $request->validate([
            'photos' => ['required', 'array', 'min:1', 'max:20'],
            'photos.*' => ['required', 'distinct', 'exists:photos,id'],
        ]);

        $photos = Photo::whereIn('id', $data['photos'])->orderByDesc('created_at')->get();

        if ($photos->count() === 1) {
            return $this->downloadSingle($photos->first());
        }

        return $this->downloadZip($photos);
    }

    protected function downloadSingle(Photo $photo): Response
    {
        $contents = @file_get_contents($photo->image_url);

        if ($contents === false) {
            abort(404, 'La photo demandée est introuvable.');
        }

        $filename = $this->makePhotoFilename($photo, 'photo');

        return response($contents, 200)
            ->header('Content-Type', 'image/jpeg')
            ->header('Content-Disposition', 'attachment; filename="'.$filename.'"');
    }

    protected function downloadZip($photos): Response
    {
        $zipPath = tempnam(sys_get_temp_dir(), 'gallery-');
        $zip = new ZipArchive;

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            abort(500, 'Impossible de générer le téléchargement ZIP.');
        }

        foreach ($photos as $index => $photo) {
            $contents = @file_get_contents($photo->image_url);

            if ($contents === false) {
                continue;
            }

            $zip->addFromString($this->makePhotoFilename($photo, 'photo-'.$index), $contents);
        }

        $zip->close();

        return response()->download($zipPath, 'photos-'.now()->format('Ymd-His').'.zip', [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }

    protected function makePhotoFilename(Photo $photo, string $prefix): string
    {
        $base = Str::slug($photo->event?->name ?: $photo->title ?: 'photo');
        $ext = pathinfo(parse_url($photo->image_url, PHP_URL_PATH) ?: 'photo.jpg', PATHINFO_EXTENSION) ?: 'jpg';

        return strtolower($prefix.'-'.$base.'.'.$ext);
    }

    public function destroyPhoto(Photo $photo, CloudinaryService $cloudinary)
    {
        $this->authorizeMedia();

        $cloudinary->delete($photo->cloudinary_public_id);
        $photo->delete();

        return redirect()->route('gallery.index')->with('success', 'Photo supprimée.');
    }

    /*
    |------------------------------------------------------------------
    | Live vidéo (YouTube / Facebook) — home_contents type 'live_stream'
    |------------------------------------------------------------------
    */

    public function liveForm()
    {
        $this->authorizeMedia();

        return view('gallery.live', [
            'live' => HomeContent::type('live_stream')->ordered()->first(),
        ]);
    }

    public function liveSave(Request $request)
    {
        $this->authorizeMedia();

        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:150'],
            'content' => ['nullable', 'string', 'max:1000'],
            'media_url' => ['nullable', 'url', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $live = HomeContent::type('live_stream')->ordered()->first()
            ?? new HomeContent(['type' => 'live_stream', 'display_order' => 1]);

        $live->title = $data['title'] ?? 'Culte en direct';
        $live->content = $data['content'] ?? $live->content;
        $live->media_url = $data['media_url'] ?? null;
        $live->is_active = $request->boolean('is_active');
        $live->save();

        return redirect()->route('live.edit')->with('success', 'Paramètres du direct enregistrés.');
    }
}
