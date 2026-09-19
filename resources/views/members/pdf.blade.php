<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Fiche membre - {{ $member->name }}</title>
    <style>
        body { color: #172033; font-family: DejaVu Sans, sans-serif; font-size: 11px; margin: 32px; }
        .header { border-bottom: 3px solid #4338ca; padding-bottom: 16px; }
        h1 { color: #1e1b4b; font-size: 24px; margin: 0 0 6px; }
        h2 { color: #1e1b4b; font-size: 15px; margin: 26px 0 10px; }
        .muted { color: #64748b; }
        .grid { margin-top: 20px; width: 100%; }
        .grid td { border-bottom: 1px solid #e2e8f0; padding: 9px 6px; vertical-align: top; width: 50%; }
        .label { color: #64748b; display: block; font-size: 9px; font-weight: bold; text-transform: uppercase; }
        .value { display: block; margin-top: 3px; }
        .summary { background: #eef2ff; border-radius: 8px; margin-top: 18px; padding: 14px; }
        table.history { border-collapse: collapse; width: 100%; }
        .history th { background: #e0e7ff; color: #312e81; font-size: 9px; padding: 9px 7px; text-align: left; text-transform: uppercase; }
        .history td { border-bottom: 1px solid #e2e8f0; padding: 8px 7px; }
        .footer { border-top: 1px solid #cbd5e1; color: #64748b; font-size: 9px; margin-top: 28px; padding-top: 8px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Fiche individuelle du membre</h1>
        <div class="muted">Annuaire de la jeunesse · Générée le {{ $generatedAt->translatedFormat('d F Y à H:i') }}</div>
    </div>

    <table class="grid">
        <tr>
            <td><span class="label">Nom complet</span><span class="value">{{ $member->name }}</span></td>
            <td><span class="label">Sexe</span><span class="value">{{ $member->sex === 'female' ? 'Femme' : ($member->sex === 'male' ? 'Homme' : 'Non renseigné') }}</span></td>
        </tr>
        <tr>
            <td><span class="label">Département</span><span class="value">{{ $member->dept ?: 'Sans département' }}</span></td>
            <td><span class="label">Fonction</span><span class="value">{{ $member->role ?: 'Fidèle' }}</span></td>
        </tr>
        <tr>
            <td><span class="label">Téléphone</span><span class="value">{{ $member->phone ?: 'Non renseigné' }}</span></td>
            <td><span class="label">Email</span><span class="value">{{ $member->email ?: 'Non renseigné' }}</span></td>
        </tr>
        <tr>
            <td><span class="label">Date de naissance</span><span class="value">{{ $member->birth_date?->translatedFormat('d F Y') ?: 'Non renseignée' }}</span></td>
            <td><span class="label">Adresse</span><span class="value">{{ $member->address ?: 'Non renseignée' }}</span></td>
        </tr>
    </table>

    <div class="summary">
        <strong>Taux de présence : {{ $rate }}%</strong><br>
        <span class="muted">{{ $total }} relevé(s) de présence enregistré(s)</span>
    </div>

    @if ($member->notes)
        <h2>Notes</h2>
        <div>{{ $member->notes }}</div>
    @endif

    <h2>Historique de présence</h2>
    @if ($attendances->isNotEmpty())
        <table class="history">
            <thead>
                <tr><th>Événement</th><th>Date</th><th>Statut</th></tr>
            </thead>
            <tbody>
                @foreach ($attendances as $attendance)
                    <tr>
                        <td>{{ $attendance->event->name }}</td>
                        <td>{{ $attendance->event->date->translatedFormat('d/m/Y') }}</td>
                        <td>{{ match ($attendance->status) { 'present' => 'Présent', 'late' => 'En retard', 'excused' => 'Excusé', default => 'Absent' } }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="muted">Aucun relevé de présence enregistré.</div>
    @endif

    <div class="footer">Document réservé à l’administration de l’église.</div>
</body>
</html>
