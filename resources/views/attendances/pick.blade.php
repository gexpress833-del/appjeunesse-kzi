@extends('layouts.app')

@section('title', 'Présences')

@section('content')
<h1 class="text-2xl font-bold text-slate-900">Prise de présence</h1>

<div class="mt-6 grid gap-6 lg:grid-cols-2">
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-bold text-slate-900">Événements à venir</h2>
        <div class="mt-4 space-y-3">
            @forelse ($upcoming as $event)
                <div class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 p-3">
                    <div>
                        <p class="font-semibold text-slate-900">{{ $event->name }}</p>
                        <p class="text-sm text-slate-500">{{ $event->date->translatedFormat('d/m/Y · H\hi') }}</p>
                    </div>
                    <a href="{{ route('attendances.sheet', ['event' => $event, 'dept' => $dept ?? request('dept')]) }}" class="rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Ouvrir</a>
                </div>
            @empty
                <p class="text-sm text-slate-500">Aucun événement à venir.</p>
            @endforelse
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-bold text-slate-900">Sélection du département</h2>
        @if (auth()->user()->isResponsable())
            <div class="mt-4 rounded-xl bg-emerald-50 p-4 text-sm text-emerald-800">
                Vous êtes responsable du département <strong>{{ auth()->user()->dept }}</strong>.
            </div>
        @else
            <form method="GET" action="{{ route('attendances.pick') }}" class="mt-4 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Département</label>
                    <select name="dept" class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">— Choisir —</option>
                        <option value="__none__" @selected(request('dept') === '__none__')>Fidèles sans département</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->name }}" @selected(request('dept') === $department->name)>{{ $department->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Valider</button>
            </form>
        @endif
    </div>
</div>
@endsection
