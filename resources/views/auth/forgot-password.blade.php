@extends('layouts.guest')

@section('title', 'Mot de passe oublié')

@section('content')
<div class="mx-auto max-w-md">
    <div class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
        <div class="mb-6 flex justify-center">
            <img src="{{ asset('logoEglise.jpg') }}" class="h-20 w-20 rounded-2xl object-cover ring-2 ring-indigo-400/60" alt="Logo La Parole Éternelle Kolwezi">
        </div>
        <h1 class="text-2xl font-bold text-slate-900">Mot de passe oublié</h1>
        <p class="mt-2 text-sm text-slate-500">Saisissez votre adresse e-mail. Si elle correspond à un compte, vous recevrez un lien sécurisé.</p>

        @if (session('success'))
            <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="mt-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-4">
            @csrf
            <div>
                <label for="email" class="block text-sm font-medium text-slate-700">Adresse e-mail</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="email" class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <button class="w-full rounded-xl bg-indigo-600 py-3 font-semibold text-white hover:bg-indigo-500">Envoyer le lien</button>
        </form>

        <a href="{{ route('login') }}" class="mt-6 block text-center text-sm font-semibold text-indigo-600 hover:underline">Retour à la connexion</a>
    </div>
</div>
@endsection
