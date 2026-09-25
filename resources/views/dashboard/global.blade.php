@extends('layouts.app')

@section('title', 'Portail église')

@section('content')
<div class="flex flex-wrap items-end justify-between gap-3">
    <div>
        <p class="text-xs font-bold uppercase tracking-[0.18em] text-indigo-600">Portail église</p>
        <h1 class="mt-1 text-2xl font-bold text-slate-900">Bonjour {{ auth()->user()->roleLabel() }} {{ auth()->user()->full_name }} 👋</h1>
    </div>
    <span class="rounded-full bg-indigo-100 px-3 py-1 text-xs font-bold uppercase text-indigo-700">
            {{ auth()->user()->isAdmin() ? 'Administration' : (auth()->user()->isPastorPrincipal() ? 'Direction pastorale' : 'Secrétariat') }}
    </span>
</div>

<div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-sm text-slate-500">Membres au répertoire</p>
        <p class="mt-1 text-3xl font-bold text-slate-900">{{ $membersCount }}</p>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-sm text-slate-500">Comptes actifs</p>
        <p class="mt-1 text-3xl font-bold text-emerald-600">{{ $usersCount }}</p>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-sm text-slate-500">En attente de validation</p>
        <p class="mt-1 text-3xl font-bold {{ $pendingCount > 0 ? 'text-amber-600' : 'text-slate-900' }}">{{ $pendingCount }}</p>
        @if ($pendingCount > 0 && auth()->user()->isAdmin())
            <a href="{{ route('users.index', ['status' => 'pending']) }}" class="text-xs font-semibold text-indigo-600 hover:underline">Valider →</a>
        @endif
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-sm text-slate-500">Photos en galerie</p>
        <p class="mt-1 text-3xl font-bold text-slate-900">{{ $photosCount }}</p>
    </div>
</div>

<section class="mt-8 rounded-3xl border border-indigo-400/20 bg-slate-900/60 p-5 shadow-2xl shadow-indigo-950/20 backdrop-blur-xl">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-amber-300">Centre de pilotage</p>
            <h2 class="mt-1 text-xl font-bold text-white">Décisions pastorales</h2>
            <p class="mt-1 text-sm text-slate-400">Accédez rapidement aux espaces qui structurent la vie de l’Église.</p>
        </div>
        <span class="rounded-full border border-amber-400/30 bg-amber-400/10 px-3 py-1 text-xs font-bold uppercase text-amber-200">Direction</span>
    </div>
    <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <a href="{{ route('events.create') }}" class="rounded-2xl border border-amber-400/25 bg-amber-400/10 p-4 transition hover:-translate-y-1 hover:bg-amber-400/15">
            <span class="text-xl">📅</span>
            <p class="mt-3 font-semibold text-white">Créer un événement</p>
            <p class="mt-1 text-xs text-slate-400">Organiser un culte ou une activité.</p>
        </a>
        <a href="{{ route('carousel.index') }}" class="rounded-2xl border border-cyan-400/25 bg-cyan-400/10 p-4 transition hover:-translate-y-1 hover:bg-cyan-400/15">
            <span class="text-xl">📣</span>
            <p class="mt-3 font-semibold text-white">Publier une annonce</p>
            <p class="mt-1 text-xs text-slate-400">Diffuser une communication de l’Église.</p>
        </a>
        <a href="{{ route('members.index') }}" class="rounded-2xl border border-emerald-400/25 bg-emerald-400/10 p-4 transition hover:-translate-y-1 hover:bg-emerald-400/15">
            <span class="text-xl">👥</span>
            <p class="mt-3 font-semibold text-white">Suivre les membres</p>
            <p class="mt-1 text-xs text-slate-400">Consulter le répertoire de l’Église.</p>
        </a>
        <a href="{{ route('settings.index') }}" class="rounded-2xl border border-violet-400/25 bg-violet-400/10 p-4 transition hover:-translate-y-1 hover:bg-violet-400/15">
            <span class="text-xl">⚙️</span>
            <p class="mt-3 font-semibold text-white">Gérer les départements</p>
            <p class="mt-1 text-xs text-slate-400">Accompagner Jeunesse et ECODIM.</p>
        </a>
    </div>
</section>

