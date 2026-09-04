@extends('layouts.app')

@section('title', 'Mon profil')

@section('content')
<h1 class="text-2xl font-bold text-slate-900">Mon profil</h1>

<form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data"
      class="mt-6 max-w-2xl space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    @csrf
    @method('PUT')

    <div class="flex items-center gap-4">
        @if (auth()->user()->profile_photo_url)
            <img src="{{ auth()->user()->profile_photo_url }}" class="h-16 w-16 rounded-full object-cover" alt="">
        @else
            <span class="flex h-16 w-16 items-center justify-center rounded-full bg-indigo-100 text-2xl font-bold text-indigo-700">
                {{ strtoupper(substr(auth()->user()->full_name, 0, 1)) }}
            </span>
        @endif
        <div>
            <label class="block text-sm font-medium text-slate-700">Photo de profil</label>
            <input type="file" name="profile_photo" accept="image/*"
                   class="mt-1 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-indigo-700 hover:file:bg-indigo-100">
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="block text-sm font-medium text-slate-700">Nom complet</label>
            <input name="full_name" value="{{ old('full_name', auth()->user()->full_name) }}" required
                   class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700">Email <span class="text-slate-400">(non modifiable)</span></label>
            <input value="{{ auth()->user()->email }}" disabled
                   class="mt-1 w-full rounded-xl border-slate-200 bg-slate-50 text-slate-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700">Téléphone</label>
            <input name="phone" value="{{ old('phone', auth()->user()->phone) }}"
                   class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700">Département</label>
            <select name="dept" class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">— Aucun —</option>
                @foreach ($departments as $dept)
                    <option value="{{ $dept->name }}" @selected(old('dept', auth()->user()->dept) === $dept->name)>{{ $dept->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700">Date de naissance</label>
            <input name="birth_date" type="date" value="{{ old('birth_date', auth()->user()->birth_date?->format('Y-m-d')) }}"
                   class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700">Adresse</label>
            <input name="address" value="{{ old('address', auth()->user()->address) }}"
                   class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700">Nouveau mot de passe</label>
            <input name="password" type="password" placeholder="Laisser vide pour ne pas changer"
                   class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700">Confirmer le mot de passe</label>
            <input name="password_confirmation" type="password"
                   class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
        </div>
    </div>

    <button class="rounded-xl bg-indigo-600 px-6 py-3 font-semibold text-white hover:bg-indigo-500">Enregistrer</button>
</form>
@endsection
