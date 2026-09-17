@extends('layouts.app')

@section('title', 'Feuille de présence')

@section('content')
<div class="flex flex-wrap items-center justify-between gap-3">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Présences — {{ $event->name }}</h1>
        <p class="mt-1 text-sm text-slate-500">Groupe : <span class="font-semibold text-slate-700">{{ $dept ?? 'Fidèles sans département' }}</span> · {{ $event->date->translatedFormat('d/m/Y · H\hi') }}</p>
    </div>
    <a href="{{ route('attendances.pick') }}" class="text-sm font-semibold text-indigo-600 hover:underline">← Retour</a>
</div>

<div class="mt-4 flex flex-wrap gap-2 text-xs font-bold">
    <span class="rounded-full bg-emerald-100 px-3 py-1 text-emerald-700">● Présent</span>
    <span class="rounded-full bg-amber-100 px-3 py-1 text-amber-700">● En retard</span>
    <span class="rounded-full bg-sky-100 px-3 py-1 text-sky-700">● Excusé</span>
    <span class="rounded-full bg-rose-100 px-3 py-1 text-rose-700">● Absent</span>
</div>

@if ($departmentSelectionRequired)
    <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div>
            <h2 class="text-lg font-bold text-slate-900">Choisir le département à suivre</h2>
            <p class="mt-1 text-sm text-slate-500">Sélectionnez un département de la liste de roulement pour afficher et consulter ses présences.</p>
        </div>

        <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($departments as $department)
                <a href="{{ route('attendances.sheet', ['event' => $event, 'dept' => $department->name]) }}" class="rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-3 font-semibold text-indigo-700 transition hover:border-indigo-400 hover:bg-indigo-100">
                    {{ $department->name }}
                </a>
            @endforeach
            <a href="{{ route('attendances.sheet', ['event' => $event, 'dept' => '__none__']) }}" class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 font-semibold text-slate-700 transition hover:border-slate-400 hover:bg-slate-100">
                Fidèles sans département
            </a>
        </div>
    </div>
@else
    @php($readOnly = auth()->user()->isAdmin() || auth()->user()->isSecretariat())
    @php($statusLabels = ['present' => 'Présent', 'late' => 'En retard', 'excused' => 'Excusé', 'absent' => 'Absent'])
    @php($availableStatuses = array_intersect_key($statusLabels, array_flip(\App\Models\AppSetting::current()->attendance_statuses ?? array_keys($statusLabels))))

    @if ($readOnly)
        <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
            Vue en lecture seule : les présences sont saisies par les responsables de département. L’administration et le secrétariat peuvent uniquement consulter les relevés.
        </div>
    @endif

    @if (! $readOnly)
        <form method="POST" action="{{ route('attendances.store', $event) }}" class="mt-6 space-y-4">
            @csrf
            <input type="hidden" name="dept" value="{{ $dept ?? '__none__' }}">
    @endif

        <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Photo</th>
                        <th class="px-4 py-3">Membre</th>
                        <th class="px-4 py-3">Statut</th>
                        <th class="px-4 py-3">Notes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($members as $member)
                        @php($attendance = $existing[$member->id] ?? null)
                        <tr>
                            <td class="px-4 py-3">
                                @if ($member->profile_photo_url)
                                    <img src="{{ $member->profile_photo_url }}" alt="Photo de {{ $member->name }}" class="h-11 w-11 rounded-full object-cover ring-2 ring-indigo-100">
                                @else
                                    <span class="flex h-11 w-11 items-center justify-center rounded-full bg-indigo-100 text-sm font-bold text-indigo-700">{{ strtoupper(substr($member->name, 0, 1)) }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 font-medium text-slate-900">{{ $member->name }}</td>
                            <td class="px-4 py-3">
                                @if ($readOnly)
                                    <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ ($attendance?->status ?? 'absent') === 'present' ? 'bg-emerald-100 text-emerald-700' : (($attendance?->status ?? 'absent') === 'late' ? 'bg-amber-100 text-amber-700' : (($attendance?->status ?? 'absent') === 'excused' ? 'bg-sky-100 text-sky-700' : 'bg-rose-100 text-rose-700')) }}">
                                        {{ $statusLabels[$attendance?->status ?? 'absent'] ?? 'Absent' }}
                                    </span>
                                @else
                                    <select name="statuses[{{ $member->id }}]" class="rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
                                        @foreach ($availableStatuses as $value => $label)
                                            <option value="{{ $value }}" @selected(($attendance?->status ?? 'absent') === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if ($readOnly)
                                    <span class="text-slate-500">{{ $attendance?->notes ?? '—' }}</span>
                                @else
                                    <input type="text" name="notes[{{ $member->id }}]" value="{{ old('notes.' . $member->id, $attendance?->notes) }}" class="w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500" placeholder="Note optionnelle">
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-10 text-center text-slate-500">Aucun membre dans ce département.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if (! $readOnly && $members->isNotEmpty())
            <div class="flex flex-wrap items-center gap-3">
                <button type="button" id="mark-all-present" class="rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-2.5 text-sm font-bold text-emerald-700 hover:bg-emerald-100">✓ Tout marquer présent</button>
                <button class="rounded-xl bg-indigo-600 px-6 py-3 font-semibold text-white hover:bg-indigo-500">Enregistrer les présences</button>
            </div>
        @endif

    @if (! $readOnly)
        </form>
    @endif

    @if (! $readOnly && $members->isNotEmpty())
        <script>
            document.getElementById('mark-all-present')?.addEventListener('click', () => {
                document.querySelectorAll('select[name^="statuses["]').forEach((select) => {
                    select.value = 'present';
                });
            });
        </script>
    @endif
@endif
@endsection
