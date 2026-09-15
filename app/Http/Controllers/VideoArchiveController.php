<?php

namespace App\Http\Controllers;

use App\Models\VideoArchive;
use Illuminate\Http\Request;

class VideoArchiveController extends Controller
{
    public function manage()
    {
        return view('videos.manage', [
            'videoArchives' => VideoArchive::query()->with('publisher')->withCount(['likes', 'comments'])->latest()->get(),
        ]);
    }

    public function create()
    {
        return view('videos.form', [
            'videoArchive' => new VideoArchive(['broadcast_type' => 'replay']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['published_by'] = auth()->id();

        VideoArchive::create($data);

        return redirect()->route('videos.manage')->with('success', 'Vidéo archivée ajoutée.');
    }

    public function edit(VideoArchive $videoArchive)
    {
        return view('videos.form', compact('videoArchive'));
    }

    public function update(Request $request, VideoArchive $videoArchive)
    {
        $videoArchive->update($this->validated($request));

        return redirect()->route('videos.manage')->with('success', 'Vidéo archivée mise à jour.');
    }

    public function destroy(VideoArchive $videoArchive)
    {
        $videoArchive->delete();

        return redirect()->route('videos.manage')->with('success', 'Vidéo archivée supprimée.');
    }

    public function archive(Request $request)
    {
        $type = in_array($request->query('type'), ['live', 'replay'], true) ? $request->query('type') : 'all';

        $query = VideoArchive::query()->withCount(['likes', 'comments'])->with('publisher');

        if ($type !== 'all') {
            $query->where('broadcast_type', $type);
        }

        $videoArchives = $query->latest()->get();

        return view('videos.archive', [
            'type' => $type,
            'videoArchives' => $videoArchives,
            'totalVideos' => $videoArchives->count(),
            'totalViews' => $videoArchives->sum('views_count'),
            'totalLikes' => $videoArchives->sum('likes_count'),
        ]);
    }

    public function like(Request $request, VideoArchive $videoArchive)
    {
        $like = $videoArchive->likes()->where('user_id', auth()->id())->first();
        $liked = false;

        if ($like) {
            $like->delete();
        } else {
            $videoArchive->likes()->create(['user_id' => auth()->id()]);
            $liked = true;
        }

        $count = $videoArchive->likes()->count();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'liked' => $liked,
                'count' => $count,
                'message' => $liked ? 'Vous aimez cette vidéo.' : 'Votre like a été retiré.',
            ]);
        }

        return back();
    }

    public function comment(Request $request, VideoArchive $videoArchive)
    {
        $data = $request->validate(['body' => ['required', 'string', 'max:1000']]);
        $comment = $videoArchive->comments()->create([
            'user_id' => auth()->id(),
            'body' => $data['body'],
        ]);

        $payload = [
            'count' => $videoArchive->comments()->count(),
            'comment' => [
                'user' => auth()->user()->full_name,
                'body' => $comment->body,
                'created_at' => $comment->created_at->diffForHumans(),
            ],
            'message' => 'Commentaire ajouté.',
        ];

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json($payload);
        }

        return back()->with('success', 'Commentaire ajouté.');
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'media_url' => ['required', 'url', 'max:1000'],
            'broadcast_type' => ['required', 'in:live,replay'],
        ]);
    }
}
