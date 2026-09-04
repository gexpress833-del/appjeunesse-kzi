@extends('layouts.guest')

@section('title', 'Inscription')

@section('content')
<div class="mx-auto max-w-2xl">
    <div class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
        <div class="mb-6 flex justify-center">
            <img src="{{ asset('logoEglise.jpg') }}" class="h-20 w-20 rounded-2xl object-cover ring-2 ring-indigo-400/60" alt="Logo La Parole Éternelle Kolwezi">
        </div>
        <h1 class="text-2xl font-bold text-slate-900">Créer mon compte</h1>
        <p class="mt-1 text-sm text-slate-500">Votre compte sera <strong>en attente de validation</strong> par l'administrateur avant votre accès à l'espace membre.</p>

        @if ($errors->any())
            <div class="mt-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                <ul class="list-inside list-disc">
                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('register.attempt') }}" class="mt-6 grid gap-4 sm:grid-cols-2">
            @csrf
            <div>
                <label class="block text-sm font-medium text-slate-700">Nom complet *</label>
                <input name="full_name" value="{{ old('full_name') }}" required
                       class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Nom d'utilisateur *</label>
                <input name="username" value="{{ old('username') }}" required
                       class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Email *</label>
                <input name="email" type="email" value="{{ old('email') }}" required
                       class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Téléphone</label>
                <input name="phone" value="{{ old('phone') }}"
                       class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
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
                <input name="birth_date" type="date" value="{{ old('birth_date') }}"
                       class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Mot de passe * <span class="text-slate-400">(8 caractères min.)</span></label>
                  <div class="relative mt-1">
                      <input id="register-password" name="password" type="password" required
                          class="w-full rounded-xl border-slate-300 pr-12 focus:border-indigo-500 focus:ring-indigo-500">
                    <button type="button" data-password-toggle="register-password" class="absolute inset-y-0 right-0 px-3 text-slate-500 hover:text-indigo-600" aria-label="Afficher le mot de passe">👁</button>
                  </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Confirmer le mot de passe *</label>
                  <div class="relative mt-1">
                      <input id="register-password-confirmation" name="password_confirmation" type="password" required
                          class="w-full rounded-xl border-slate-300 pr-12 focus:border-indigo-500 focus:ring-indigo-500">
                    <button type="button" data-password-toggle="register-password-confirmation" class="absolute inset-y-0 right-0 px-3 text-slate-500 hover:text-indigo-600" aria-label="Afficher le mot de passe">👁</button>
                  </div>
            </div>
            <div class="sm:col-span-2">
                <button class="w-full rounded-xl bg-indigo-600 py-3 font-semibold text-white hover:bg-indigo-500">Créer mon compte</button>
            </div>
        </form>

        <p class="mt-6 text-center text-sm text-slate-500">
            Déjà inscrit ?
            <a href="{{ route('login') }}" class="font-semibold text-indigo-600 hover:underline">Connectez-vous</a>
        </p>
    </div>
</div>
@endsection
