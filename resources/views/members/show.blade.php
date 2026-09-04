@extends('layouts.app')

@section('title', $member->name)

@section('content')
<div class="flex flex-wrap items-start justify-between gap-4">
    <div class="flex items-center gap-4">
        @if ($member->profile_photo_url)
            <img src="{{ $member->profile_photo_url }}" class="h-20 w-20 rounded-2xl object-cover shadow" alt="">
        @else
            <span class="flex h-20 w-20 items-center justify-center rounded-2xl bg-indigo-100 text-3xl font-bold text-indigo-700">{{ strtoupper(substr($member->name, 0, 1)) }}</span>
        @endif
        <div>
            <h1 class="text-2xl font-bold text-slate-900">{{ $member->name }}</h1>
            <p class="mt-1 flex flex-wrap items-center gap-2 text-sm text-slate-500">
                <span class="rounded-full bg-slate-100 px-2.5 py-1 font-semibold text-slate-700">{{ $member->dept }}</span>
                @if ($member->phone) <span>📞 {{ $member->phone }}</span> @endif
                @if ($member->email) <span>✉ {{ $member->email }}</span> @endif
            </p>
        </div>
    </div>
    @if (auth()->user()->isAdmin() || auth()->user()->isSecretariat() || (auth()->user()->isResponsable() && auth()->user()->dept === $member->dept))
        <a href="{{ route('members.edit', $member) }}" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Modifier la fiche</a>
    @endif
</div>

<div class="mt-6 grid gap-4 sm:grid-cols-3">
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-sm text-slate-500">Taux de présence</p>
        <p class="mt-1 text-3xl font-bold {{ ($rate ?? 0) >= 70 ? 'text-emerald-600' : 'text-amber-600' }}">{{ $rate ?? 0 }}%</p>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-sm text-slate-500">Relevés de présence</p>
        <p class="mt-1 text-3xl font-bold text-slate-900">{{ $total }}</p>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-sm text-slate-500">Date de naissance</p>
        <p class="mt-1 text-lg font-bold text-slate-900">{{ $member->birth_date?->translatedFormat('d F Y') ?? '—' }}</p>
    </div>
</div>

@if ($member->notes)
    <div class="mt-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-sm font-semibold text-slate-500">Notes</p>
        <p class="mt-1 text-slate-700">{{ $member->notes }}</p>
    </div>
@endif

@if ($attendances->isNotEmpty())
    <h2 class="mt-8 text-lg font-bold text-slate-900">Historique de présence</h2>
    <div class="mt-3 overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                <tr><th class="px-4 py-3">Événement</th><th class="px-4 py-3">Date</th><th class="px-4 py-3">Statut</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($attendances as $attendance)
                    <tr>
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $attendance->event->name }}</td>
                        <td class="px-4 py-3 text-slate-500">{{ $attendance->event->date->translatedFormat('d/m/Y') }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2.5 py-1 text-xs font-bold
                                {{ $attendance->status === 'present' ? 'bg-emerald-100 text-emerald-700' : ($attendance->status === 'late' ? 'bg-amber-100 text-amber-700' : ($attendance->status === 'excused' ? 'bg-sky-100 text-sky-700' : 'bg-rose-100 text-rose-700')) }}">
                                {{ match($attendance->status) { 'present' => 'Présent', 'late' => 'En retard', 'excused' => 'Excusé', default => 'Absent' } }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
@endsection
