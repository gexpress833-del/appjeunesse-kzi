@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
<div class="flex flex-wrap items-center justify-between gap-3">
    <h1 class="text-2xl font-bold text-slate-900">Notifications</h1>
    @if (auth()->user()->unreadNotifications->isNotEmpty())
        <form method="POST" action="{{ route('notifications.read.all') }}">
            @csrf
            <button class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Tout marquer comme lu
            </button>
        </form>
    @endif
</div>

<form method="POST" action="{{ route('notifications.bulk.destroy') }}" class="mt-5">
    @csrf
    <div class="mb-3 flex items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white p-3 shadow-sm">
        <button type="button" id="toggle-notifications-selection" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">
            <span class="toggle-label">Tout sélectionner</span>
        </button>
        <button type="submit" class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-100">
            Supprimer la sélection
        </button>
    </div>

    <div class="space-y-3">
        @forelse ($notifications as $notification)
            <div class="rounded-2xl border {{ $notification->read_at ? 'border-slate-200 bg-white' : 'border-amber-300 bg-amber-500/5' }} p-4 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex min-w-0 flex-1 items-start gap-3">
                        <input type="checkbox" name="notifications[]" value="{{ $notification->id }}" class="mt-1 h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 notification-checkbox">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-bold text-slate-900">{{ data_get($notification->data, 'title', 'Notification') }}</p>
                            <p class="mt-1 text-sm text-slate-600">{{ data_get($notification->data, 'message', '') }}</p>
                        </div>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        <span class="text-[10px] uppercase tracking-[0.18em] text-slate-400">{{ $notification->created_at->diffForHumans() }}</span>
                        @if (! $notification->read_at)
                            <form method="POST" action="{{ route('notifications.read', $notification) }}">
                                @csrf
                                <button class="rounded-lg border border-emerald-200 bg-emerald-50 px-2 py-1 text-[11px] font-semibold text-emerald-700 hover:bg-emerald-100">
                                    Lu
                                </button>
                            </form>
                        @endif
                        <form method="POST" action="{{ route('notifications.destroy', $notification) }}" onsubmit="return confirm('Supprimer cette notification ?');">
                            @csrf
                            @method('DELETE')
                            <button class="rounded-lg border border-rose-200 bg-rose-50 px-2 py-1 text-[11px] font-semibold text-rose-700 hover:bg-rose-100">
                                Supprimer
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500">
                Aucune notification pour le moment.
            </div>
        @endforelse
    </div>
</form>

<div class="mt-4">{{ $notifications->links() }}</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const toggleButton = document.getElementById('toggle-notifications-selection');
        const checkboxes = document.querySelectorAll('.notification-checkbox');
        const toggleLabel = toggleButton?.querySelector('.toggle-label');

        if (!toggleButton || !toggleLabel) return;

        let allSelected = false;

        toggleButton.addEventListener('click', () => {
            allSelected = !allSelected;
            checkboxes.forEach((checkbox) => {
                checkbox.checked = allSelected;
            });
            toggleLabel.textContent = allSelected ? 'Tout désélectionner' : 'Tout sélectionner';
        });
    });
</script>
@endsection
