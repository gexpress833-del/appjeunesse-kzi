@extends('layouts.app')

@section('title', $videoArchive->exists ? 'Modifier une vidéo archivée' : 'Ajouter une vidéo archivée')

@section('content')
<div class="max-w-3xl">
    <p class="text-xs font-bold uppercase tracking-[0.2em] text-indigo-600">Administration vidéo</p>
    <h1 class="mt-1 text-2xl font-bold text-slate-900">{{ $videoArchive->exists ? 'Modifier la vidéo' : 'Ajouter une vidéo archivée' }}</h1>
    <p class="mt-1 text-sm text-slate-500">Utilisez un lien YouTube ou Facebook public. Il sera intégré dans l’application sans lien sortant visible.</p>
</div>

<form method="POST" action="{{ $videoArchive->exists ? route('videos.update', $videoArchive) : route('videos.store') }}" class="mt-6 max-w-3xl space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    @csrf
    @if ($videoArchive->exists)
        @method('PUT')
    @endif

    <div>
        <label for="title" class="block text-sm font-medium text-slate-700">Titre *</label>
        <input id="title" name="title" required maxlength="150" value="{{ old('title', $videoArchive->title) }}" placeholder="Ex. Culte de la jeunesse - samedi" class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
        @error('title')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="media_url" class="block text-sm font-medium text-slate-700">Lien de la vidéo *</label>
        <input id="media_url" name="media_url" type="url" required maxlength="1000" value="{{ old('media_url', $videoArchive->media_url) }}" placeholder="https://www.youtube.com/watch?v=..." class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
        <p class="mt-1 text-xs text-slate-500">YouTube et Facebook sont pris en charge. Le lien sera converti en lecteur intégré.</p>
        @error('media_url')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="broadcast_type" class="block text-sm font-medium text-slate-700">Type *</label>
        <select id="broadcast_type" name="broadcast_type" required class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
            <option value="live" @selected(old('broadcast_type', $videoArchive->broadcast_type) === 'live')>En direct</option>
            <option value="replay" @selected(old('broadcast_type', $videoArchive->broadcast_type) === 'replay')>Retransmission</option>
        </select>
        @error('broadcast_type')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="description" class="block text-sm font-medium text-slate-700">Description</label>
        <textarea id="description" name="description" rows="5" maxlength="2000" placeholder="Présentez brièvement cette vidéo..." class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">{{ old('description', $videoArchive->description) }}</textarea>
        @error('description')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
    </div>

    <div class="flex flex-wrap items-center gap-3">
        <button type="submit" class="rounded-xl bg-indigo-600 px-6 py-3 font-semibold text-white hover:bg-indigo-500">{{ $videoArchive->exists ? 'Enregistrer les modifications' : 'Ajouter la vidéo' }}</button>
        <a href="{{ route('videos.manage') }}" class="text-sm text-slate-500 hover:underline">Annuler</a>
    </div>
</form>
@endsection
