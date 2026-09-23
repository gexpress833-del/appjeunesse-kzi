@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
@php($unreadCount = auth()->user()->unreadNotifications()->count())
<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <p class="text-xs font-bold uppercase tracking-[0.22em] text-cyan-500">Centre d’alertes</p>
        <h1 class="mt-1 text-3xl font-black tracking-tight text-slate-900">Notifications</h1>
        <p class="mt-1 text-sm text-slate-500">Retrouvez ici les informations importantes de votre espace.</p>
    </div>
    @if ($unreadCount > 0)
        <form method="POST" action="{{ route('notifications.read.all') }}">
            @csrf
            <button class="inline-flex items-center gap-2 rounded-xl border border-cyan-200 bg-cyan-50 px-4 py-2.5 text-sm font-bold text-cyan-700 transition hover:bg-cyan-100">
                <span aria-hidden="true">✓</span> Tout marquer comme lu
            </button>
        </form>
    @endif
</div>

<form id="bulk-notifications-form" method="POST" action="{{ route('notifications.bulk.destroy') }}" class="mt-6">
    @csrf
    <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white p-3 shadow-sm">
        <button type="button" id="toggle-notifications-selection" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-bold text-slate-700 transition hover:bg-slate-100">
            <span aria-hidden="true">☷</span><span class="toggle-label">Tout sélectionner</span>
        </button>
        <button type="submit" class="inline-flex items-center gap-2 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm font-bold text-rose-700 transition hover:bg-rose-100">
            <span aria-hidden="true">⌫</span> Supprimer la sélection
        </button>
    </div>
</form>

<div class="mt-4 space-y-3">
    @forelse ($notifications as $notification)
        @php
            $notificationType = data_get($notification->data, 'type', 'notification');
            $notificationPresentation = match ($notificationType) {
                'event_created' => ['class' => 'notification-event', 'icon' => '📅', 'label' => 'Événement'],
                'attendance_recorded', 'attendance_batch_recorded' => ['class' => 'notification-attendance', 'icon' => '✅', 'label' => 'Présences'],
                'member_added' => ['class' => 'notification-member', 'icon' => '👥', 'label' => 'Nouveau membre'],
                'role_updated' => ['class' => 'notification-role', 'icon' => '🔑', 'label' => 'Rôle et accès'],
                'social_visit_assigned' => ['class' => 'notification-social', 'icon' => '🤝', 'label' => 'Suivi social'],
                'account_validated' => ['class' => 'notification-account', 'icon' => '✓', 'label' => 'Compte validé'],
                'live_published', 'replay_published' => ['class' => 'notification-media', 'icon' => '▶', 'label' => 'Média disponible'],
                default => ['class' => 'notification-default', 'icon' => '🔔', 'label' => 'Information'],
            };
        @endphp
        <article class="notification-card {{ $notificationPresentation['class'] }} {{ $notification->read_at ? 'is-read' : 'is-unread' }} group rounded-2xl border p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
            <div class="flex items-start gap-3">
                <input form="bulk-notifications-form" type="checkbox" name="notifications[]" value="{{ $notification->id }}" class="notification-checkbox mt-1 h-4 w-4 rounded border-slate-300 text-cyan-600 focus:ring-cyan-500">
                <div class="flex min-w-0 flex-1 gap-3">
                    <div class="notification-icon flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-lg" aria-hidden="true">{{ $notificationPresentation['icon'] }}</div>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="notification-kicker text-[10px] font-black uppercase tracking-[0.16em]">{{ $notificationPresentation['label'] }}</p>
                            @if (! $notification->read_at)
                                <span class="notification-status rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide">Nouveau</span>
                            @endif
                        </div>
                        <p class="notification-title mt-1 text-base font-black leading-tight text-slate-900">{{ data_get($notification->data, 'title', 'Notification') }}</p>
                        <p class="notification-body mt-1 text-sm leading-6 text-slate-600">{{ data_get($notification->data, 'message', '') }}</p>
                        <div class="notification-meta mt-2 flex flex-wrap items-center gap-2 text-[10px] font-bold uppercase tracking-[0.12em]">
                            <span>{{ $notification->created_at->diffForHumans() }}</span>
                            @if (filled(data_get($notification->data, 'department')))
                                <span>· {{ data_get($notification->data, 'department') }}</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    @if (! $notification->read_at)
                        <form method="POST" action="{{ route('notifications.read', $notification) }}">
                            @csrf
                            <button title="Marquer comme lu" aria-label="Marquer comme lu" class="flex h-9 w-9 items-center justify-center rounded-xl border border-emerald-200 bg-emerald-50 font-bold text-emerald-700 transition hover:bg-emerald-100">✓</button>
                        </form>
                    @endif
                    <form method="POST" action="{{ route('notifications.destroy', $notification) }}" onsubmit="return confirm('Supprimer cette notification ?');">
                        @csrf
                        @method('DELETE')
                        <button title="Supprimer" aria-label="Supprimer" class="flex h-9 w-9 items-center justify-center rounded-xl border border-rose-200 bg-rose-50 font-bold text-rose-700 transition hover:bg-rose-100">×</button>
                    </form>
                </div>
            </div>
        </article>
    @empty
        <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-xl text-slate-400">✓</div>
            <p class="mt-3 text-base font-bold text-slate-700">Tout est calme</p>
            <p class="mt-1 text-sm text-slate-500">Aucune notification pour le moment.</p>
        </div>
    @endforelse
</div>

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
