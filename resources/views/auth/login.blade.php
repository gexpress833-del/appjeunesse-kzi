@extends('layouts.guest')

@section('title', 'Connexion')

@section('content')
<div class="mx-auto max-w-md">
    <div class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
        <div class="mb-6 flex justify-center">
            <img src="{{ asset('logoEglise.jpg') }}" class="h-20 w-20 rounded-2xl object-cover ring-2 ring-indigo-400/60" alt="Logo La Parole Éternelle Kolwezi">
        </div>
        <h1 class="text-2xl font-bold text-slate-900">Connexion</h1>
        <p class="mt-1 text-sm text-slate-500">Utilisez votre adresse email ou votre nom d'utilisateur.</p>

        @if (session('success'))
            <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="mt-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('login.attempt') }}" class="mt-6 space-y-4">
            @csrf
            <div>
                <label for="login" class="block text-sm font-medium text-slate-700">Email ou nom d'utilisateur</label>
                <input id="login" name="login" type="text" value="{{ old('login') }}" required autofocus
                       class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <div>
                <label for="password" class="block text-sm font-medium text-slate-700">Mot de passe</label>
                  <div class="relative mt-1">
                      <input id="password" name="password" type="password" required
                          class="w-full rounded-xl border-slate-300 pr-12 focus:border-indigo-500 focus:ring-indigo-500">
                    <button type="button" data-password-toggle="password" class="absolute inset-y-0 right-0 px-3 text-slate-500 hover:text-indigo-600" aria-label="Afficher le mot de passe">👁</button>
                  </div>
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="remember" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                Se souvenir de moi
            </label>
            <button class="w-full rounded-xl bg-indigo-600 py-3 font-semibold text-white hover:bg-indigo-500">Se connecter</button>
        </form>

        <a href="{{ route('password.request') }}" class="mt-4 block text-center text-sm font-semibold text-indigo-600 hover:underline">Mot de passe oublié ?</a>

        <p class="mt-6 text-center text-sm text-slate-500">
            Pas encore de compte ?
            <a href="{{ route('register') }}" class="font-semibold text-indigo-600 hover:underline">Inscrivez-vous</a>
        </p>
    </div>
</div>
@endsection
