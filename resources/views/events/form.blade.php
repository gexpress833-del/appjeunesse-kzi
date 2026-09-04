@extends('layouts.app')

@section('title', $event->exists ? 'Modifier un événement' : 'Nouvel événement')

@section('content')
<h1 class="text-2xl font-bold text-slate-900">{{ $event->exists ? 'Modifier : '.$event->name : 'Créer un événement' }}</h1>

<form method="POST"
      action="{{ $event->exists ? route('events.update', $event) : route('events.store') }}"
      enctype="multipart/form-data"
      class="mt-6 max-w-2xl space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    @csrf
    @if ($event->exists) @method('PUT') @endif

    <div>
        <label class="block text-sm font-medium text-slate-700">Nom de l'événement *</label>
        <input name="name" value="{{ old('name', $event->name) }}" required placeholder="ex. Culte dominical, Veillée de prière…"
               class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Date et heure *</label>
        <input name="date" type="datetime-local" value="{{ old('date', $event->date?->format('Y-m-d\TH:i')) }}" required
               class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Description</label>
        <textarea name="description" rows="4"
                  class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">{{ old('description', $event->description) }}</textarea>
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Image de couverture</label>
        <input type="file" name="photo" accept="image/*"
               class="mt-1 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-indigo-700 hover:file:bg-indigo-100">
        @if ($event->photo_url)
            <img src="{{ $event->photo_url }}" class="mt-2 h-32 w-auto rounded-xl object-cover" alt="">
        @endif
    </div>

    <div class="flex items-center gap-3">
        <button class="rounded-xl bg-indigo-600 px-6 py-3 font-semibold text-white hover:bg-indigo-500">
            {{ $event->exists ? 'Enregistrer' : 'Créer l\'événement' }}
        </button>
        <a href="{{ route('events.index') }}" class="text-sm text-slate-500 hover:underline">Annuler</a>
    </div>
</form>

@if ($event->exists)
    <form method="POST" action="{{ route('events.destroy', $event) }}" class="mt-4"
          onsubmit="return confirm('Supprimer cet événement et ses présences ?')">
        @csrf @method('DELETE')
        <button class="text-sm font-semibold text-rose-600 hover:underline">🗑 Supprimer cet événement</button>
    </form>
@endif
@endsection
