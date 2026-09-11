<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\HomeContent;
use App\Models\VideoArchive;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Landing page publique : carrousel (versets, témoignages, bannières
     * d'événements), section live vidéo et prochains événements.
     */
    public function index(Request $request)
    {
        $versets = HomeContent::type('verset')->active()->ordered()->get();
        $temoignages = HomeContent::type('temoignage')->active()->ordered()->get();
        $banners = HomeContent::type('event_banner')->active()->ordered()->get();
        $live = HomeContent::type('live_stream')->active()->ordered()->first();
        $upcomingEvents = Event::upcoming()->take(4)->get();

        $liveArchive = null;

        if ($live?->media_url) {
            $liveArchive = VideoArchive::firstOrCreate(
                ['media_url' => $live->media_url],
                [
                    'title' => $live->title ?: 'Culte vidéo',
                    'description' => $live->content,
                    'broadcast_type' => $live->broadcast_type ?: 'replay',
                ],
            );

            $viewKey = 'video-viewed-'.$liveArchive->id;
            if (! $request->session()->has($viewKey)) {
                $liveArchive->increment('views_count');
                $request->session()->put($viewKey, true);
                $liveArchive->refresh();
            }

            $liveArchive->loadCount('likes')->load('comments.user');
        }

        $videoArchives = VideoArchive::withCount('likes')
            ->with(['comments.user', 'likes'])
            ->when($liveArchive, fn ($query) => $query->whereKeyNot($liveArchive->id))
            ->latest()
            ->take(12)
            ->get();

        return view('home', compact('versets', 'temoignages', 'banners', 'live', 'liveArchive', 'upcomingEvents', 'videoArchives'));
    }
}
