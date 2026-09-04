@extends('layouts.app')

@section('title', 'Visites sociales')

@section('content')
<div class="flex flex-wrap items-center justify-between gap-3">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Visites sociales</h1>
        <p class="mt-1 text-sm text-slate-500">Planification et suivi de l'accompagnement des membres.</p>
    </div>
    @if (auth()->user()->isAdmin() || auth()->user()->isSecretariat() || auth()->user()->isSocialResponsable())
        <a href="{{ route('social-visits.create') }}" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">➕ Planifier une visite</a>
    @endif
</div>

<div class="mt-6 overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
            <tr><th class="px-4 py-3">Membre</th><th class="px-4 py-3">Date</th><th class="px-4 py-3">Motif</th><th class="px-4 py-3">Statut</th><th class="px-4 py-3">Assigné à</th><th class="px-4 py-3"></th></tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($visits as $visit)
                <tr>
                    <td class="px-4 py-3 font-semibold text-slate-900">{{ $visit->member->name }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $visit->visit_date->translatedFormat('d/m/Y · H\hi') }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $visit->reason }}</td>
                    <td class="px-4 py-3"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $visit->status === 'completed' ? 'bg-emerald-100 text-emerald-700' : ($visit->status === 'cancelled' ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700') }}">{{ match($visit->status) { 'completed' => 'Terminée', 'cancelled' => 'Annulée', default => 'Planifiée' } }}</span></td>
                    <td class="px-4 py-3 text-slate-600">{{ $visit->assignee?->full_name ?? '—' }}</td>
                    <td class="px-4 py-3 text-right">
                        @if (auth()->user()->isAdmin() || auth()->user()->isSecretariat() || auth()->user()->isSocialResponsable())
                            <a href="{{ route('social-visits.edit', $visit) }}" class="font-semibold text-indigo-600 hover:underline">Modifier</a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-10 text-center text-slate-500">Aucune visite sociale enregistrée.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $visits->links() }}</div>
@endsection
