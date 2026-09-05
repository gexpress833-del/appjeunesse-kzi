<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapport de présence</title>
    <style>
        @page { margin: 28px 34px 32px; }
        body { background: #f8fafc; color: #172033; font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        .page { background: #ffffff; padding: 24px 26px 20px; }
        .header { border-bottom: 1px solid #dbe5e7; padding-bottom: 16px; }
        .header-table { width: 100%; }
        .logo-cell { padding-right: 12px; vertical-align: middle; width: 58px; }
        .logo { border: 2px solid #ccfbf1; height: 48px; width: 48px; }
        .brand-cell { vertical-align: middle; }
        .brand { color: #0f766e; font-size: 21px; font-weight: bold; letter-spacing: .4px; }
        .brand-subtitle { color: #64748b; font-size: 9px; margin-top: 4px; }
        .document-tag { background: #ecfdf5; color: #047857; font-size: 8px; font-weight: bold; padding: 7px 10px; text-align: center; }
        .eyebrow { color: #0f766e; font-size: 8px; font-weight: bold; letter-spacing: 1.2px; text-transform: uppercase; }
        .title { color: #0f172a; font-size: 21px; font-weight: bold; margin: 22px 0 6px; }
        .meta { color: #64748b; font-size: 9px; }
        .filters { background: #f8fafc; border-left: 3px solid #14b8a6; color: #475569; font-size: 9px; margin-top: 12px; padding: 8px 10px; }
        .summary { border-collapse: separate; border-spacing: 8px 0; margin: 22px -8px 0; width: calc(100% + 16px); }
        .summary td { background: #f0fdfa; border: 1px solid #ccfbf1; padding: 11px 12px; width: 25%; }
        .summary strong { color: #0f766e; font-size: 17px; }
        .summary-label { color: #334155; font-size: 9px; font-weight: bold; }
        .summary-note { color: #64748b; font-size: 8px; }
        table.data { border-collapse: collapse; margin-top: 22px; width: 100%; }
        table.data th { background: #115e59; color: #fff; font-size: 8px; font-weight: bold; padding: 9px 7px; text-align: left; text-transform: uppercase; }
        table.data td { border-bottom: 1px solid #e2e8f0; color: #334155; padding: 9px 7px; }
        table.data tr:nth-child(even) td { background: #f8fafc; }
        .status { font-weight: bold; }
        .present { color: #047857; }
        .late { color: #b45309; }
        .excused { color: #0369a1; }
        .absent { color: #be123c; }
        .footer { border-top: 1px solid #dbe5e7; color: #94a3b8; font-size: 8px; margin-top: 24px; padding-top: 9px; }
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

    <table class="data">
        <thead>
            <tr><th>Événement</th><th>Date</th><th>Département</th><th>Membre</th><th>Statut</th><th>Notes</th></tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row->event->name }}</td>
                    <td>{{ $row->event->date->translatedFormat('d/m/Y H\hi') }}</td>
                    <td>{{ $row->member->dept ?: '—' }}</td>
                    <td>{{ $row->member->name }}</td>
                    <td class="status {{ $row->status }}">{{ match($row->status) { 'present' => 'Présent', 'late' => 'En retard', 'excused' => 'Excusé', default => 'Absent' } }}</td>
                    <td>{{ $row->notes ?: '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" style="text-align:center; padding: 24px;">Aucune donnée correspondante.</td></tr>
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