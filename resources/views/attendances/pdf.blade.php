<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapport de présence</title>
    <style>
        @page { margin: 28px 32px; }
        body { color: #172033; font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        .header { border-bottom: 3px solid #0f766e; padding-bottom: 14px; }
        .brand { color: #0f766e; font-size: 20px; font-weight: bold; }
        .muted { color: #64748b; }
        .title { color: #0f172a; font-size: 18px; font-weight: bold; margin: 18px 0 4px; }
        .meta { color: #475569; font-size: 9px; }
        .summary { margin-top: 18px; width: 100%; }
        .summary td { background: #f0fdfa; border: 1px solid #ccfbf1; padding: 9px; width: 25%; }
        .summary strong { color: #0f766e; font-size: 15px; }
        .summary-label { color: #475569; font-size: 9px; }
        table.data { border-collapse: collapse; margin-top: 20px; width: 100%; }
        table.data th { background: #0f766e; color: #fff; font-size: 9px; padding: 8px 6px; text-align: left; }
        table.data td { border-bottom: 1px solid #e2e8f0; padding: 7px 6px; }
        table.data tr:nth-child(even) td { background: #f8fafc; }
        .status { font-weight: bold; }
        .present { color: #047857; }
        .late { color: #b45309; }
        .excused { color: #0369a1; }
        .absent { color: #be123c; }
        .footer { border-top: 1px solid #cbd5e1; color: #64748b; font-size: 8px; margin-top: 20px; padding-top: 8px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">appjeunesse-kzi</div>
        <div class="muted">La Parole Éternelle — Kolwezi</div>
    </div>

    <div class="title">Rapport de présence</div>
    <div class="meta">Généré le {{ $generatedAt->translatedFormat('d F Y à H\hi') }}</div>

    @if (collect($filters)->filter()->isNotEmpty())
        <div class="meta" style="margin-top: 6px;">Filtres appliqués :
            {{ $filters['event_id'] ?? '' }} {{ $filters['dept'] ?? '' }} {{ $filters['status'] ?? '' }}
            {{ ! empty($filters['from']) ? 'du '.$filters['from'] : '' }} {{ ! empty($filters['to']) ? 'au '.$filters['to'] : '' }}
        </div>
    @endif

    @if ($summary->isNotEmpty())
        <table class="summary">
            <tr>
                @foreach ($summary->take(4) as $item)
                    <td><div class="summary-label">{{ $item->dept ?: 'Sans département' }}</div><strong>{{ $item->rate }}%</strong><br><span class="muted">{{ $item->present + $item->late }}/{{ $item->total }} présents ou retards</span></td>
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

    <div class="footer">Document officiel de suivi des présences · appjeunesse-kzi</div>
</body>
</html>