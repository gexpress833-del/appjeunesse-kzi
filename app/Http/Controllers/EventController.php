<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;

class EventController extends Controller
{
    /**
     * Liste des événements : à venir d'abord, puis passés.
     */
    public function index()
    {
        $user = auth()->user();

        return view('events.index', [
            'upcoming' => Event::query()
                ->when($user->isResponsable(), fn ($query) => $query->where(fn ($query) => $query
                    ->whereNull('dept')
                    ->orWhere('dept', $user->dept)))
                ->upcoming()
                ->withCount('members')
                ->paginate(10, ['*'], 'up')
                ->withQueryString(),
            'past' => Event::query()
                ->when($user->isResponsable(), fn ($query) => $query->where(fn ($query) => $query
                    ->whereNull('dept')
                    ->orWhere('dept', $user->dept)))
                ->past()
                ->withCount('members')
                ->paginate(10, ['*'], 'past')
                ->withQueryString(),
        ]);
    }

    public function create()
    {
        return view('events.form', [
            'event' => new Event([
                'date' => now()->next('sunday')->setTime(9, 0),
                'dept' => auth()->user()->isResponsable() ? auth()->user()->dept : null,
            ]),
        ]);
    }

    public function store(Request $request, CloudinaryService $cloudinary)
    {
        $data = $this->validated($request);
        $user = auth()->user();

        if ($user->isResponsable()) {
            $data['dept'] = $user->dept;
        }

        $data['created_by'] = $user->username;
        $data = $this->handlePhoto($request, $cloudinary, $data);

        Event::create($data);

        return redirect()->route('events.index')->with('success', 'Événement créé.');
    }

    public function edit(Event $event)
    {
        $this->authorizeDepartment($event);

        return view('events.form', ['event' => $event]);
    }

    public function update(Request $request, Event $event, CloudinaryService $cloudinary)
    {
        $this->authorizeDepartment($event);

        $data = $this->validated($request);

        if (auth()->user()->isResponsable()) {
            $data['dept'] = $event->dept;
        }

        if ($request->hasFile('photo')) {
            $cloudinary->delete($event->cloudinary_public_id);
            $uploaded = $cloudinary->upload($request->file('photo'), 'appjeune-kzi/events');
            $data['photo_url'] = $uploaded['url'];
            $data['cloudinary_public_id'] = $uploaded['public_id'];
        }

        $event->update($data);

        return redirect()->route('events.index')->with('success', 'Événement mis à jour.');
    }

    public function destroy(Event $event, CloudinaryService $cloudinary)
    {
        $this->authorizeDepartment($event);

        $cloudinary->delete($event->cloudinary_public_id);
        $event->delete();

        return redirect()->route('events.index')->with('success', 'Événement supprimé.');
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:5000'],
            'dept' => ['nullable', 'string', 'exists:departments,name'],
            'photo' => ['nullable', 'image', 'max:8192'],
        ]);
    }

    protected function handlePhoto(Request $request, CloudinaryService $cloudinary, array $data): array
    {
        if (! $request->hasFile('photo')) {
            return $data;
        }

        $uploaded = $cloudinary->upload($request->file('photo'), 'appjeune-kzi/events');
        $data['photo_url'] = $uploaded['url'];
        $data['cloudinary_public_id'] = $uploaded['public_id'];

        return $data;
    }

    protected function authorizeDepartment(Event $event): void
    {
        $user = auth()->user();

        if ($user->isResponsable() && $event->dept !== $user->dept) {
            abort(403, 'Vous ne pouvez gérer que les événements de votre département.');
        }
    }
}
