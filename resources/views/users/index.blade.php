@extends('layouts.app')

@section('title', 'Utilisateurs')

@section('content')
<div class="flex flex-wrap items-center justify-between gap-3">
    <h1 class="text-2xl font-bold text-slate-900">Utilisateurs</h1>
    <a href="{{ route('users.create') }}" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">➕ Créer un compte</a>
</div>

<form method="GET" class="mt-6 grid gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm md:grid-cols-4">
    <div>
        <label class="block text-xs font-medium text-slate-500">Statut</label>
        <select name="status" class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
            <option value="">Tous</option>
            @foreach (['pending' => 'En attente', 'active' => 'Actif', 'inactive' => 'Inactif'] as $value => $label)
                <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs font-medium text-slate-500">Rôle</label>
        <select name="role" class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
            <option value="">Tous</option>
            @foreach (['admin' => 'Admin', 'secretariat' => 'Secrétariat', 'responsable' => 'Responsable', 'user' => 'Membre'] as $value => $label)
                <option value="{{ $value }}" @selected(request('role') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="md:col-span-2 flex items-end gap-3">
        <button class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Filtrer</button>
        <a href="{{ route('users.index') }}" class="text-sm text-slate-500 hover:underline">Réinitialiser</a>
    </div>
</form>

<div class="mt-6 overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
            <tr>
                <th class="px-4 py-3">Nom</th>
                <th class="px-4 py-3">Rôle</th>
                <th class="px-4 py-3">Statut</th>
                <th class="px-4 py-3">Département</th>
                <th class="px-4 py-3">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($users as $userItem)
                <tr>
                    <td class="px-4 py-3">
                        <div class="font-medium text-slate-900">{{ $userItem->full_name }}</div>
                        <div class="text-xs text-slate-500">{{ $userItem->email }}</div>
                    </td>
                    <td class="px-4 py-3">
                        <span class="rounded-full bg-indigo-100 px-2.5 py-1 text-xs font-semibold text-indigo-700">{{ $userItem->role }}</span>
                    </td>
                    <td class="px-4 py-3">
                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold
                            {{ $userItem->status === 'active' ? 'bg-emerald-100 text-emerald-700' : ($userItem->status === 'pending' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-600') }}">
                            {{ $userItem->status }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-slate-600">{{ $userItem->dept ?? '—' }}</td>
                    <td class="px-4 py-3">
                        <div class="flex min-w-64 flex-col gap-2">
                            @if ($userItem->status === 'pending')
                                <form method="POST" action="{{ route('users.validate', $userItem) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="text-sm font-semibold text-emerald-600 hover:underline">Valider le compte</button>
                                </form>
                            @endif

                            @if (! $userItem->isPrimaryAdmin() || auth()->id() === $userItem->id)
                                <form method="POST" action="{{ route('users.role', $userItem) }}" class="flex flex-col gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <select name="role" class="w-full rounded-lg border-slate-300 py-1.5 text-xs focus:border-indigo-500 focus:ring-indigo-500">
                                        @foreach (['admin' => 'Admin', 'secretariat' => 'Secrétariat', 'responsable' => 'Responsable', 'user' => 'Membre'] as $roleValue => $roleLabel)
                                            <option value="{{ $roleValue }}" @selected($userItem->role === $roleValue)>{{ $roleLabel }}</option>
                                        @endforeach
                                    </select>
                                    <select name="dept" class="w-full rounded-lg border-slate-300 py-1.5 text-xs focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="">Département inchangé</option>
                                        @foreach ($departments as $department)
                                            <option value="{{ $department->name }}" @selected($userItem->dept === $department->name)>{{ $department->name }}</option>
                                        @endforeach
                                    </select>
                                    <button class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-500">Enregistrer le rôle</button>
                                </form>
                            @else
                                <span class="text-xs text-slate-400">Administrateur principal protégé</span>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-10 text-center text-slate-500">Aucun utilisateur.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $users->links() }}</div>
@endsection
