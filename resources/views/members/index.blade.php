@extends('layouts.app')

@section('title', 'Annuaire des membres')

@section('content')
<div class="flex flex-wrap items-center justify-between gap-3">
    <h1 class="text-2xl font-bold text-slate-900">Annuaire des membres</h1>
    @canany(['admin'], auth()->user())
    @endcanany
    @if (auth()->user()->isAdmin() || auth()->user()->isSecretariat() || auth()->user()->isResponsable())
        <a href="{{ route('members.create') }}" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">➕ Ajouter un membre</a>
    @endif
</div>

<form method="GET" class="mt-4 flex flex-wrap items-end gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
    <div>
        <label class="block text-xs font-medium text-slate-500">Rechercher</label>
        <input name="q" value="{{ request('q') }}" placeholder="Nom, téléphone, email…"
               class="mt-1 rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
    </div>
    @unless (auth()->user()->isResponsable())
        <div>
            <label class="block text-xs font-medium text-slate-500">Département</label>
            <select name="dept" class="mt-1 rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">Tous</option>
                @foreach ($departments as $dept)
                    <option value="{{ $dept->name }}" @selected(request('dept') === $dept->name)>{{ $dept->name }}</option>
                @endforeach
            </select>
        </div>
    @endunless
    <button class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Filtrer</button>
    <a href="{{ route('members.index') }}" class="text-sm text-slate-500 hover:underline">Réinitialiser</a>
</form>

<div class="mt-4 overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
            <tr>
                <th class="px-4 py-3">Membre</th>
                <th class="px-4 py-3">Département</th>
                <th class="px-4 py-3">Téléphone</th>
                <th class="px-4 py-3">Email</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($members as $member)
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3">
                        <a href="{{ route('members.show', $member) }}" class="flex items-center gap-3 font-medium text-slate-900 hover:text-indigo-600">
                            @if ($member->profile_photo_url)
                                <img src="{{ $member->profile_photo_url }}" class="h-9 w-9 rounded-full object-cover" alt="">
                            @else
                                <span class="flex h-9 w-9 items-center justify-center rounded-full bg-indigo-100 text-sm font-bold text-indigo-700">{{ strtoupper(substr($member->name, 0, 1)) }}</span>
                            @endif
                            {{ $member->name }}
                        </a>
                    </td>
                    <td class="px-4 py-3"><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ $member->dept }}</span></td>
                    <td class="px-4 py-3 text-slate-500">{{ $member->phone ?? '—' }}</td>
                    <td class="px-4 py-3 text-slate-500">{{ $member->email ?? '—' }}</td>
                    <td class="px-4 py-3 text-right">
                        @if (auth()->user()->isAdmin() || auth()->user()->isSecretariat() || (auth()->user()->isResponsable() && auth()->user()->dept === $member->dept))
                            <a href="{{ route('members.edit', $member) }}" class="text-indigo-600 hover:underline">Modifier</a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-4 py-10 text-center text-slate-500">Aucun membre trouvé.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $members->links() }}</div>
@endsection
