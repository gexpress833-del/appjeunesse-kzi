@extends('layouts.app')

@section('title', 'Gouvernance')

@section('content')
@php($portalLabels = ['church' => 'Église', 'youth' => 'Jeunesse', 'ecodim' => 'ECODIM'])
<div class="mx-auto max-w-5xl">
    <header class="border-b border-white/10 pb-5">
        <p class="text-sm font-semibold uppercase tracking-wider text-amber-300">Accès de gouvernance</p>
        <h1 class="mt-2 text-3xl font-bold text-white">Supervision {{ $portalLabels[$portal] }}</h1>
        <p class="mt-2 text-sm text-slate-300">Vous consultez cet espace en tant qu’administrateur principal. Les responsabilités métier restent attribuées séparément.</p>
    </header>

    <section class="mt-6" aria-label="Indicateurs de supervision">
        <dl class="divide-y divide-white/10">
            @foreach ($summary as $label => $value)
                <div class="flex items-center justify-between gap-4 py-4">
                    <dt class="text-sm text-slate-300">{{ match ($label) {
                        'classes' => 'Classes ECODIM actives',
                        'children' => 'Enfants avec inscription ECODIM active',
                        'transitions' => 'Transitions enregistrées',
                        'members' => 'Membres',
                        'events' => 'Activités',
                        'users' => 'Comptes actifs',
                        'departments' => 'Départements',
                        default => ucfirst($label),
                    } }}</dt>
                    <dd class="text-2xl font-bold text-white">{{ $value }}</dd>
                </div>
            @endforeach
        </dl>
    </section>

    <nav class="mt-6 flex flex-wrap gap-3" aria-label="Actions de supervision">
        <a href="{{ route(auth()->user()->portalAnnouncementsRouteName($portal)) }}" class="rounded-lg border border-cyan-300/30 bg-cyan-400/10 px-4 py-2 text-sm font-semibold text-cyan-100">Consulter les annonces</a>
        @if ($portal === 'ecodim')
            <a href="{{ route('members.index') }}" class="rounded-lg border border-white/15 bg-white/5 px-4 py-2 text-sm font-semibold text-white">Dossiers des membres ECODIM</a>
            <a href="{{ route('ecodim.attendances.pick') }}" class="rounded-lg border border-white/15 bg-white/5 px-4 py-2 text-sm font-semibold text-white">Superviser les présences</a>
            <a href="{{ route('ecodim.events.index') }}" class="rounded-lg border border-white/15 bg-white/5 px-4 py-2 text-sm font-semibold text-white">Activités ECODIM</a>
        @elseif ($portal === 'youth')
            <a href="{{ route('youth.events.index') }}" class="rounded-lg border border-white/15 bg-white/5 px-4 py-2 text-sm font-semibold text-white">Activités jeunesse</a>
        @else
            <a href="{{ route('members.index') }}" class="rounded-lg border border-white/15 bg-white/5 px-4 py-2 text-sm font-semibold text-white">Annuaire</a>
            <a href="{{ route('settings.index') }}" class="rounded-lg border border-white/15 bg-white/5 px-4 py-2 text-sm font-semibold text-white">Administration de l’église</a>
        @endif
    </nav>
</div>
@endsection
