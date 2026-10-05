@extends('layouts.app')

@section('title', 'Annonces')

@section('content')
@php($portalLabels = ['church' => 'Église', 'youth' => 'Jeunesse', 'ecodim' => 'ECODIM'])
<div class="mx-auto max-w-4xl">
    <a href="{{ route(auth()->user()->portalDashboardRouteName($portal)) }}" class="text-sm font-semibold text-cyan-200 hover:text-white">← Portail {{ $portalLabels[$portal] }}</a>
    <h1 class="mt-3 text-3xl font-bold text-white">Annonces {{ $portalLabels[$portal] }}</h1>
    <div class="mt-5 divide-y divide-white/10">
        @forelse ($announcements as $announcement)
            <article class="py-5">
                <h2 class="text-lg font-semibold text-white">{{ $announcement->title ?: 'Annonce' }}</h2>
                @if ($announcement->author_or_reference)
                    <p class="mt-1 text-xs text-cyan-200">{{ $announcement->author_or_reference }}</p>
                @endif
                <p class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-300">{{ $announcement->content }}</p>
            </article>
        @empty
            <p class="py-6 text-sm text-slate-300">Aucune annonce publiée pour ce portail.</p>
        @endforelse
    </div>
</div>
@endsection
