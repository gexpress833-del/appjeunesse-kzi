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

<form method="POST" action="{{ route('attendances.store', $event) }}" class="mt-6 space-y-4">
    @csrf
    <input type="hidden" name="dept" value="{{ $dept ?? '__none__' }}">

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
                            <select name="statuses[{{ $member->id }}]" class="rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach (['present' => 'Présent', 'late' => 'En retard', 'excused' => 'Excusé', 'absent' => 'Absent'] as $value => $label)
                                    <option value="{{ $value }}" @selected(($attendance?->status ?? 'absent') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td class="px-4 py-3">
                            <input type="text" name="notes[{{ $member->id }}]" value="{{ old('notes.' . $member->id, $attendance?->notes) }}" class="w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500" placeholder="Note optionnelle">
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

    @if ($members->isNotEmpty())
        <div class="flex flex-wrap items-center gap-3">
            <button type="button" id="mark-all-present" class="rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-2.5 text-sm font-bold text-emerald-700 hover:bg-emerald-100">✓ Tout marquer présent</button>
            <button class="rounded-xl bg-indigo-600 px-6 py-3 font-semibold text-white hover:bg-indigo-500">Enregistrer les présences</button>
        </div>
    @endif
</form>

@if ($members->isNotEmpty())
    <script>
        document.getElementById('mark-all-present')?.addEventListener('click', () => {
            document.querySelectorAll('select[name^="statuses["]').forEach((select) => {
                select.value = 'present';
            });
        });
    </script>
@endif
@endsection
