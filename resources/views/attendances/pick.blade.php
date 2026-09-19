@extends('layouts.app')

@section('title', 'Présences')

@section('content')
<div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <h1 class="text-2xl font-bold text-slate-900">Prise de présence</h1>
    @if (auth()->user()->isResponsable())
        <a href="{{ route('attendances.pdf') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-500 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:from-emerald-500 hover:to-teal-400">
            <span aria-hidden="true">📄</span>
            Exporter mes rapports PDF
        </a>
    @endif
</div>

<div class="mt-6 grid gap-6 lg:grid-cols-1">
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-lg font-bold text-slate-900">Événements à venir</h2>
            @if (auth()->user()->isResponsable())
                <div class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold uppercase tracking-wide text-emerald-700">
                    Département : {{ auth()->user()->dept }}
                </div>
            @endif
        </div>

        <div class="mt-4 space-y-3">
            @forelse ($upcoming as $event)
                <div class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 p-3">
                    <div>
                        <p class="font-semibold text-slate-900">{{ $event->name }}</p>
                        <p class="text-sm text-slate-500">{{ $event->date->translatedFormat('d/m/Y · H\hi') }}</p>
                    </div>
                    <div class="flex flex-wrap items-center justify-end gap-2">
                        @if (auth()->user()->isResponsable())
                            <a href="{{ route('attendances.sheet', ['event' => $event, 'dept' => auth()->user()->dept]) }}" class="action-link rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Présences</a>
                            <a href="{{ route('attendances.pdf', ['event_id' => $event->id]) }}" class="action-link rounded-lg bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-500">PDF</a>
                        @else
                            <div class="w-full min-w-64 text-right">
                                <p class="mb-2 text-xs font-bold uppercase tracking-wide text-slate-500">Département</p>
                                <div class="flex flex-wrap justify-end gap-2">
                                    @foreach ($departments as $department)
                                        <a href="{{ route('attendances.sheet', ['event' => $event, 'dept' => $department->name]) }}" class="action-link rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-500">{{ $department->name }}</a>
                                    @endforeach
                                    <a href="{{ route('attendances.sheet', ['event' => $event, 'dept' => '__none__']) }}" class="action-link rounded-lg bg-slate-700 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-600">Sans département</a>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-sm text-slate-500">Aucun événement à venir.</p>
            @endforelse
        </div>

        @if ($past->isNotEmpty())
            <div class="mt-8 border-t border-slate-200 pt-6">
                <h2 class="text-lg font-bold text-slate-900">Événements passés</h2>
                <div class="mt-4 space-y-3">
                    @foreach ($past as $event)
                        <div class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50/60 p-3">
                            <div>
                                <p class="font-semibold text-slate-900">{{ $event->name }}</p>
                                <p class="text-sm text-slate-500">{{ $event->date->translatedFormat('d/m/Y · H\hi') }}</p>
                            </div>
                            <div class="flex flex-wrap items-center justify-end gap-2">
                                @if (auth()->user()->isResponsable())
                                    <a href="{{ route('attendances.sheet', ['event' => $event, 'dept' => auth()->user()->dept]) }}" class="action-link rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Présences</a>
                                    <a href="{{ route('attendances.pdf', ['event_id' => $event->id]) }}" class="action-link rounded-lg bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-500">PDF</a>
                                @else
                                    <div class="flex max-w-2xl flex-wrap justify-end gap-2">
                                        @foreach ($departments as $department)
                                            <a href="{{ route('attendances.sheet', ['event' => $event, 'dept' => $department->name]) }}" class="action-link rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-500">{{ $department->name }}</a>
                                        @endforeach
                                        <a href="{{ route('attendances.sheet', ['event' => $event, 'dept' => '__none__']) }}" class="action-link rounded-lg bg-slate-700 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-600">Sans département</a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
