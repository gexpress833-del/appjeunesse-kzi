@extends('layouts.app')

@section('title', 'Direct vidéo')

@section('content')
<h1 class="text-2xl font-bold text-slate-900">Direct vidéo</h1>

<form method="POST" action="{{ route('live.save') }}" class="mt-6 max-w-2xl space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    @csrf

    <div>
        <label class="block text-sm font-medium text-slate-700">Titre</label>
        <input name="title" value="{{ old('title', $live?->title ?? 'Culte en direct') }}"
               class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
    </div>

    <div>
        <label for="broadcast_type" class="block text-sm font-medium text-slate-700">Type de diffusion</label>
        <select id="broadcast_type" name="broadcast_type" class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
            <option value="live" @selected(old('broadcast_type', $live?->broadcast_type ?? 'live') === 'live')>En direct</option>
            <option value="replay" @selected(old('broadcast_type', $live?->broadcast_type ?? 'live') === 'replay')>Retransmission</option>
        </select>
    </div>

    <div>
        <label for="media_url" class="block text-sm font-medium text-slate-700">Lien vidéo</label>
        <div class="mt-1 flex gap-2">
            <input id="media_url" name="media_url" type="text" inputmode="url" autocomplete="url" autocapitalize="none" spellcheck="false"
                   value="{{ old('media_url', $live?->media_url) }}" placeholder="https://youtube.com/watch?..."
                   class="w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
            <button type="button" data-paste-url="media_url" class="shrink-0 rounded-xl border border-indigo-200 bg-indigo-50 px-3 text-sm font-semibold text-indigo-700 hover:bg-indigo-100">
                Coller
            </button>
    </div>
        <p class="mt-2 text-xs text-slate-500">Collez un lien YouTube ou Facebook public.</p>

    <div>
        <label class="block text-sm font-medium text-slate-700">Description</label>
        <textarea name="content" rows="4"
                  class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">{{ old('content', $live?->content) }}</textarea>
    </div>

    <label class="flex items-center gap-3 text-sm text-slate-700">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $live?->is_active ?? false)) class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
        Activer le direct sur la page d’accueil
    </label>

    <button class="rounded-xl bg-indigo-600 px-6 py-3 font-semibold text-white hover:bg-indigo-500">Enregistrer</button>
</form>

<script>
    document.querySelectorAll('[data-paste-url]').forEach((button) => {
        button.addEventListener('click', async () => {
            const input = document.getElementById(button.dataset.pasteUrl);

            if (!input) return;

            input.focus();

            try {
                input.value = (await navigator.clipboard.readText()).trim();
                input.dispatchEvent(new Event('input', { bubbles: true }));
            } catch {
                button.textContent = 'Maintenez puis collez';
                window.setTimeout(() => { button.textContent = 'Coller'; }, 2200);
            }
        });
    });
</script>
@endsection
