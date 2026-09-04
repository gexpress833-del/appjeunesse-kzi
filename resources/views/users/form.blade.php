@extends('layouts.app')

@section('title', 'Créer un compte')

@section('content')
<h1 class="text-2xl font-bold text-slate-900">Créer un compte</h1>

<form method="POST" action="{{ route('users.store') }}" class="mt-6 max-w-2xl space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    @csrf

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="block text-sm font-medium text-slate-700">Nom complet *</label>
            <input name="full_name" value="{{ old('full_name') }}" required class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700">Nom d’utilisateur *</label>
            <input name="username" value="{{ old('username') }}" required class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700">Email *</label>
            <input name="email" type="email" value="{{ old('email') }}" required class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700">Téléphone</label>
            <input name="phone" value="{{ old('phone') }}" class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700">Rôle *</label>
            <select name="role" class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
                @foreach (['user' => 'Membre', 'responsable' => 'Responsable', 'secretariat' => 'Secrétariat'] as $value => $label)
                    @if (auth()->user()->isAdmin() || $value !== 'secretariat')
                        <option value="{{ $value }}" @selected(old('role') === $value)>{{ $label }}</option>
                    @endif
                @endforeach
                @if (auth()->user()->isAdmin())
                    <option value="admin" @selected(old('role') === 'admin')>Admin</option>
                @endif
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700">Département</label>
            <select name="dept" class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">— Aucun —</option>
                @foreach ($departments as $dept)
                    <option value="{{ $dept->name }}" @selected(old('dept') === $dept->name)>{{ $dept->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700">Date de naissance</label>
            <input name="birth_date" type="date" value="{{ old('birth_date') }}" class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700">Mot de passe *</label>
            <input name="password" type="password" required class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700">Adresse</label>
        <input name="address" value="{{ old('address') }}" class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
    </div>

    <button class="rounded-xl bg-indigo-600 px-6 py-3 font-semibold text-white hover:bg-indigo-500">Créer le compte</button>
</form>
@endsection
