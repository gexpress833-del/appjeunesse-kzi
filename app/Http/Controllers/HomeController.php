<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\HomeContent;

class HomeController extends Controller
{
    /**
     * Landing page publique : carrousel (versets, témoignages, bannières
     * d'événements), section live vidéo et prochains événements.
     */
    public function index()
    {
        $versets = HomeContent::type('verset')->active()->ordered()->get();
        $temoignages = HomeContent::type('temoignage')->active()->ordered()->get();
        $banners = HomeContent::type('event_banner')->active()->ordered()->get();
        $live = HomeContent::type('live_stream')->active()->ordered()->first();
        $upcomingEvents = Event::upcoming()->take(4)->get();

        return view('home', compact('versets', 'temoignages', 'banners', 'live', 'upcomingEvents'));
    }
}
