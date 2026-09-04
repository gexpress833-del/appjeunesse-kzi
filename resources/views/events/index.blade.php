@extends('layouts.app')

@section('title', 'Événements')

@section('content')
<div class="flex flex-wrap items-center justify-between gap-3">
    <h1 class="text-2xl font-bold text-slate-900">Événements</h1>
    @if (auth()->user()->isAdmin() || auth()->user()->isSecretariat() || auth()->user()->isResponsable())
        <a href="{{ route('events.create') }}" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">➕ Créer un événement</a>
    @endif
</div>

<h2 class="mt-6 text-sm font-bold uppercase tracking-wide text-slate-500">À venir</h2>
<div class="mt-3 space-y-3">
    @forelse ($upcoming as $event)
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center gap-4">
                <div class="flex h-14 w-14 flex-col items-center justify-center rounded-xl bg-indigo-50 text-indigo-700">
                    <span class="text-lg font-bold leading-none">{{ $event->date->format('d') }}</span>
                    <span class="text-[10px] font-bold uppercase">{{ $event->date->translatedFormat('M') }}</span>
                </div>
                <div>
                    <p class="font-semibold text-slate-900">{{ $event->name }}</p>
                    <p class="text-sm text-slate-500">{{ $event->date->translatedFormat('l d F Y') }} · {{ $event->date->format('H\hi') }} · {{ $event->members_count }} présence(s)</p>
                    <p class="mt-1 text-xs font-semibold uppercase tracking-wide {{ $event->dept ? 'text-indigo-600' : 'text-emerald-600' }}">
                        {{ $event->dept ? 'Département : '.$event->dept : 'Événement global' }}
                    </p>
                </div>
            </div>
            @if (auth()->user()->isAdmin() || auth()->user()->isSecretariat() || auth()->user()->isResponsable())
                <div class="flex items-center gap-3 text-sm">
                    <a href="{{ route('attendances.sheet', $event) }}" class="rounded-lg bg-emerald-50 px-3 py-2 font-semibold text-emerald-700 hover:bg-emerald-100">✅ Faire l'appel</a>
                    @if (auth()->user()->isAdmin() || auth()->user()->isSecretariat())
                        <a href="{{ route('events.edit', $event) }}" class="text-indigo-600 hover:underline">Modifier</a>
                    @endif
                </div>
            @endif
        </div>
    @empty
        <p class="rounded-2xl border border-dashed border-slate-300 bg-white p-6 text-center text-slate-500">Aucun événement à venir.</p>
    @endforelse
</div>
{{ $upcoming->links() }}

<h2 class="mt-10 text-sm font-bold uppercase tracking-wide text-slate-500">Passés</h2>
<div class="mt-3 space-y-3">
    @forelse ($past as $event)
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white/60 p-4 shadow-sm">
            <div class="flex items-center gap-4 opacity-80">
                <div class="flex h-12 w-12 flex-col items-center justify-center rounded-lg bg-slate-100 text-slate-500">
                    <span class="text-base font-bold leading-none">{{ $event->date->format('d') }}</span>
                    <span class="text-[10px] font-bold uppercase">{{ $event->date->translatedFormat('M') }}</span>
                </div>
                <div>
                    <p class="font-semibold text-slate-700">{{ $event->name }}</p>
                    <p class="text-xs text-slate-400">{{ $event->date->translatedFormat('d/m/Y') }} · {{ $event->members_count }} présence(s)</p>
                </div>
            </div>
            @if (auth()->user()->isAdmin() || auth()->user()->isSecretariat() || auth()->user()->isResponsable())
                <a href="{{ route('attendances.sheet', $event) }}" class="text-sm text-slate-500 hover:underline">Voir l'appel</a>
            @endif
        </div>
    @empty
        <p class="text-sm text-slate-400">Aucun événement passé.</p>
    @endforelse
</div>
{{ $past->links() }}
@endsection
