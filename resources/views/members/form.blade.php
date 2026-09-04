@extends('layouts.app')

@section('title', $member->exists ? 'Modifier '.$member->name : 'Nouveau membre')

@section('content')
<h1 class="text-2xl font-bold text-slate-900">{{ $member->exists ? 'Modifier : '.$member->name : 'Ajouter un membre' }}</h1>

<form method="POST"
      action="{{ $member->exists ? route('members.update', $member) : route('members.store') }}"
      enctype="multipart/form-data"
      class="mt-6 max-w-3xl space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    @csrf
    @if ($member->exists) @method('PUT') @endif

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="block text-sm font-medium text-slate-700">Nom complet *</label>
            <input name="name" value="{{ old('name', $member->name) }}" required
                   class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700">Département</label>
            @if (auth()->user()->isResponsable())
                <input value="{{ old('dept', $member->dept ?? auth()->user()->dept) }}" disabled
                       class="mt-1 w-full rounded-xl border-slate-200 bg-slate-50 text-slate-500">
                <input type="hidden" name="dept" value="{{ auth()->user()->dept }}">
                <p class="mt-1 text-xs text-slate-400">En tant que responsable, vous gérez uniquement votre département.</p>
            @else
                <select name="dept" class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">— Choisir —</option>
                    <option value="__none__" @selected(old('dept', $member->dept) === '__none__' || old('dept', $member->dept) === null)>Fidèle sans département</option>
                    @foreach ($departments as $dept)
                        <option value="{{ $dept->name }}" @selected(old('dept', $member->dept) === $dept->name)>{{ $dept->name }}</option>
                    @endforeach
                </select>
            @endif
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700">Fonction dans le département</label>
            <input name="role" value="{{ old('role', $member->role) }}" placeholder="ex. choriste, caméraman…"
                   class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700">Téléphone</label>
            <input name="phone" value="{{ old('phone', $member->phone) }}"
                   class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700">Email</label>
            <input name="email" type="email" value="{{ old('email', $member->email) }}"
                   class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700">Date de naissance</label>
            <input name="birth_date" type="date" value="{{ old('birth_date', $member->birth_date?->format('Y-m-d')) }}"
                   class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
        </div>
        <div class="sm:col-span-2">
            <label class="block text-sm font-medium text-slate-700">Adresse</label>
            <input name="address" value="{{ old('address', $member->address) }}"
                   class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
        </div>
        <div class="sm:col-span-2">
            <label class="block text-sm font-medium text-slate-700">Photo de profil</label>
            <input type="file" name="profile_photo" accept="image/*"
                   class="mt-1 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-indigo-700 hover:file:bg-indigo-100">
            @if ($member->profile_photo_url)
                <p class="mt-1 text-xs text-slate-400">Une nouvelle photo remplacera l'actuelle.</p>
            @endif
        </div>
        <div class="sm:col-span-2">
            <label class="block text-sm font-medium text-slate-700">Notes</label>
            <textarea name="notes" rows="3"
                      class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">{{ old('notes', $member->notes) }}</textarea>
        </div>
    </div>

    <div class="flex items-center gap-3">
        <button class="rounded-xl bg-indigo-600 px-6 py-3 font-semibold text-white hover:bg-indigo-500">
            {{ $member->exists ? 'Enregistrer' : 'Ajouter au répertoire' }}
        </button>
        <a href="{{ route('members.index') }}" class="text-sm text-slate-500 hover:underline">Annuler</a>
    </div>
</form>

@if ($member->exists && (auth()->user()->isAdmin() || auth()->user()->isSecretariat() || (auth()->user()->isResponsable() && auth()->user()->dept === $member->dept)))
    <form method="POST" action="{{ route('members.destroy', $member) }}" class="mt-4"
          onsubmit="return confirm('Supprimer définitivement {{ $member->name }} du répertoire ?')">
        @csrf @method('DELETE')
        <button class="text-sm font-semibold text-rose-600 hover:underline">🗑 Supprimer ce membre</button>
    </form>
@endif
@endsection
