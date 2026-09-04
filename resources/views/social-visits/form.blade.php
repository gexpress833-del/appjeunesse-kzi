@extends('layouts.app')

@section('title', $visit->exists ? 'Modifier une visite sociale' : 'Planifier une visite sociale')

@section('content')
<h1 class="text-2xl font-bold text-slate-900">{{ $visit->exists ? 'Modifier la visite sociale' : 'Planifier une visite sociale' }}</h1>

<form method="POST" action="{{ $visit->exists ? route('social-visits.update', $visit) : route('social-visits.store') }}" class="mt-6 max-w-2xl space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    @csrf
    @if ($visit->exists) @method('PUT') @endif

    <div>
        <label class="block text-sm font-medium text-slate-700">Membre visité *</label>
        <select name="member_id" required class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
            <option value="">— Choisir un membre —</option>
            @foreach ($members as $member)
                <option value="{{ $member->id }}" @selected((string) old('member_id', $visit->member_id) === (string) $member->id)>{{ $member->name }} — {{ $member->dept }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Date et heure *</label>
        <input name="visit_date" type="datetime-local" value="{{ old('visit_date', $visit->visit_date?->format('Y-m-d\TH:i')) }}" required class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Motif *</label>
        <select name="reason" required class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
            @foreach (['Suivi d’absence', 'Encouragement', 'Cas social', 'Nouveau venu', 'Autre'] as $reason)
                <option value="{{ $reason }}" @selected(old('reason', $visit->reason) === $reason)>{{ $reason }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Équipe ou responsable assigné</label>
        <select name="assigned_to" class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
            <option value="">— Non assigné —</option>
            @foreach ($assignees as $assignee)
                <option value="{{ $assignee->id }}" @selected((string) old('assigned_to', $visit->assigned_to) === (string) $assignee->id)>{{ $assignee->full_name }} — {{ $assignee->role }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Statut *</label>
        <select name="status" required class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
            @foreach (['planned' => 'Planifiée', 'completed' => 'Terminée', 'cancelled' => 'Annulée'] as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $visit->status ?? 'planned') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700">Compte-rendu</label>
        <textarea name="report_notes" rows="5" class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">{{ old('report_notes', $visit->report_notes) }}</textarea>
    </div>

    <div class="flex items-center gap-3">
        <button class="rounded-xl bg-indigo-600 px-6 py-3 font-semibold text-white hover:bg-indigo-500">{{ $visit->exists ? 'Enregistrer' : 'Planifier la visite' }}</button>
        <a href="{{ route('social-visits.index') }}" class="text-sm text-slate-500 hover:underline">Annuler</a>
    </div>
</form>

@if ($visit->exists)
    <form method="POST" action="{{ route('social-visits.destroy', $visit) }}" class="mt-4" onsubmit="return confirm('Supprimer cette visite sociale ?')">
        @csrf @method('DELETE')
        <button class="text-sm font-semibold text-rose-600 hover:underline">🗑 Supprimer cette visite</button>
    </form>
@endif
@endsection
