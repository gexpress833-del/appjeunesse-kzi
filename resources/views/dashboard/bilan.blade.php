@extends('layouts.app')

@section('title', 'Bilan de participation')

@section('content')
<div class="rounded-3xl border border-white/10 bg-slate-950/30 p-6 shadow-2xl shadow-slate-950/20 backdrop-blur-sm">
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[0.18em] text-cyan-300">Bilan</p>
            <h1 class="mt-2 text-3xl font-black text-white">Participation aux cultes et réunions</h1>
        </div>

        <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center rounded-xl border border-cyan-400/40 bg-cyan-500/10 px-4 py-2 text-sm font-semibold text-cyan-200 transition hover:bg-cyan-500/20">
            Retour au tableau de bord
        </a>
    </div>

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
                <p class="text-sm text-slate-400">Taux de participation</p>
                <p class="mt-2 text-3xl font-black {{ ($stats['rate'] ?? 0) >= 70 ? 'text-emerald-400' : 'text-amber-400' }}">{{ $stats['rate'] ?? 0 }}%</p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-slate-900/40 p-5">
                <p class="text-sm text-slate-400">Présent(e)</p>
                <p class="mt-2 text-3xl font-black text-emerald-400">{{ $stats['present'] ?? 0 }}</p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-slate-900/40 p-5">
                <p class="text-sm text-slate-400">Retards / absences</p>
                <p class="mt-2 text-3xl font-black text-white">{{ $stats['late'] ?? 0 }} / {{ $stats['absent'] ?? 0 }}</p>
            </div>
        </div>

        @if (($stats['total'] ?? 0) > 0)
            <div class="mt-5 rounded-2xl border border-white/10 bg-slate-900/40 p-5">
                <div class="flex h-4 overflow-hidden rounded-full bg-slate-800">
                    @if (($stats['present'] ?? 0) > 0)
                        <div class="flex items-center justify-center bg-emerald-500" style="width: {{ round(($stats['present'] / $stats['total']) * 100) }}%"></div>
                    @endif
                    @if (($stats['late'] ?? 0) > 0)
                        <div class="flex items-center justify-center bg-amber-500" style="width: {{ round(($stats['late'] / $stats['total']) * 100) }}%"></div>
                    @endif
                    @if (($stats['excused'] ?? 0) > 0)
                        <div class="flex items-center justify-center bg-sky-500" style="width: {{ round(($stats['excused'] / $stats['total']) * 100) }}%"></div>
                    @endif
                    @if (($stats['absent'] ?? 0) > 0)
                        <div class="flex items-center justify-center bg-rose-500" style="width: {{ round(($stats['absent'] / $stats['total']) * 100) }}%"></div>
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

                <div class="overflow-hidden rounded-2xl border border-white/10 bg-slate-900/40">
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
</div>
@endsection