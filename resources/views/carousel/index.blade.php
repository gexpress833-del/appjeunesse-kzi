@extends('layouts.app')

@section('title', 'Carrousel')

@section('content')
<div class="flex items-center justify-between gap-3">
    <h1 class="text-2xl font-bold text-slate-900">Carrousel d’accueil</h1>
    <a href="{{ route('carousel.create') }}" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">➕ Ajouter</a>
</div>

<div class="mt-6 space-y-4">
    @forelse ($contents as $content)
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-indigo-600">{{ $content->type }}</p>
                    <h2 class="mt-1 text-lg font-semibold text-slate-900">{{ $content->title ?? 'Sans titre' }}</h2>
                </div>
                <div class="flex items-center gap-2">
                    <form method="POST" action="{{ route('carousel.toggle', $content) }}">
                        @csrf
                        @method('PATCH')
                        <button class="rounded-lg px-3 py-2 text-sm font-semibold {{ $content->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                            {{ $content->is_active ? 'Activé' : 'Masqué' }}
                        </button>
                    </form>
                    <a href="{{ route('carousel.edit', $content) }}" class="rounded-lg bg-slate-100 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-200">Modifier</a>
                    <form method="POST" action="{{ route('carousel.destroy', $content) }}" onsubmit="return confirm('Supprimer ce contenu ?')">
                        @csrf
                        @method('DELETE')
                        <button class="rounded-lg bg-rose-100 px-3 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-200">Supprimer</button>
                    </form>
                </div>
            </div>
            <p class="mt-3 text-slate-700">{{ $content->content }}</p>
            @if ($content->author_or_reference)
                <p class="mt-2 text-sm italic text-slate-500">{{ $content->author_or_reference }}</p>
            @endif
        </div>
    @empty
        <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center text-slate-500">
            Aucun contenu n’a été ajouté au carrousel.
        </div>
    @endforelse
</div>
@endsection
