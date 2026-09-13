<?php

namespace App\Http\Controllers;

use App\Models\VideoArchive;
use Illuminate\Http\Request;

class VideoArchiveController extends Controller
{
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
}
