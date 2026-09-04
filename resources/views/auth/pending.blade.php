@extends('layouts.guest')

@section('title', 'Compte en attente')

@section('content')
<div class="mx-auto max-w-lg">
    <div class="rounded-2xl border border-amber-200 bg-white p-8 text-center shadow-sm">
        <p class="text-5xl">⏳</p>
        <h1 class="mt-4 text-2xl font-bold text-slate-900">
            {{ auth()->user()->status === 'pending' ? 'Compte en attente de validation' : 'Compte désactivé' }}
        </h1>
        <p class="mt-2 text-slate-600">
            @if (auth()->user()->status === 'pending')
                Bonjour <strong>{{ auth()->user()->full_name }}</strong>, votre compte a bien été créé mais il doit être
                <strong>validé par l'administrateur</strong> avant que vous puissiez accéder à l'espace membre.
                Nous vous invitons à revenir plus tard.
            @else
                Votre compte est actuellement <strong>désactivé</strong>. Contactez l'administrateur de la jeunesse
                pour rétablir votre accès.
            @endif
        </p>
        <form method="POST" action="{{ route('logout') }}" class="mt-6">
            @csrf
            <button class="rounded-xl bg-slate-900 px-6 py-3 font-semibold text-white hover:bg-slate-700">Se déconnecter</button>
        </form>
    </div>
</div>
@endsection
