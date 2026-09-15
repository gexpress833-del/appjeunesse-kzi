@extends('layouts.app')

@section('title', 'Gestion des vidéos archivées')

@section('content')
<div class="flex flex-wrap items-center justify-between gap-3">
    <div>
        <p class="text-xs font-bold uppercase tracking-[0.2em] text-indigo-600">Administration</p>
        <h1 class="mt-1 text-2xl font-bold text-slate-900">Vidéos archivées</h1>
        <p class="mt-1 text-sm text-slate-500">Ajoutez, modifiez ou retirez les directs et retransmissions visibles publiquement.</p>
    </div>
    <a href="{{ route('videos.create') }}" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-indigo-500/20 hover:bg-indigo-500">➕ Ajouter une vidéo</a>
</div>

<div class="mt-6 grid gap-4 sm:grid-cols-3">
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><span class="text-xs font-bold uppercase tracking-wide text-slate-500">Total</span><strong class="mt-1 block text-2xl text-slate-900">{{ $videoArchives->count() }}</strong></div>
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><span class="text-xs font-bold uppercase tracking-wide text-slate-500">Directs</span><strong class="mt-1 block text-2xl text-slate-900">{{ $videoArchives->where('broadcast_type', 'live')->count() }}</strong></div>
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><span class="text-xs font-bold uppercase tracking-wide text-slate-500">Retransmissions</span><strong class="mt-1 block text-2xl text-slate-900">{{ $videoArchives->where('broadcast_type', 'replay')->count() }}</strong></div>
</div>

<div class="mt-6 space-y-4">
    @forelse ($videoArchives as $video)
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-full px-2.5 py-1 text-xs font-bold uppercase {{ $video->broadcast_type === 'live' ? 'bg-rose-100 text-rose-700' : 'bg-cyan-100 text-cyan-700' }}">{{ $video->broadcast_type === 'live' ? 'En direct' : 'Retransmission' }}</span>
                        <span class="text-xs text-slate-400">{{ $video->created_at->diffForHumans() }}</span>
                    </div>
                    <h2 class="mt-2 text-lg font-bold text-slate-900">{{ $video->title }}</h2>
                    <p class="mt-1 break-all text-xs text-slate-500">{{ $video->media_url }}</p>
                    @if ($video->description)<p class="mt-2 text-sm text-slate-600">{{ $video->description }}</p>@endif
                    <p class="mt-3 text-xs text-slate-500">{{ $video->views_count }} vue(s) · {{ $video->likes_count }} like(s) · {{ $video->comments_count }} commentaire(s)</p>
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    <a href="{{ route('videos.edit', $video) }}" class="rounded-lg bg-slate-100 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-200">Modifier</a>
                    <form method="POST" action="{{ route('videos.destroy', $video) }}" onsubmit="return confirm('Supprimer cette vidéo archivée ?')">
                        @csrf
                        @method('DELETE')
                        <button class="rounded-lg bg-rose-100 px-3 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-200">Supprimer</button>
                    </form>
                </div>
            </div>
        </article>
    @empty
        <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center text-slate-500">Aucune vidéo archivée.</div>
    @endforelse
</div>
@endsection
