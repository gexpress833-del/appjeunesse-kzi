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

<div class="mt-6 space-y-3">
    @forelse ($notifications as $notification)
        <div class="rounded-2xl border {{ $notification->read_at ? 'border-slate-200 bg-white' : 'border-amber-300 bg-amber-500/5' }} p-4 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-sm font-bold text-slate-900">{{ data_get($notification->data, 'title', 'Notification') }}</p>
                    <p class="mt-1 text-sm text-slate-600">{{ data_get($notification->data, 'message', '') }}</p>
                </div>
                <span class="text-[10px] uppercase tracking-[0.18em] text-slate-400">{{ $notification->created_at->diffForHumans() }}</span>
            </div>
        </div>
    @empty
        <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500">
            Aucune notification pour le moment.
        </div>
    @endforelse
</div>

<div class="mt-4">{{ $notifications->links() }}</div>
@endsection
