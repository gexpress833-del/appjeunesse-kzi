@extends('layouts.app')

@section('title', 'Rapports')

@section('content')
<h1 class="text-2xl font-bold text-slate-900">Rapports de présence</h1>

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
    <div class="md:col-span-5 flex gap-3">
        <button class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Filtrer</button>
        <a href="{{ route('attendances.export', request()->query()) }}" class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">Exporter CSV</a>
    </div>
</form>

@if ($summary->isNotEmpty())
    <div class="mt-8 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($summary as $item)
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-sm font-semibold text-slate-900">{{ $item->dept }}</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ $item->rate }}%</p>
                <p class="text-xs text-slate-500">{{ $item->present + $item->late }} / {{ $item->total }} présents ou en retard</p>
            </div>
        @endforeach
    </div>
@endif

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
                    <td class="px-4 py-3 text-slate-700">{{ $row->member->dept }}</td>
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
