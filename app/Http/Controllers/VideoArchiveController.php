<?php

namespace App\Http\Controllers;

use App\Models\VideoArchive;
use Illuminate\Http\Request;

class VideoArchiveController extends Controller
{
    public function like(VideoArchive $videoArchive)
    {
        $like = $videoArchive->likes()->where('user_id', auth()->id())->first();

        if ($like) {
            $like->delete();
        } else {
            $videoArchive->likes()->create(['user_id' => auth()->id()]);
        }

        return back();
    }

    public function comment(Request $request, VideoArchive $videoArchive)
    {
        $data = $request->validate(['body' => ['required', 'string', 'max:1000']]);
        $videoArchive->comments()->create([
            'user_id' => auth()->id(),
            'body' => $data['body'],
        ]);

        return back()->with('success', 'Commentaire ajouté.');
    }
}
