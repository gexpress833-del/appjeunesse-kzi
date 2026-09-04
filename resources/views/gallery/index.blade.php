@extends('layouts.app')

@section('title', 'Galerie')

@section('content')
<div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
    <h1 class="text-2xl font-bold text-slate-900">Galerie photos</h1>
    @if ($canManage)
        <a href="{{ route('gallery.upload') }}" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">📤 Publier</a>
    @endif
</div>

<div class="mt-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
    <form method="GET" action="{{ route('gallery.index') }}" class="flex flex-col gap-3 lg:flex-row lg:items-end">
        <div class="flex-1">
            <label for="search" class="mb-1 block text-sm font-medium text-slate-700">Recherche par événement ou titre</label>
            <input id="search" name="search" value="{{ old('search', $search) }}" placeholder="Ex. Culte du dimanche"
                   class="w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
        </div>

        <div class="lg:w-72">
            <label for="event_id" class="mb-1 block text-sm font-medium text-slate-700">Événement</label>
            <select id="event_id" name="event_id" class="w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">Tous les événements</option>
                @foreach ($events as $event)
                    <option value="{{ $event->id }}" @selected((string) old('event_id', $selectedEventId) === (string) $event->id)>{{ $event->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex gap-2">
            <button type="submit" class="rounded-xl bg-slate-800 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-700">Filtrer</button>
            <a href="{{ route('gallery.index') }}" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-100">Réinitialiser</a>
        </div>
    </form>
</div>

@if ($photos->isEmpty())
    <div class="mt-6 rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center text-slate-500">
        Aucune photo n’a encore été publiée pour ce filtre.
    </div>
@else
    <form method="POST" action="{{ route('gallery.download') }}" class="mt-6">
        @csrf
        <div class="mb-4 flex items-center justify-between gap-3">
            <p class="text-sm text-slate-600">Sélectionnez les photos à télécharger.</p>
            <button type="submit" class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">⬇️ Télécharger la sélection</button>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($photos as $photo)
                <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="relative">
                        <label class="absolute left-3 top-3 z-10 flex items-center gap-2 rounded-full bg-slate-900/75 px-2 py-1 text-xs font-medium text-white backdrop-blur-sm">
                            <input type="checkbox" name="photos[]" value="{{ $photo->id }}" class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            <span>Sélection</span>
                        </label>

                        <button type="button"
                                class="preview-trigger block w-full text-left"
                                data-image="{{ $photo->image_url }}"
                                data-title="{{ $photo->title ?? 'Photo de la jeunesse' }}"
                                data-event="{{ $photo->event?->name ?? 'Aucun événement' }}">
                            <img src="{{ $photo->image_url }}" alt="{{ $photo->title ?? 'Photo' }}" class="h-52 w-full object-cover transition duration-200 hover:brightness-110" />
                        </button>
                    </div>

                    <div class="space-y-2 p-4">
                        <p class="font-semibold text-slate-900">{{ $photo->title ?? 'Photo de la jeunesse' }}</p>
                        @if ($photo->event)
                            <p class="text-xs uppercase tracking-wide text-indigo-600">{{ $photo->event->name }}</p>
                        @endif
                        @if ($photo->description)
                            <p class="text-sm text-slate-600">{{ Str::limit($photo->description, 90) }}</p>
                        @endif

                        <div class="flex items-center justify-between gap-3 pt-2">
                            <button type="button" class="preview-trigger text-sm font-semibold text-cyan-700 hover:underline" data-image="{{ $photo->image_url }}" data-title="{{ $photo->title ?? 'Photo de la jeunesse' }}" data-event="{{ $photo->event?->name ?? 'Aucun événement' }}">Aperçu</button>

                            @if ($canManage)
                                <form method="POST" action="{{ route('gallery.destroy', $photo) }}" onsubmit="return confirm('Supprimer cette photo ?')" class="inline-block">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-sm font-semibold text-rose-600 hover:underline">Supprimer</button>
                                </form>
                            @endif
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    </form>

    <div class="mt-6">{{ $photos->links() }}</div>
@endif

<div id="photo-preview-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/80 p-4 backdrop-blur-sm">
    <div class="relative w-full max-w-5xl overflow-hidden rounded-3xl border border-white/10 bg-slate-900 shadow-2xl shadow-slate-950/50">
        <button type="button" id="close-preview-modal" aria-label="Fermer l’aperçu" class="absolute right-4 top-4 z-10 rounded-full bg-slate-800/80 px-3 py-1 text-lg font-semibold text-white hover:bg-slate-700">✕</button>

        <div class="flex flex-col md:flex-row">
            <div class="flex-1 bg-slate-950 p-3">
                <img id="preview-image" src="" alt="Aperçu de photo" class="max-h-[76vh] w-full rounded-2xl object-contain">
            </div>

            <div class="w-full max-w-sm border-t border-white/10 bg-slate-900 p-5 md:border-l md:border-t-0">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan-300">Aperçu</p>
                <h3 id="preview-title" class="mt-3 text-xl font-bold text-white">Photo</h3>
                <p id="preview-event" class="mt-1 text-sm text-slate-300"></p>

                <div class="mt-6 flex gap-3">
                    <a id="preview-download" href="#" target="_blank" rel="noopener" class="flex-1 rounded-xl bg-emerald-500 px-4 py-2.5 text-center text-sm font-semibold text-white shadow-lg shadow-emerald-600/25 hover:bg-emerald-400">⬇️ Télécharger</a>
                    <button type="button" id="preview-close-button" class="rounded-xl border border-slate-600 px-4 py-2.5 text-sm font-semibold text-slate-200 hover:bg-slate-800">Fermer</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const previewModal = document.getElementById('photo-preview-modal');
    const previewImage = document.getElementById('preview-image');
    const previewTitle = document.getElementById('preview-title');
    const previewEvent = document.getElementById('preview-event');
    const previewDownload = document.getElementById('preview-download');

    function openPreview(imageUrl, title, eventName) {
        previewImage.src = imageUrl;
        previewImage.alt = title || 'Photo';
        previewTitle.textContent = title || 'Photo';
        previewEvent.textContent = eventName || 'Aucun événement';
        previewDownload.href = imageUrl;
        previewModal.classList.remove('hidden');
        previewModal.classList.add('flex');
    }

    function closePreview() {
        previewModal.classList.add('hidden');
        previewModal.classList.remove('flex');
    }

    document.querySelectorAll('.preview-trigger').forEach((button) => {
        button.addEventListener('click', () => {
            openPreview(button.dataset.image, button.dataset.title, button.dataset.event);
        });
    });

    document.getElementById('close-preview-modal').addEventListener('click', closePreview);
    document.getElementById('preview-close-button').addEventListener('click', closePreview);
    previewModal.addEventListener('click', (event) => {
        if (event.target === previewModal) {
            closePreview();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !previewModal.classList.contains('hidden')) {
            closePreview();
        }
    });
</script>
@endsection
