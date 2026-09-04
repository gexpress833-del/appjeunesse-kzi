@extends('layouts.app')

@section('title', 'Mon tableau de bord')

@section('content')
@php
    $roleLabels = [
        'admin' => 'Administrateur',
        'secretariat' => 'Secrétariat',
        'responsable' => 'Responsable',
        'user' => 'Membre',
    ];
    $currentUser = auth()->user();
@endphp
<div class="rounded-3xl border border-white/10 bg-slate-950/30 p-6 shadow-2xl shadow-slate-950/20 backdrop-blur-sm">
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[0.18em] text-cyan-300">Tableau de bord personnel</p>
            <h1 class="mt-2 text-3xl font-black text-white">Bonjour {{ auth()->user()->full_name }} 👋</h1>
            <div class="mt-3 flex flex-wrap items-center gap-2 text-xs">
                <span class="rounded-full border border-cyan-400/30 bg-cyan-500/10 px-3 py-1.5 font-semibold text-cyan-200">
                    Rôle : {{ $roleLabels[$currentUser->role] ?? ucfirst($currentUser->role) }}
                </span>
                @if ($currentUser->dept)
                    <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-slate-300">
                        Département : {{ $currentUser->dept }}
                    </span>
                @endif
            </div>
        </div>
        <a href="{{ route('profile.edit') }}" class="inline-flex items-center justify-center rounded-xl border border-cyan-400/40 bg-cyan-500/10 px-4 py-2 text-sm font-semibold text-cyan-200 transition hover:bg-cyan-500/20">Mon profil</a>
    </div>

    <section class="mt-6 grid gap-5 xl:grid-cols-[1.35fr_0.65fr]">
        <div class="rounded-2xl border border-indigo-400/25 bg-gradient-to-br from-indigo-500/15 to-sky-500/10 p-5">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-indigo-300">Votre activité</p>
                    <h2 class="mt-2 text-xl font-bold text-white">Suivez votre parcours</h2>
                </div>
                <a href="{{ route('dashboard.bilan') }}" class="rounded-xl bg-indigo-500 px-3 py-2 text-sm font-semibold text-white transition hover:bg-indigo-400">Voir le bilan</a>
            </div>
            <div class="mt-5 grid gap-3 sm:grid-cols-3">
                <div class="rounded-xl border border-white/10 bg-slate-950/30 p-4">
                    <p class="text-xs text-slate-400">Participation</p>
                    <p class="mt-1 text-2xl font-black text-emerald-300">{{ $stats['rate'] ?? 0 }}%</p>
                </div>
                <div class="rounded-xl border border-white/10 bg-slate-950/30 p-4">
                    <p class="text-xs text-slate-400">Présences</p>
                    <p class="mt-1 text-2xl font-black text-white">{{ $stats['present'] ?? 0 }}</p>
                </div>
                <div class="rounded-xl border border-white/10 bg-slate-950/30 p-4">
                    <p class="text-xs text-slate-400">Visites récentes</p>
                    <p class="mt-1 text-2xl font-black text-amber-300">{{ $visits->count() }}</p>
                </div>
            </div>
        </div>

        <aside class="rounded-2xl border border-amber-400/25 bg-gradient-to-br from-amber-500/10 to-orange-400/10 p-5">
            <div class="flex items-center justify-between gap-3">
                <h2 class="font-bold text-white">📣 Annonces</h2>
                <a href="{{ route('home') }}" class="text-xs font-semibold text-amber-200 hover:text-white">Tout voir</a>
            </div>
            @forelse ($announcements as $announcement)
                <article class="mt-4 border-b border-white/10 pb-3 last:border-0 last:pb-0">
                    <p class="text-sm font-semibold text-amber-100">{{ $announcement->title ?? 'Annonce' }}</p>
                    <p class="mt-1 line-clamp-2 text-xs leading-5 text-slate-300">{{ $announcement->content }}</p>
                </article>
            @empty
                <p class="mt-4 text-sm text-slate-300">Aucune annonce publiée pour le moment.</p>
            @endforelse
        </aside>
    </section>

    <section class="mt-5 flex flex-wrap gap-3">
        <a href="{{ route('social-visits.index') }}" class="rounded-xl border border-amber-400/30 bg-amber-500/10 px-4 py-2.5 text-sm font-semibold text-amber-100 transition hover:bg-amber-500/20">🤝 Mes visites</a>
        <a href="{{ route('members.index') }}" class="rounded-xl border border-cyan-400/30 bg-cyan-500/10 px-4 py-2.5 text-sm font-semibold text-cyan-100 transition hover:bg-cyan-500/20">👥 Annuaire</a>
        <a href="{{ route('gallery.index') }}" class="rounded-xl border border-cyan-400/30 bg-cyan-500/10 px-4 py-2.5 text-sm font-semibold text-cyan-100 transition hover:bg-cyan-500/20">🖼️ Galerie</a>
    </section>

    @if (is_null($member))
        <div class="mt-6 rounded-2xl border border-amber-400/30 bg-amber-500/10 p-6 text-amber-100">
            <p class="font-semibold">Votre fiche membre n'est pas encore rattachée à votre compte.</p>
            <p class="mt-1 text-sm text-amber-100/80">Demandez au secrétariat de vous ajouter au répertoire avec l'adresse email <strong>{{ auth()->user()->email }}</strong>.</p>
        </div>
    @else
        <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-white/10 bg-slate-900/40 p-5">
                <p class="text-sm text-slate-400">Département</p>
                <p class="mt-2 text-2xl font-bold text-white">{{ $member->dept ?? '—' }}</p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-slate-900/40 p-5">
                <p class="text-sm text-slate-400">Total relevés</p>
                <p class="mt-2 text-3xl font-black text-white">{{ $stats['total'] ?? 0 }}</p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-slate-900/40 p-5">
                <p class="text-sm text-slate-400">Excusé(e)</p>
                <p class="mt-2 text-3xl font-black text-sky-400">{{ $stats['excused'] ?? 0 }}</p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-slate-900/40 p-5">
                <p class="text-sm text-slate-400">Retards / absences</p>
                <p class="mt-2 text-3xl font-black text-white">{{ $stats['late'] ?? 0 }} / {{ $stats['absent'] ?? 0 }}</p>
            </div>
        </div>

        @if (($stats['total'] ?? 0) > 0)
            <div class="mt-5 rounded-2xl border border-white/10 bg-slate-900/40 p-5">
                <div class="flex h-4 overflow-hidden rounded-full bg-slate-800">
                    @if ($stats['present'] > 0)
                        <div class="flex items-center justify-center bg-emerald-500" style="width: {{ round($stats['present'] / $stats['total'] * 100) }}%"></div>
                    @endif
                    @if ($stats['late'] > 0)
                        <div class="flex items-center justify-center bg-amber-500" style="width: {{ round($stats['late'] / $stats['total'] * 100) }}%"></div>
                    @endif
                    @if ($stats['excused'] > 0)
                        <div class="flex items-center justify-center bg-sky-500" style="width: {{ round($stats['excused'] / $stats['total'] * 100) }}%"></div>
                    @endif
                    @if ($stats['absent'] > 0)
                        <div class="flex items-center justify-center bg-rose-500" style="width: {{ round($stats['absent'] / $stats['total'] * 100) }}%"></div>
                    @endif
                </div>
                <p class="mt-2 text-xs text-slate-400">P = présent · R = retard · E = excusé · A = absent — sur {{ $stats['total'] }} relevés</p>
            </div>
        @endif

        @if ($recent->isNotEmpty())
            <section class="mt-8">
                <div class="mb-3 flex items-center justify-between gap-3">
                    <h2 class="text-xl font-bold text-white">📌 Dernières participations</h2>
                </div>
                <div class="overflow-x-auto rounded-2xl border border-white/10 bg-slate-900/40">
                    <table class="w-full text-sm text-slate-200">
                        <thead class="bg-slate-800/70 text-left text-xs uppercase tracking-[0.12em] text-slate-400">
                            <tr>
                                <th class="px-4 py-3">Événement</th>
                                <th class="px-4 py-3">Date</th>
                                <th class="px-4 py-3">Statut</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @foreach ($recent as $attendance)
                                <tr>
                                    <td class="px-4 py-3 font-medium text-white">{{ $attendance->event->name }}</td>
                                    <td class="px-4 py-3 text-slate-300">{{ $attendance->event->date->translatedFormat('d/m/Y') }}</td>
                                    <td class="px-4 py-3">
                                        <span class="rounded-full px-2.5 py-1 text-xs font-bold
                                            {{ $attendance->status === 'present' ? 'bg-emerald-500/20 text-emerald-300' : ($attendance->status === 'late' ? 'bg-amber-500/20 text-amber-300' : ($attendance->status === 'excused' ? 'bg-sky-500/20 text-sky-300' : 'bg-rose-500/20 text-rose-300')) }}">
                                            {{ match($attendance->status) { 'present' => 'Présent', 'late' => 'En retard', 'excused' => 'Excusé', default => 'Absent' } }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
    @endif

    <section class="mt-8">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-xl font-bold text-white">🤝 Mes visites et accompagnements</h2>
            <a href="{{ route('social-visits.index') }}" class="text-sm font-semibold text-cyan-300 hover:text-cyan-200">Voir toutes les visites</a>
        </div>

        @if ($visits->isNotEmpty())
            <div class="overflow-x-auto rounded-2xl border border-white/10 bg-slate-900/40">
                <table class="w-full text-sm text-slate-200">
                    <thead class="bg-slate-800/70 text-left text-xs uppercase tracking-[0.12em] text-slate-400">
                        <tr>
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3">Motif</th>
                            <th class="px-4 py-3">Statut</th>
                            <th class="px-4 py-3">Responsable</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @foreach ($visits as $visit)
                            <tr>
                                <td class="px-4 py-3 text-slate-300">{{ $visit->visit_date->translatedFormat('d/m/Y · H\hi') }}</td>
                                <td class="px-4 py-3 font-medium text-white">{{ $visit->reason }}</td>
                                <td class="px-4 py-3"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $visit->status === 'completed' ? 'bg-emerald-500/20 text-emerald-300' : ($visit->status === 'cancelled' ? 'bg-rose-500/20 text-rose-300' : 'bg-amber-500/20 text-amber-300') }}">{{ match($visit->status) { 'completed' => 'Terminée', 'cancelled' => 'Annulée', default => 'Planifiée' } }}</span></td>
                                <td class="px-4 py-3 text-slate-300">{{ $visit->assignee?->full_name ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="mt-3 rounded-2xl border border-dashed border-white/10 bg-slate-900/20 p-6 text-sm text-slate-300">Aucune visite sociale n'est actuellement planifiée pour vous.</p>
        @endif
    </section>

    @if (auth()->user()->isSocialResponsable())
        <section class="mt-6 rounded-2xl border border-indigo-400/25 bg-indigo-500/10 p-5">
            <h2 class="font-bold text-white">Espace Responsable Social</h2>
            <p class="mt-1 text-sm text-slate-200">Planifiez les visites, assignez les équipes et renseignez les comptes-rendus d'accompagnement.</p>
            <div class="mt-3 flex flex-wrap gap-3">
                <a href="{{ route('social-visits.create') }}" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Planifier une visite</a>
                <a href="{{ route('members.index', ['dept' => 'Social']) }}" class="rounded-xl border border-indigo-300/40 px-4 py-2 text-sm font-semibold text-indigo-100 hover:bg-indigo-500/10">Gérer l'équipe</a>
                <a href="{{ route('attendances.pick') }}" class="rounded-xl border border-indigo-300/40 px-4 py-2 text-sm font-semibold text-indigo-100 hover:bg-indigo-500/10">Prendre les présences</a>
            </div>
        </section>
    @elseif (auth()->user()->managesMedia())
        <section class="mt-6 rounded-2xl border border-cyan-400/25 bg-cyan-500/10 p-5">
            <h2 class="font-bold text-white">Espace Médias & Communication</h2>
            <p class="mt-1 text-sm text-slate-200">Gérez le direct vidéo et la galerie photo de la jeunesse.</p>
            <div class="mt-3 flex flex-wrap gap-3">
                <a href="{{ route('live.edit') }}" class="rounded-xl bg-cyan-600 px-4 py-2 text-sm font-semibold text-white hover:bg-cyan-500">Gérer le direct</a>
                <a href="{{ route('gallery.upload') }}" class="rounded-xl border border-cyan-300/40 px-4 py-2 text-sm font-semibold text-cyan-100 hover:bg-cyan-500/10">Publier des photos</a>
            </div>
        </section>
    @endif

    @if ($upcoming->isNotEmpty())
        <section class="mt-8">
            <div class="mb-3 flex items-center justify-between gap-3">
                <h2 class="text-xl font-bold text-white">📅 À venir</h2>
            </div>
            <div class="grid gap-4 md:grid-cols-3">
                @foreach ($upcoming as $event)
                    <div class="rounded-2xl border border-white/10 bg-slate-900/40 p-5">
                        <p class="text-xs font-bold uppercase tracking-[0.14em] text-cyan-300">{{ $event->date->translatedFormat('l d F') }} · {{ $event->date->format('H\hi') }}</p>
                        <p class="mt-2 font-semibold text-white">{{ $event->name }}</p>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</div>
@endsection
