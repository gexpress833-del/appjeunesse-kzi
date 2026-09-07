<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapport de présence</title>
    <style>
        @page { margin: 24px 30px 30px; }
        body { background: #eef4f7; color: #172033; font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        .page { background: #ffffff; border: 1px solid #d8e5ea; padding: 0 25px 20px; }
        .header { background: #071a2b; border-bottom: 4px solid #2dd4bf; margin: 0 -25px; padding: 18px 25px; }
        .header-table { width: 100%; }
        .logo-cell { padding-right: 12px; vertical-align: middle; width: 58px; }
        .logo { border: 2px solid #5eead4; height: 48px; width: 48px; }
        .brand-cell { vertical-align: middle; }
        .brand { color: #f8fafc; font-size: 21px; font-weight: bold; letter-spacing: .4px; }
        .brand-subtitle { color: #a5f3fc; font-size: 9px; margin-top: 4px; }
        .document-tag { border: 1px solid #2dd4bf; color: #99f6e4; font-size: 8px; font-weight: bold; padding: 7px 10px; text-align: center; }
        .eyebrow { color: #0891b2; font-size: 8px; font-weight: bold; letter-spacing: 1.2px; margin-top: 22px; text-transform: uppercase; }
        .title { color: #082f49; font-size: 22px; font-weight: bold; margin: 5px 0 6px; }
        .meta { color: #64748b; font-size: 9px; }
        .filters { background: #f0f9ff; border-left: 4px solid #06b6d4; color: #334155; font-size: 9px; margin-top: 12px; padding: 9px 11px; }
        .summary { border-collapse: separate; border-spacing: 8px 0; margin: 22px -8px 0; width: calc(100% + 16px); }
        .summary td { background: #f0fdfa; border: 1px solid #99f6e4; padding: 11px 12px; width: 25%; }
        .summary td:nth-child(2) { background: #eff6ff; border-color: #bfdbfe; }
        .summary td:nth-child(3) { background: #fff7ed; border-color: #fed7aa; }
        .summary td:nth-child(4) { background: #fdf2f8; border-color: #fbcfe8; }
        .summary strong { color: #0f766e; font-size: 17px; }
        .summary td:nth-child(2) strong { color: #2563eb; }
        .summary td:nth-child(3) strong { color: #c2410c; }
        .summary td:nth-child(4) strong { color: #be185d; }
        .summary-label { color: #1e293b; font-size: 9px; font-weight: bold; }
        .summary-note { color: #64748b; font-size: 8px; }
        .legend { border-collapse: separate; border-spacing: 6px 0; margin: 18px -6px 0; width: calc(100% + 12px); }
        .legend td { border: 1px solid #dbe7ec; color: #334155; font-size: 8px; font-weight: bold; padding: 7px 8px; }
        .legend-mark { display: inline-block; height: 8px; margin-right: 4px; width: 8px; }
        .legend-present { background: #10b981; }
        .legend-late { background: #f59e0b; }
        .legend-excused { background: #0ea5e9; }
        .legend-absent { background: #ef4444; }
        .table-caption { color: #0f766e; font-size: 9px; font-weight: bold; letter-spacing: .8px; margin-top: 24px; text-transform: uppercase; }
        table.data { border-collapse: collapse; margin-top: 8px; width: 100%; }
        table.data th { background: #0b334d; color: #cffafe; font-size: 8px; font-weight: bold; padding: 9px 7px; text-align: left; text-transform: uppercase; }
        table.data td { border-bottom: 1px solid #dbe7ec; color: #334155; padding: 8px 7px; }
        table.data tr:nth-child(even) td { background: #f5fafd; }
        .member-photo { border: 2px solid #99f6e4; height: 30px; width: 30px; }
        .member-avatar { background: #dbeafe; color: #1d4ed8; font-size: 11px; font-weight: bold; height: 30px; line-height: 30px; text-align: center; width: 30px; }
        .status { border-radius: 10px; font-size: 8px; font-weight: bold; padding: 4px 6px; }
        .present { color: #047857; }
        .late { color: #b45309; }
        .excused { color: #0369a1; }
        .absent { color: #be123c; }
        .status.present { background: #d1fae5; }
        .status.late { background: #fef3c7; }
        .status.excused { background: #e0f2fe; }
        .status.absent { background: #ffe4e6; }
        .footer { border-top: 1px solid #cbdde3; color: #64748b; font-size: 8px; margin-top: 24px; padding-top: 9px; }
        .footer-table { width: 100%; }
        .footer-right { color: #0f766e; text-align: right; }
    </style>
</head>
<body>
<div class="page">
    <div class="header">
        <table class="header-table">
            <tr>
                <td class="logo-cell">
                    <img class="logo" src="{{ public_path('logoEglise.jpg') }}" alt="Logo La Parole Éternelle">
                </td>
                <td class="brand-cell">
                    <div class="brand">appjeunesse-kzi</div>
                    <div class="brand-subtitle">La Parole Éternelle — Kolwezi</div>
                </td>
                <td class="document-tag">DOCUMENT OFFICIEL<br>PRÉSENCES</td>
            </tr>
        </table>
    </div>

    <div class="eyebrow">Suivi de participation</div>
    <div class="title">Rapport de présence</div>
    <div class="meta">Généré le {{ $generatedAt->translatedFormat('d F Y à H\hi') }}</div>

    @if (collect($filters)->filter()->isNotEmpty())
        <div class="filters"><strong>Filtres appliqués :</strong>
            {{ $filters['event_id'] ?? '' }} {{ $filters['dept'] ?? '' }} {{ $filters['status'] ?? '' }}
            {{ ! empty($filters['from']) ? 'du '.$filters['from'] : '' }} {{ ! empty($filters['to']) ? 'au '.$filters['to'] : '' }}
        </div>
    @endif

    @if ($summary->isNotEmpty())
        <table class="summary">
            <tr>
                @foreach ($summary->take(4) as $item)
                    <td><div class="summary-label">{{ $item->dept ?: 'Sans département' }}</div><strong>{{ $item->rate }}%</strong><br><span class="summary-note">{{ $item->present + $item->late }}/{{ $item->total }} présents ou retards</span></td>
                @endforeach
            </tr>
        </table>
    @endif

    <table class="legend">
        <tr>
            <td><span class="legend-mark legend-present"></span>PRÉSENT · Participation confirmée</td>
            <td><span class="legend-mark legend-late"></span>EN RETARD · Arrivée tardive</td>
            <td><span class="legend-mark legend-excused"></span>EXCUSÉ · Absence justifiée</td>
            <td><span class="legend-mark legend-absent"></span>ABSENT · Non présent</td>
        </tr>
    </table>

    <div class="table-caption">Détail des participations</div>
    <table class="data">
        <thead>
            <tr><th>Événement</th><th>Date</th><th>Département</th><th>Photo</th><th>Membre</th><th>Statut</th><th>Notes</th></tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row->event->name }}</td>
                    <td>{{ $row->event->date->translatedFormat('d/m/Y H\hi') }}</td>
                    <td>{{ $row->member->dept ?: '—' }}</td>
                    <td>
                        @if ($row->member->profile_photo_url)
                            <img class="member-photo" src="{{ $row->member->profile_photo_url }}" alt="Photo de {{ $row->member->name }}">
                        @else
                            <div class="member-avatar">{{ strtoupper(substr($row->member->name, 0, 1)) }}</div>
                        @endif
                    </td>
                    <td>{{ $row->member->name }}</td>
                    <td><span class="status {{ $row->status }}">{{ match($row->status) { 'present' => 'PRÉSENT', 'late' => 'EN RETARD', 'excused' => 'EXCUSÉ', default => 'ABSENT' } }}</span></td>
                    <td>{{ $row->notes ?: '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="7" style="text-align:center; padding: 24px;">Aucune donnée correspondante.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <table class="footer-table">
            <tr>
                <td>Document officiel de suivi des présences</td>
                <td class="footer-right">appjeunesse-kzi · Kolwezi</td>
            </tr>
        </table>
    </div>
</div>
</body>
</html>