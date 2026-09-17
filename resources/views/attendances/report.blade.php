@extends('layouts.app')

@section('title', 'Rapports')

@section('content')
<h1 class="text-2xl font-bold text-slate-900">Rapports de présence</h1>

<div class="mt-4 flex flex-wrap gap-2 text-xs font-bold">
    <span class="rounded-full bg-emerald-100 px-3 py-1 text-emerald-700">● Présent · confirmé</span>
    <span class="rounded-full bg-amber-100 px-3 py-1 text-amber-700">● En retard · arrivée tardive</span>
    <span class="rounded-full bg-sky-100 px-3 py-1 text-sky-700">● Excusé · absence justifiée</span>
    <span class="rounded-full bg-rose-100 px-3 py-1 text-rose-700">● Absent · non présent</span>
</div>

<form method="GET" action="{{ route('attendances.report') }}" class="mt-6 grid gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm md:grid-cols-5">
    <div>
        <label class="block text-xs font-medium text-slate-500">Événement</label>
        <select name="event_id" class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
            <option value="">Tous</option>
            @foreach ($events as $eventItem)
                <option value="{{ $eventItem->id }}" @selected(request('event_id') == $eventItem->id)>{{ $eventItem->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs font-medium text-slate-500">Département</label>
        <select name="dept" class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
            <option value="">Tous</option>
            @foreach ($departments as $department)
                <option value="{{ $department->name }}" @selected(request('dept') === $department->name)>{{ $department->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs font-medium text-slate-500">Statut</label>
        <select name="status" class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
            <option value="">Tous</option>
            @foreach (['present' => 'Présent', 'late' => 'En retard', 'excused' => 'Excusé', 'absent' => 'Absent'] as $value => $label)
                <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs font-medium text-slate-500">Du</label>
        <input type="date" name="from" value="{{ request('from') }}" class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
    </div>
    <div>
        <label class="block text-xs font-medium text-slate-500">Au</label>
        <input type="date" name="to" value="{{ request('to') }}" class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
    </div>
    <div class="md:col-span-5 flex flex-wrap gap-3">
        <button class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500">Filtrer</button>
        @if (auth()->user()->isAdmin() || auth()->user()->isSecretariat() || auth()->user()->isResponsable())
            <a href="{{ route('attendances.pdf', request()->query()) }}" class="rounded-xl bg-gradient-to-r from-emerald-600 to-teal-500 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:from-emerald-500 hover:to-teal-400">Télécharger le rapport PDF</a>
        @endif
    </div>
</form>

@if ($summary->isNotEmpty())
    <section class="mt-8 rounded-2xl border border-indigo-200 bg-indigo-50/60 p-5 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-lg font-bold text-slate-900">Statistiques globales</h2>
                <p class="mt-1 text-sm text-slate-600">Tous les départements inclus dans la sélection actuelle.</p>
            </div>
            @if (request('dept'))
                <span class="rounded-full bg-white px-3 py-1 text-xs font-bold text-indigo-700 shadow-sm">Département filtré : {{ request('dept') }}</span>
            @endif
        </div>
        <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
            <div class="rounded-xl bg-white p-3 shadow-sm"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Taux global</p><p class="mt-1 text-2xl font-bold text-indigo-700">{{ $overall['rate'] }}%</p></div>
            <div class="rounded-xl bg-white p-3 shadow-sm"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Présents</p><p class="mt-1 text-2xl font-bold text-emerald-600">{{ $overall['present'] }}</p></div>
            <div class="rounded-xl bg-white p-3 shadow-sm"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">En retard</p><p class="mt-1 text-2xl font-bold text-amber-600">{{ $overall['late'] }}</p></div>
            <div class="rounded-xl bg-white p-3 shadow-sm"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Excusés</p><p class="mt-1 text-2xl font-bold text-sky-600">{{ $overall['excused'] }}</p></div>
            <div class="rounded-xl bg-white p-3 shadow-sm"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Absents</p><p class="mt-1 text-2xl font-bold text-rose-600">{{ $overall['absent'] }}</p></div>
        </div>
        <p class="mt-3 text-xs text-slate-500">{{ $overall['present'] + $overall['late'] }} présence(s) confirmée(s) ou en retard sur {{ $overall['total'] }} relevé(s).</p>
    </section>

    <section class="mt-8">
        <h2 class="text-lg font-bold text-slate-900">Statistiques par département</h2>
        <p class="mt-1 text-sm text-slate-500">Sélectionnez un département dans les filtres pour afficher uniquement son bilan.</p>
        <div class="mt-3 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($summary as $item)
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-sm font-semibold text-slate-900">{{ $item->dept ?? 'Fidèles sans département' }}</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ $item->rate }}%</p>
                <p class="text-xs text-slate-500">{{ $item->present + $item->late }} / {{ $item->total }} présents ou en retard</p>
            </div>
        @endforeach
        </div>
    </section>
@endif

<section class="mt-8">
    <div class="mb-3 flex items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-bold text-slate-900">Historique par événement</h2>
            <p class="text-sm text-slate-500">Chaque événement ayant au moins une présence enregistrée.</p>
        </div>
    </div>
    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($eventHistory as $historyEvent)
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wide text-indigo-600">{{ $historyEvent->date->translatedFormat('d F Y · H\hi') }}</p>
                <h3 class="mt-1 font-bold text-slate-900">{{ $historyEvent->name }}</h3>
                <p class="mt-1 text-xs text-slate-500">{{ $historyEvent->recorded_attendances }} présence(s) dans cette sélection</p>
                <a href="{{ route('attendances.pdf', array_merge(request()->query(), ['event_id' => $historyEvent->id])) }}" class="mt-3 inline-flex rounded-lg bg-emerald-600 px-3 py-2 text-xs font-bold text-white hover:bg-emerald-500">PDF de cet événement</a>
            </article>
        @empty
            <p class="text-sm text-slate-500">Aucun événement ne possède encore de présence enregistrée.</p>
        @endforelse
    </div>
</section>

<div class="mt-8 overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
            <tr>
                <th class="px-4 py-3">Événement</th>
                <th class="px-4 py-3">Département</th>
                <th class="px-4 py-3">Membre</th>
                <th class="px-4 py-3">Statut</th>
                <th class="px-4 py-3">Notes</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($rows as $row)
                <tr>
                    <td class="px-4 py-3 text-slate-700">{{ $row->event->name }}</td>
                    <td class="px-4 py-3 text-slate-700">{{ $row->member->dept ?? 'Sans département' }}</td>
                    <td class="px-4 py-3 font-medium text-slate-900">{{ $row->member->name }}</td>
                    <td class="px-4 py-3">
                        <span class="rounded-full px-2.5 py-1 text-xs font-bold
                            {{ $row->status === 'present' ? 'bg-emerald-100 text-emerald-700' : ($row->status === 'late' ? 'bg-amber-100 text-amber-700' : ($row->status === 'excused' ? 'bg-sky-100 text-sky-700' : 'bg-rose-100 text-rose-700')) }}">
                            {{ match($row->status) { 'present' => 'Présent', 'late' => 'En retard', 'excused' => 'Excusé', default => 'Absent' } }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-slate-500">{{ $row->notes ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-10 text-center text-slate-500">Aucune donnée correspondante.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $rows->links() }}</div>
@endsection
