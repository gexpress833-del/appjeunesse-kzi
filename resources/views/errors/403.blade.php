@extends('layouts.guest')

@section('title', 'Accès non autorisé')

@section('content')
<div class="mx-auto max-w-xl">
    <div class="rounded-3xl border border-amber-200/40 bg-slate-900/80 p-8 text-center shadow-xl shadow-slate-950/30 ring-1 ring-white/10">
        <div class="mb-4 inline-flex h-16 w-16 items-center justify-center rounded-full bg-amber-500/15 text-3xl text-amber-300">🔒</div>
        <p class="text-xs font-bold uppercase tracking-[0.2em] text-amber-200/80">Accès</p>
        <h1 class="mt-3 text-3xl font-black text-white">Accès non autorisé</h1>
        <p class="mt-3 text-base text-slate-200">Vous n’avez pas les autorisations nécessaires pour accéder à cette page.</p>
        <p class="mt-2 text-sm text-slate-300">{{ $contextMessage }}</p>

        <div class="mt-6 flex flex-wrap justify-center gap-3">
            <a href="{{ $returnUrl }}" class="rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-indigo-500">Retour au portail</a>
            <a href="{{ route('home') }}" class="rounded-xl border border-slate-700 bg-slate-800/70 px-5 py-3 text-sm font-semibold text-slate-100 transition hover:bg-slate-700">Retour à l’accueil</a>
        </div>
    </div>
</div>
@endsection
