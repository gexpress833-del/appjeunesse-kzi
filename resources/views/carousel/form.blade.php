@extends('layouts.app')

@section('title', $content->exists ? 'Modifier le contenu' : 'Nouveau contenu')

@section('content')
<h1 class="text-2xl font-bold text-slate-900">{{ $content->exists ? 'Modifier le contenu' : 'Ajouter un contenu' }}</h1>

<form method="POST" action="{{ $content->exists ? route('carousel.update', $content) : route('carousel.store') }}" class="mt-6 max-w-2xl space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    @csrf
    @if ($content->exists)
        @method('PUT')
    @endif

    <div>
        <label class="block text-sm font-medium text-slate-700">Type</label>
        <select name="type" class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
            @foreach (['verset', 'temoignage', 'event_banner'] as $type)
                <option value="{{ $type }}" @selected(old('type', $content->type) === $type)>{{ $type }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">Titre</label>
        <input name="title" value="{{ old('title', $content->title) }}" placeholder="Titre ou libellé"
               class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">Contenu *</label>
        <textarea name="content" rows="5" required
                  class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">{{ old('content', $content->content) }}</textarea>
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">Auteur / référence</label>
        <input name="author_or_reference" value="{{ old('author_or_reference', $content->author_or_reference) }}"
               class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">Lien media</label>
        <input name="media_url" type="url" value="{{ old('media_url', $content->media_url) }}"
               class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="block text-sm font-medium text-slate-700">Ordre d’affichage</label>
            <input type="number" name="display_order" value="{{ old('display_order', $content->display_order ?? 1) }}" min="0" max="999"
                   class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
        </div>
        <label class="flex items-center gap-3 pt-8 text-sm text-slate-700">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $content->is_active ?? true)) class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
            Actif
        </label>
    </div>

    <div class="flex items-center gap-3">
        <button class="rounded-xl bg-indigo-600 px-6 py-3 font-semibold text-white hover:bg-indigo-500">{{ $content->exists ? 'Enregistrer' : 'Créer' }}</button>
        <a href="{{ route('carousel.index') }}" class="text-sm text-slate-500 hover:underline">Annuler</a>
    </div>
</form>
@endsection
