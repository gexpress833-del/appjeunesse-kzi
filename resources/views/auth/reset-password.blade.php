@extends('layouts.guest')

@section('title', 'Nouveau mot de passe')

@section('content')
<div class="mx-auto max-w-md">
    <div class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
        <h1 class="text-2xl font-bold text-slate-900">Nouveau mot de passe</h1>
        <p class="mt-2 text-sm text-slate-500">Choisissez un nouveau mot de passe d’au moins 8 caractères.</p>

        @if ($errors->any())
            <div class="mt-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('password.update') }}" class="mt-6 space-y-4">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div>
                <label for="email" class="block text-sm font-medium text-slate-700">Adresse e-mail</label>
                <input id="email" name="email" type="email" value="{{ old('email', $email) }}" required autocomplete="email" class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <div>
                <label for="password" class="block text-sm font-medium text-slate-700">Nouveau mot de passe</label>
                <input id="password" name="password" type="password" required autocomplete="new-password" class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-slate-700">Confirmer le mot de passe</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <button class="w-full rounded-xl bg-indigo-600 py-3 font-semibold text-white hover:bg-indigo-500">Réinitialiser le mot de passe</button>
        </form>
    </div>
</div>
@endsection