<div class="mt-8 grid gap-6 lg:grid-cols-5">
    <section class="lg:col-span-3 rounded-3xl border border-slate-700/70 bg-slate-900/55 p-5 shadow-xl shadow-slate-950/20">
        <div class="flex items-center justify-between gap-3">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-cyan-300">Vie de l’Église</p>
                <h2 class="mt-1 text-lg font-bold text-white">Départements et responsables</h2>
            </div>
            <a href="{{ route('settings.index') }}" class="text-xs font-semibold text-cyan-300 hover:text-cyan-100">Voir tout →</a>
        </div>
        <div class="mt-4 space-y-3">
            @forelse ($departments as $department)
                <div class="flex items-center justify-between gap-3 rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                    <div class="min-w-0">
                        <p class="truncate font-semibold text-white">{{ $department->name }}</p>
                        <p class="mt-1 text-xs text-slate-400">{{ $department->members_count }} membre{{ $department->members_count === 1 ? '' : 's' }}</p>
                    </div>
                    <span class="shrink-0 rounded-full border border-cyan-400/20 bg-cyan-400/10 px-2.5 py-1 text-[10px] font-bold uppercase text-cyan-200">
                        {{ $department->leader?->full_name ?? 'À accompagner' }}
                    </span>
                </div>
            @empty
                <p class="rounded-2xl border border-dashed border-slate-700 px-4 py-6 text-center text-sm text-slate-400">Les départements pourront être ajoutés depuis les paramètres.</p>
            @endforelse
        </div>
    </section>

    <section class="lg:col-span-2 rounded-3xl border border-slate-700/70 bg-slate-900/55 p-5 shadow-xl shadow-slate-950/20">
        <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-amber-300">Communication</p>
        <h2 class="mt-1 text-lg font-bold text-white">Dernières annonces</h2>
        <div class="mt-4 space-y-3">
            @forelse ($recentAnnouncements as $announcement)
                <div class="rounded-2xl border border-white/10 bg-white/5 p-3">
                    <p class="text-xs font-bold uppercase tracking-wide text-amber-200">{{ $announcement->sourceLabel() }}</p>
                    <p class="mt-1 font-semibold text-white">{{ $announcement->title }}</p>
                    <p class="mt-1 line-clamp-2 text-xs leading-5 text-slate-400">{{ $announcement->content }}</p>
                </div>
            @empty
                <p class="rounded-2xl border border-dashed border-slate-700 px-4 py-6 text-center text-sm text-slate-400">Aucune communication récente.</p>
            @endforelse
        </div>
    </section>
</div>

@if ($upcoming->isNotEmpty())
    <h2 class="mt-10 text-lg font-bold text-slate-900">📅 Prochains événements</h2>
    <div class="mt-3 grid gap-4 sm:grid-cols-3">
        @foreach ($upcoming as $event)
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-bold uppercase text-indigo-600">{{ $event->date->translatedFormat('l d F') }} · {{ $event->date->format('H\hi') }}</p>
                <p class="mt-1 font-semibold">{{ $event->name }}</p>
            </div>
        @endforeach
    </div>
@endif

@if ($lastEvents->isNotEmpty())
    <h2 class="mt-10 text-lg font-bold text-slate-900">📊 Dernières réunions & taux de présence par département</h2>
    <div class="mt-3 overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-4 py-3">Événement</th>
                    <th class="px-4 py-3">Date</th>
                    <th class="px-4 py-3">Présences relevées</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($lastEvents as $event)
                    <tr>
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $event->name }}</td>
                        <td class="px-4 py-3 text-slate-500">{{ $event->date->translatedFormat('d/m/Y') }}</td>
                        <td class="px-4 py-3">{{ $event->members_count }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if ($deptStats->isNotEmpty())
        <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($deptStats as $stat)
                <div class="rounded-xl border border-slate-200 bg-white p-4">
                    <p class="text-sm font-semibold text-slate-900">{{ $stat->dept ?? 'Sans département' }}</p>
                    @if ($stat->rate !== null)
                        <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full {{ $stat->rate >= 70 ? 'bg-emerald-500' : ($stat->rate >= 40 ? 'bg-amber-500' : 'bg-rose-500') }}"
                                 style="width: {{ $stat->rate }}%"></div>
                        </div>
                        <p class="mt-1 text-xs text-slate-500">{{ $stat->rate }}% de présence ({{ $stat->ok }}/{{ $stat->total }})</p>
                    @else
                        <p class="mt-2 text-xs text-slate-400">Aucune donnée</p>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
@endif
@endsection
