@extends('layouts.guest')

@section('title', 'Erreur')

@section('content')
<div class="mx-auto max-w-lg">
    <div class="rounded-3xl border border-slate-200 bg-white p-8 text-center shadow-sm">
        <div class="mb-4 inline-flex h-16 w-16 items-center justify-center rounded-full bg-rose-100 text-3xl text-rose-600">⚠️</div>
        <p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-500">Information</p>
        <h1 class="mt-3 text-3xl font-black text-slate-900">Nous rencontrons une difficulté</h1>
        <p class="mt-3 text-base text-slate-600">{{ $message }}</p>

        <div class="mt-6 rounded-2xl bg-slate-50 p-4 text-left">
            <p class="text-sm font-semibold text-slate-800">Que faire maintenant ?</p>
            <ul class="mt-3 list-disc space-y-2 pl-5 text-sm text-slate-600">
                <li>Rechargez la page et réessayez.</li>
                <li>Retournez à l’accueil pour reprendre votre navigation.</li>
                <li>Si le problème persiste, réessayez plus tard ou contactez le support.</li>
            </ul>
        </div>

        <div class="mt-6 flex flex-wrap justify-center gap-3">
            <a href="{{ route('home') }}" class="rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white hover:bg-indigo-500">Retour à l’accueil</a>
            <a href="{{ route('login') }}" class="rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">Se connecter</a>
        </div>
    </div>
</div>
@endsection
