<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Fiche membre - {{ $member->name }}</title>
    <style>
        @page { margin: 28px 32px 30px; }
        body { background: #f4f7fb; color: #172033; font-family: DejaVu Sans, sans-serif; font-size: 10px; margin: 0; }
        .topbar { background: #0b1630; color: #fff; padding: 18px 22px; }
        .brand { color: #69e2f5; font-size: 9px; font-weight: bold; letter-spacing: 2px; text-transform: uppercase; }
        .header { background: #fff; border-bottom: 1px solid #dbe5f0; padding: 20px 22px 18px; }
        h1 { color: #0b1630; font-size: 24px; margin: 0 0 5px; }
        h2 { color: #0b1630; font-size: 14px; margin: 24px 0 10px; }
        .muted { color: #64748b; }
        .identity { background: #fff; border-bottom: 1px solid #dbe5f0; padding: 20px 22px; }
        .identity-photo { background: #dbeafe; border: 4px solid #69e2f5; height: 92px; width: 92px; }
        .identity-photo img { height: 92px; width: 92px; }
        .identity-fallback { color: #1d4ed8; font-size: 38px; font-weight: bold; line-height: 92px; text-align: center; }
        .identity-name { color: #0b1630; font-size: 21px; font-weight: bold; margin: 4px 0 7px; }
        .identity-meta { color: #475569; font-size: 10px; }
        .pill { background: #e0f7fb; color: #08798a; font-size: 9px; font-weight: bold; padding: 5px 9px; }
        .grid { background: #fff; margin-top: 12px; padding: 8px 16px; width: 100%; }
        .grid td { border-bottom: 1px solid #e2e8f0; padding: 10px 6px; vertical-align: top; width: 50%; }
        .label { color: #64748b; display: block; font-size: 8px; font-weight: bold; letter-spacing: .8px; text-transform: uppercase; }
        .value { color: #172033; display: block; font-size: 10px; margin-top: 4px; }
        .summary { background: #0b1630; color: #fff; margin-top: 16px; padding: 15px 18px; }
        .summary strong { color: #69e2f5; font-size: 15px; }
        .summary .muted { color: #cbd5e1; margin-top: 4px; }
        table.history { border-collapse: collapse; width: 100%; }
        .history th { background: #dbeafe; color: #1e3a8a; font-size: 8px; padding: 10px 8px; text-align: left; text-transform: uppercase; }
        .history td { background: #fff; border-bottom: 1px solid #e2e8f0; padding: 9px 8px; }
        .status { color: #08798a; font-weight: bold; }
        .footer { border-top: 1px solid #cbd5e1; color: #64748b; font-size: 8px; margin-top: 26px; padding-top: 9px; }
    </style>
</head>
<body>
    <div class="topbar">
        <span class="brand">Appjeunesse · La Parole Éternelle Kolwezi</span>
    </div>
    <div class="header">
        <h1>Fiche individuelle du membre</h1>
        <div class="muted">Profil officiel · Générée le {{ $generatedAt->translatedFormat('d F Y à H:i') }}</div>
    </div>

    <div class="identity">
        <table width="100%">
            <tr>
                <td width="115" valign="top">
                    <div class="identity-photo">
                        @if ($member->profile_photo_url)
                            <img src="{{ $member->profile_photo_url }}" alt="Photo de {{ $member->name }}">
                        @else
                            <div class="identity-fallback">{{ strtoupper(substr($member->name, 0, 1)) }}</div>
                        @endif
                    </div>
                </td>
                <td valign="middle">
                    <div class="identity-name">{{ $member->name }}</div>
                    <span class="pill">{{ $member->dept ?: 'Membre de la jeunesse' }}</span>
                    <div class="identity-meta">{{ $member->role ?: 'Fidèle' }} · {{ $member->email ?: 'Compte non renseigné' }}</div>
                </td>
            </tr>
        </table>
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
        <strong>{{ $rate }}% de présence</strong><br>
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
