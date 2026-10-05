@extends('layouts.app')

@section('title', auth()->user()->portalLabel())

@section('content')
@php($portalLabels = ['church' => 'Église', 'youth' => 'Jeunesse', 'ecodim' => 'ECODIM'])
<div class="mx-auto max-w-4xl">
    <header class="border-b border-white/10 pb-5">
        <p class="text-sm font-semibold uppercase tracking-wider text-cyan-300">Consultation</p>
        <h1 class="mt-2 text-3xl font-bold text-white">Portail {{ $portalLabels[$portal] }}</h1>
        <p class="mt-2 text-sm text-slate-300">Informations institutionnelles et annonces publiées.</p>
        <a href="{{ route(auth()->user()->portalAnnouncementsRouteName($portal)) }}" class="mt-4 inline-flex rounded-lg border border-cyan-300/30 bg-cyan-400/10 px-4 py-2 text-sm font-semibold text-cyan-100">Toutes les annonces</a>
    </header>

    <section class="mt-6" aria-labelledby="portal-announcements-title">
        <h2 id="portal-announcements-title" class="text-xl font-bold text-white">Annonces du portail</h2>
        <div class="mt-4 divide-y divide-white/10">
            @forelse ($announcements as $announcement)
                <article class="py-4">
                    <h3 class="font-semibold text-white">{{ $announcement->title ?: 'Annonce' }}</h3>
                    @if ($announcement->author_or_reference)
                        <p class="mt-1 text-xs text-cyan-200">{{ $announcement->author_or_reference }}</p>
                    @endif
                    <p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-300">{{ $announcement->content }}</p>
                </article>
            @empty
                <p class="py-5 text-sm text-slate-300">Aucune annonce publiée pour le moment.</p>
            @endforelse
        </div>
    </section>
</div>
@endsection
