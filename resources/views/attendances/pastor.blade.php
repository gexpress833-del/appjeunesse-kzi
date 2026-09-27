@extends('layouts.app')

@section('title', 'Statistiques des présences')

@section('content')
<div>
    <h1 class="text-2xl font-bold text-slate-900">Statistiques des présences par département</h1>
    <p class="mt-2 text-sm text-slate-600">Vue globale en lecture seule. La saisie des présences reste réservée aux responsables de département.</p>
</div>

<form method="GET" action="{{ route('attendances.pick') }}" class="mt-6 grid gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm md:grid-cols-4">
    <div class="md:col-span-2">
        <label for="attendance-event" class="block text-xs font-medium text-slate-600">Événement</label>
        <select id="attendance-event" name="event_id" class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
            <option value="">Tous les événements</option>
            @foreach ($events as $event)
                <option value="{{ $event->id }}" @selected((string) ($filters['event_id'] ?? '') === (string) $event->id)>
                    {{ $event->name }} · {{ $event->date->format('d/m/Y H:i') }}
                </option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="attendance-from" class="block text-xs font-medium text-slate-600">Du</label>
        <input id="attendance-from" type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
    </div>
    <div>
        <label for="attendance-to" class="block text-xs font-medium text-slate-600">Au</label>
        <input id="attendance-to" type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
    </div>
    <div class="flex flex-wrap items-center gap-3 md:col-span-4">
        <button class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500">Filtrer</button>
        <a href="{{ route('attendances.pick') }}" class="text-sm font-semibold text-slate-600 hover:underline">Effacer les filtres</a>
    </div>
</form>

<p class="mt-4 rounded-xl border border-indigo-200 bg-indigo-50/70 px-4 py-3 text-sm text-indigo-950">
    <span class="font-semibold">Périmètre affiché :</span>
    @if ($selectedEvent)
        Événement « {{ $selectedEvent->name }} » ({{ $selectedEvent->date->format('d/m/Y H:i') }}).
    @else
        Tous les événements.
    @endif
    @if (filled($filters['from'] ?? null) || filled($filters['to'] ?? null))
        Période : {{ $filters['from'] ?? 'sans date de début' }} au {{ $filters['to'] ?? 'sans date de fin' }}.
    @else
        Toutes les dates.
    @endif
    Les chiffres comptent les relevés enregistrés, regroupés selon le département du membre.
</p>

<section class="mt-6 overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
            <tr>
                <th class="px-4 py-3">Département</th>
                <th class="px-4 py-3 text-right">Présents</th>
                <th class="px-4 py-3 text-right">En retard</th>
                <th class="px-4 py-3 text-right">Excusés</th>
                <th class="px-4 py-3 text-right">Absents</th>
                <th class="px-4 py-3 text-right">Relevés</th>
                <th class="px-4 py-3 text-right">Taux de présence</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($departmentStats as $department)
                <tr>
                    <th scope="row" class="whitespace-nowrap px-4 py-3 text-left font-semibold text-slate-900">{{ $department->department }}</th>
                    <td class="px-4 py-3 text-right font-semibold text-emerald-700">{{ $department->present }}</td>
                    <td class="px-4 py-3 text-right font-semibold text-amber-700">{{ $department->late }}</td>
                    <td class="px-4 py-3 text-right font-semibold text-sky-700">{{ $department->excused }}</td>
                    <td class="px-4 py-3 text-right font-semibold text-rose-700">{{ $department->absent }}</td>
                    <td class="px-4 py-3 text-right text-slate-700">{{ $department->total }}</td>
                    <td class="px-4 py-3 text-right font-bold text-indigo-700">{{ $department->rate }}%</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-4 py-10 text-center text-slate-500">Aucun département à afficher.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</section>
@endsection