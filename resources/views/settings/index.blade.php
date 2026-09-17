@extends('layouts.app')

@section('title', 'Paramètres')

@section('content')
<div class="mb-8 flex flex-wrap items-end justify-between gap-4">
    <div>
        <p class="text-xs font-bold uppercase tracking-[0.2em] text-cyan-300">Administration</p>
        <h1 class="mt-2 text-3xl font-bold text-white">Paramètres de l'application</h1>
        <p class="mt-2 max-w-2xl text-sm text-slate-400">Centralisez l'identité de l'église, les règles de présence, les notifications et la communication.</p>
    </div>
    <span class="rounded-full border border-cyan-400/20 bg-cyan-400/10 px-3 py-1.5 text-xs font-semibold text-cyan-100">{{ auth()->user()->isAdmin() ? 'Accès administrateur' : 'Accès secrétariat' }}</span>
</div>

<form method="POST" action="{{ route('settings.update') }}" class="space-y-6">
    @csrf
    @method('PUT')

    <section class="rounded-2xl border border-white/10 bg-white/[0.04] p-5 shadow-xl shadow-slate-950/20 sm:p-6">
        <div class="mb-5">
            <h2 class="text-lg font-bold text-white">Général</h2>
            <p class="mt-1 text-sm text-slate-400">Informations utilisées dans l'interface et les rapports officiels.</p>
        </div>
        <div class="grid gap-4 md:grid-cols-2">
            <label class="block">
                <span class="text-sm font-semibold text-slate-200">Nom de l'église</span>
                <input name="church_name" value="{{ old('church_name', $settings->church_name) }}" @disabled(! auth()->user()->isAdmin()) class="mt-2 w-full rounded-xl border-white/10 bg-slate-900/80 text-white disabled:cursor-not-allowed disabled:opacity-60">
            </label>
            <label class="block">
                <span class="text-sm font-semibold text-slate-200">Nom de l'application</span>
                <input name="application_name" value="{{ old('application_name', $settings->application_name) }}" @disabled(! auth()->user()->isAdmin()) class="mt-2 w-full rounded-xl border-white/10 bg-slate-900/80 text-white disabled:cursor-not-allowed disabled:opacity-60">
            </label>
            <label class="block">
                <span class="text-sm font-semibold text-slate-200">Fuseau horaire</span>
                <select name="timezone" @disabled(! auth()->user()->isAdmin()) class="mt-2 w-full rounded-xl border-white/10 bg-slate-900/80 text-white disabled:cursor-not-allowed disabled:opacity-60">
                    @foreach (['Africa/Lubumbashi', 'Africa/Kinshasa', 'UTC', 'Europe/Paris'] as $timezone)
                        <option value="{{ $timezone }}" @selected(old('timezone', $settings->timezone) === $timezone)>{{ $timezone }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block">
                <span class="text-sm font-semibold text-slate-200">URL du logo</span>
                <input type="url" name="logo_url" value="{{ old('logo_url', $settings->logo_url) }}" placeholder="https://..." class="mt-2 w-full rounded-xl border-white/10 bg-slate-900/80 text-white">
            </label>
            <label class="block">
                <span class="text-sm font-semibold text-slate-200">Téléphone</span>
                <input name="phone" value="{{ old('phone', $settings->phone) }}" class="mt-2 w-full rounded-xl border-white/10 bg-slate-900/80 text-white">
            </label>
            <label class="block">
                <span class="text-sm font-semibold text-slate-200">Email</span>
                <input type="email" name="email" value="{{ old('email', $settings->email) }}" class="mt-2 w-full rounded-xl border-white/10 bg-slate-900/80 text-white">
            </label>
            <label class="block md:col-span-2">
                <span class="text-sm font-semibold text-slate-200">Adresse</span>
                <input name="address" value="{{ old('address', $settings->address) }}" class="mt-2 w-full rounded-xl border-white/10 bg-slate-900/80 text-white">
            </label>
        </div>
    </section>

    <section class="rounded-2xl border border-white/10 bg-white/[0.04] p-5 shadow-xl shadow-slate-950/20 sm:p-6">
        <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-lg font-bold text-white">Présences</h2>
                <p class="mt-1 text-sm text-slate-400">Les responsables saisissent les présences. L'administration et le secrétariat restent en lecture seule.</p>
            </div>
            <span class="rounded-full bg-emerald-400/10 px-3 py-1 text-xs font-semibold text-emerald-200">Règle active</span>
        </div>
        <div class="grid gap-4 md:grid-cols-2">
            <fieldset>
                <legend class="text-sm font-semibold text-slate-200">Statuts disponibles</legend>
                <div class="mt-3 grid gap-2 sm:grid-cols-2">
                    @foreach (['present' => 'Présent', 'absent' => 'Absent', 'late' => 'En retard', 'excused' => 'Excusé'] as $value => $label)
                        <label class="flex items-center gap-2 rounded-xl border border-white/10 bg-slate-900/50 px-3 py-2 text-sm text-slate-300">
                            <input type="checkbox" name="attendance_statuses[]" value="{{ $value }}" @checked(in_array($value, old('attendance_statuses', $settings->attendance_statuses ?? []), true)) class="rounded border-white/20 bg-slate-900 text-cyan-400">
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
            </fieldset>
            <label class="block">
                <span class="text-sm font-semibold text-slate-200">Durée de modification (heures)</span>
                <input type="number" min="0" max="720" name="attendance_editable_hours" value="{{ old('attendance_editable_hours', $settings->attendance_editable_hours) }}" class="mt-2 w-full rounded-xl border-white/10 bg-slate-900/80 text-white">
                <span class="mt-2 block text-xs text-slate-500">0 signifie sans limite automatique.</span>
            </label>
        </div>
    </section>

    <section class="rounded-2xl border border-white/10 bg-white/[0.04] p-5 shadow-xl shadow-slate-950/20 sm:p-6">
        <div class="mb-5">
            <h2 class="text-lg font-bold text-white">Notifications</h2>
            <p class="mt-1 text-sm text-slate-400">Choisissez les événements qui doivent produire une notification interne.</p>
        </div>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ([
                'new_registration' => 'Nouvelle inscription',
                'event_created' => 'Nouvel événement',
                'attendance_recorded' => 'Présence enregistrée',
                'social_visit' => 'Visite sociale',
                'video_comment' => 'Commentaire vidéo',
            ] as $key => $label)
                <label class="flex items-center gap-3 rounded-xl border border-white/10 bg-slate-900/50 px-3 py-3 text-sm text-slate-300">
                    <input type="checkbox" name="{{ $key }}" value="1" @checked(old($key, data_get($settings->notification_settings, $key, false))) class="rounded border-white/20 bg-slate-900 text-cyan-400">
                    {{ $label }}
                </label>
            @endforeach
        </div>
    </section>

    <section class="rounded-2xl border border-white/10 bg-white/[0.04] p-5 shadow-xl shadow-slate-950/20 sm:p-6">
        <div class="mb-5">
            <h2 class="text-lg font-bold text-white">Communication</h2>
            <p class="mt-1 text-sm text-slate-400">Contrôlez les éléments visibles sur la vitrine publique.</p>
        </div>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                'carousel_enabled' => 'Carrousel d’accueil',
                'events_enabled' => 'Événements à venir',
                'video_comments_enabled' => 'Commentaires vidéo',
                'video_likes_enabled' => 'Likes vidéo',
            ] as $key => $label)
                <label class="flex items-center gap-3 rounded-xl border border-white/10 bg-slate-900/50 px-3 py-3 text-sm text-slate-300">
                    <input type="checkbox" name="{{ $key }}" value="1" @checked(old($key, data_get($settings->communication_settings, $key, false))) class="rounded border-white/20 bg-slate-900 text-cyan-400">
                    {{ $label }}
                </label>
            @endforeach
        </div>
    </section>

    @if (auth()->user()->isAdmin())
        <section class="rounded-2xl border border-amber-400/20 bg-amber-400/[0.06] p-5 shadow-xl shadow-slate-950/20 sm:p-6">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-lg font-bold text-white">Maintenance temporaire</h2>
                    <p class="mt-1 text-sm text-slate-400">Bloquer temporairement l'accès aux comptes actifs pendant une mise à jour majeure.</p>
                </div>
                <label class="flex items-center gap-3 text-sm font-semibold text-amber-100">
                    <input type="checkbox" name="maintenance_mode" value="1" @checked(old('maintenance_mode', $settings->maintenance_mode)) class="rounded border-amber-300/30 bg-slate-900 text-amber-400">
                    Activée
                </label>
            </div>
        </section>
    @endif

    <div class="flex justify-end">
        <button class="rounded-xl bg-cyan-500 px-6 py-3 font-bold text-slate-950 shadow-lg shadow-cyan-500/20 hover:bg-cyan-400">Enregistrer les paramètres</button>
    </div>
