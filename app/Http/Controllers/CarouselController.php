<?php

namespace App\Http\Controllers;

use App\Models\HomeContent;
use Illuminate\Http\Request;

class CarouselController extends Controller
{
    /**
     * Gestion du carrousel (versets, témoignages, bannières) — secrétariat & admin.
     * Le type 'live_stream' est géré séparément par le responsable DCC.
     */
    public function index()
    {
        return view('carousel.index', [
            'contents' => HomeContent::where('type', '!=', 'live_stream')->ordered()->get(),
        ]);
    }

    public function create()
    {
        return view('carousel.form', [
            'content' => new HomeContent(['is_active' => true, 'display_order' => 1]),
        ]);
    }

    public function store(Request $request)
    {
        HomeContent::create($this->validated($request));

        return redirect()->route('carousel.index')->with('success', 'Contenu ajouté au carrousel.');
    }

    public function edit(HomeContent $homeContent)
    {
        abort_if($homeContent->type === 'live_stream', 403, 'Le direct est géré dans la section Médias.');

        return view('carousel.form', ['content' => $homeContent]);
    }

    public function update(Request $request, HomeContent $homeContent)
    {
        $homeContent->update($this->validated($request));

        return redirect()->route('carousel.index')->with('success', 'Contenu mis à jour.');
    }

    public function toggle(HomeContent $homeContent)
    {
        $homeContent->update(['is_active' => ! $homeContent->is_active]);

        return back()->with('success', $homeContent->is_active ? 'Contenu activé.' : 'Contenu masqué.');
    }

    public function destroy(HomeContent $homeContent)
    {
        $homeContent->delete();

        return redirect()->route('carousel.index')->with('success', 'Contenu supprimé.');
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'type' => ['required', 'in:verset,temoignage,event_banner'],
            'title' => ['nullable', 'string', 'max:150'],
            'content' => ['required', 'string', 'max:2000'],
            'author_or_reference' => ['nullable', 'string', 'max:150'],
            'media_url' => ['nullable', 'url', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);
    }
}
