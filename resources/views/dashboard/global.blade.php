@extends('layouts.app')

@section('title', 'Tableau de bord')

@section('content')
<div class="flex items-center justify-between">
    <h1 class="text-2xl font-bold text-slate-900">Tableau de bord</h1>
    <span class="rounded-full bg-indigo-100 px-3 py-1 text-xs font-bold uppercase text-indigo-700">
        {{ auth()->user()->isAdmin() ? 'Administration' : 'Secrétariat' }}
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
