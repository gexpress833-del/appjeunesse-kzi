@extends('layouts.app')

@section('title', 'Direct vidéo')

@section('content')
<h1 class="text-2xl font-bold text-slate-900">Direct vidéo</h1>

<form method="POST" action="{{ route('live.save') }}" class="mt-6 max-w-2xl space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    @csrf

    <div>
        <label class="block text-sm font-medium text-slate-700">Titre</label>
        <input name="title" value="{{ old('title', $live?->title ?? 'Culte en direct') }}"
               class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">Lien vidéo</label>
        <input name="media_url" type="url" value="{{ old('media_url', $live?->media_url) }}" placeholder="https://youtube.com/watch?..."
               class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">Description</label>
        <textarea name="content" rows="4"
                  class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">{{ old('content', $live?->content) }}</textarea>
    </div>

    <label class="flex items-center gap-3 text-sm text-slate-700">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $live?->is_active ?? false)) class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
        Activer le direct sur la page d’accueil
    </label>

    <button class="rounded-xl bg-indigo-600 px-6 py-3 font-semibold text-white hover:bg-indigo-500">Enregistrer</button>
</form>
@endsection
