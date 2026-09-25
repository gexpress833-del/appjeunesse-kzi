<?php

namespace App\Http\Controllers;

use App\Models\HomeContent;
use Illuminate\Http\Request;

class CarouselController extends Controller
{
    /**
     * Gestion du carrousel (versets, témoignages, bannières) — église, secrétariat,
     * administration et pasteur principal. Le type 'live_stream' est géré séparément.
     */
    public function index()
    {
        return view('carousel.index', [
            'contents' => HomeContent::where('type', '!=', 'live_stream')
                ->whereIn('source', auth()->user()->manageableContentSourcesForCurrentPortal())
                ->ordered()
                ->get(),
            'sources' => auth()->user()->manageableContentSourcesForCurrentPortal(),
        ]);
    }

    public function create()
    {
        return view('carousel.form', [
            'content' => new HomeContent([
                'source' => auth()->user()->manageableContentSourcesForCurrentPortal()[0] ?? 'church',
                'is_active' => true,
                'display_order' => 1,
            ]),
            'sources' => auth()->user()->manageableContentSourcesForCurrentPortal(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        abort_unless(in_array($data['source'], auth()->user()->manageableContentSourcesForCurrentPortal(), true), 403, 'Vous ne pouvez pas publier dans cette source.');

        HomeContent::create($data);

        return redirect()->route('carousel.index')->with('success', 'Contenu ajouté au carrousel.');
    }

    public function edit(HomeContent $homeContent)
    {
        abort_if($homeContent->type === 'live_stream', 403, 'Le direct est géré dans la section Médias.');
        abort_unless(in_array($homeContent->source, auth()->user()->manageableContentSourcesForCurrentPortal(), true), 403);

        return view('carousel.form', [
            'content' => $homeContent,
            'sources' => auth()->user()->manageableContentSourcesForCurrentPortal(),
        ]);
    }

    public function update(Request $request, HomeContent $homeContent)
    {
        abort_unless(in_array($homeContent->source, auth()->user()->manageableContentSourcesForCurrentPortal(), true), 403);

        $data = $this->validated($request);
        abort_unless(in_array($data['source'], auth()->user()->manageableContentSourcesForCurrentPortal(), true), 403, 'Vous ne pouvez pas publier dans cette source.');
        $homeContent->update($data);

        return redirect()->route('carousel.index')->with('success', 'Contenu mis à jour.');
    }

    public function toggle(HomeContent $homeContent)
    {
        abort_unless(in_array($homeContent->source, auth()->user()->manageableContentSourcesForCurrentPortal(), true), 403);
        $homeContent->update(['is_active' => ! $homeContent->is_active]);

        return back()->with('success', $homeContent->is_active ? 'Contenu activé.' : 'Contenu masqué.');
    }

    public function destroy(HomeContent $homeContent)
    {
        abort_unless(in_array($homeContent->source, auth()->user()->manageableContentSourcesForCurrentPortal(), true), 403);
        $homeContent->delete();

        return redirect()->route('carousel.index')->with('success', 'Contenu supprimé.');
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'type' => ['required', 'in:verset,temoignage,event_banner'],
            'source' => ['required', 'in:church,youth,ecodim'],
            'title' => ['nullable', 'string', 'max:150'],
            'content' => ['required', 'string', 'max:2000'],
            'author_or_reference' => ['nullable', 'string', 'max:150'],
            'media_url' => ['nullable', 'url', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);
    }
}