</form>

<section class="mt-6 rounded-2xl border border-white/10 bg-white/[0.04] p-5 shadow-xl shadow-slate-950/20 sm:p-6">
    <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="text-lg font-bold text-white">Départements</h2>
            <p class="mt-1 text-sm text-slate-400">Les renommages sont répercutés sur les membres, comptes et événements liés.</p>
        </div>
        <form method="POST" action="{{ route('settings.departments.store') }}" class="flex w-full gap-2 sm:w-auto">
            @csrf
            <input name="name" required maxlength="100" placeholder="Nouveau département" class="min-w-0 flex-1 rounded-xl border-white/10 bg-slate-900/80 text-white sm:w-56">
            <button class="rounded-xl bg-emerald-500 px-4 py-2 font-bold text-slate-950 hover:bg-emerald-400">Ajouter</button>
        </form>
    </div>
    <div class="space-y-3">
        @forelse ($departments as $department)
            <div class="flex flex-wrap items-center gap-3 rounded-xl border border-white/10 bg-slate-900/40 p-3">
                <form method="POST" action="{{ route('settings.departments.update', $department) }}" class="flex min-w-0 flex-1 gap-2">
                    @csrf
                    @method('PUT')
                    <input name="name" value="{{ $department->name }}" required maxlength="100" class="min-w-0 flex-1 rounded-lg border-white/10 bg-slate-950/70 text-white">
                    <button class="rounded-lg border border-cyan-400/20 px-3 py-2 text-xs font-semibold text-cyan-200 hover:bg-cyan-400/10">Renommer</button>
                </form>
                <span class="text-xs text-slate-500">{{ $department->members_count }} membre(s)</span>
                <form method="POST" action="{{ route('settings.departments.destroy', $department) }}" onsubmit="return confirm('Supprimer ce département ?')">
                    @csrf
                    @method('DELETE')
                    <button class="rounded-lg border border-rose-400/20 px-3 py-2 text-xs font-semibold text-rose-200 hover:bg-rose-400/10">Supprimer</button>
                </form>
            </div>
        @empty
            <p class="rounded-xl border border-dashed border-white/10 px-4 py-6 text-center text-sm text-slate-500">Aucun département enregistré.</p>
        @endforelse
    </div>
</section>

<section class="mt-6 grid gap-4 md:grid-cols-2">
    <a href="{{ route('users.create') }}" class="rounded-2xl border border-white/10 bg-white/[0.04] p-5 transition hover:bg-white/[0.07]">
        <h2 class="font-bold text-white">Créer un compte</h2>
        <p class="mt-1 text-sm text-slate-400">Créer un compte en attente de validation.</p>
    </a>
    @if (auth()->user()->isAdmin())
        <a href="{{ route('users.index') }}" class="rounded-2xl border border-white/10 bg-white/[0.04] p-5 transition hover:bg-white/[0.07]">
            <h2 class="font-bold text-white">Gérer les comptes</h2>
            <p class="mt-1 text-sm text-slate-400">Valider les inscriptions, attribuer les rôles et gérer les statuts.</p>
        </a>
    @endif
</section>
@endsection
