<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Rapport hebdomadaire Parc Informatique</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1f2937; }
        h1 { font-size: 20px; margin: 0 0 4px; }
        h2 { font-size: 14px; margin: 22px 0 8px; border-bottom: 1px solid #d1d5db; padding-bottom: 4px; }
        .muted { color: #6b7280; }
        .grid { width: 100%; border-collapse: collapse; margin-top: 8px; }
        .grid th, .grid td { border: 1px solid #e5e7eb; padding: 6px 8px; text-align: left; }
        .grid th { background: #f3f4f6; }
        .kpi { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .kpi td { width: 25%; padding: 10px; border: 1px solid #e5e7eb; vertical-align: top; }
        .kpi .label { color: #6b7280; font-size: 11px; }
        .kpi .value { font-size: 18px; font-weight: bold; margin-top: 4px; }
    </style>
</head>
<body>
    <h1>Rapport hebdomadaire — Parc Informatique</h1>
    <p class="muted">Période : {{ $report['period']['label'] }} · Généré le {{ $report['generated_at']->format('d/m/Y H:i') }}</p>

    <h2>Indicateurs tickets</h2>
    <table class="kpi">
        <tr>
            <td><div class="label">Ouverts (période)</div><div class="value">{{ $report['tickets']['opened'] }}</div></td>
            <td><div class="label">Résolus (période)</div><div class="value">{{ $report['tickets']['resolved'] }}</div></td>
            <td><div class="label">Ouverts maintenant</div><div class="value">{{ $report['tickets']['open_now'] }}</div></td>
            <td><div class="label">Non assignés</div><div class="value">{{ $report['tickets']['unassigned'] }}</div></td>
        </tr>
        <tr>
            <td><div class="label">Urgents ouverts</div><div class="value">{{ $report['tickets']['urgent_open'] }}</div></td>
            <td><div class="label">SLA dépassé</div><div class="value">{{ $report['tickets']['sla_breached'] }}</div></td>
            <td><div class="label">SLA à risque</div><div class="value">{{ $report['tickets']['sla_at_risk'] }}</div></td>
            <td>
                <div class="label">SLA respecté (clôtures période)</div>
                <div class="value">{{ $report['tickets']['sla_met_rate'] !== null ? $report['tickets']['sla_met_rate'].' %' : '—' }}</div>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <div class="label">Satisfaction moyenne (période)</div>
                <div class="value">
                    {{ $report['tickets']['avg_satisfaction'] !== null ? $report['tickets']['avg_satisfaction'].' / 5' : '—' }}
                    <span class="muted" style="font-size:11px;font-weight:normal">
                        ({{ $report['tickets']['satisfaction_count'] }} avis)
                    </span>
                </div>
            </td>
            <td colspan="2"></td>
        </tr>
    </table>

    <h2>Parc / maintenance</h2>
    <table class="kpi">
        <tr>
            <td><div class="label">Équipements</div><div class="value">{{ $report['assets']['total'] }}</div></td>
            <td><div class="label">En panne</div><div class="value">{{ $report['assets']['broken'] }}</div></td>
            <td><div class="label">Garantie expirée</div><div class="value">{{ $report['assets']['warranty_expired'] }}</div></td>
            <td><div class="label">Garantie &lt; 30 j</div><div class="value">{{ $report['assets']['warranty_expiring_30d'] }}</div></td>
        </tr>
        <tr>
            <td><div class="label">Maintenance due</div><div class="value">{{ $report['assets']['maintenance_due'] }}</div></td>
            <td><div class="label">Maintenance &lt; 30 j</div><div class="value">{{ $report['assets']['maintenance_soon'] }}</div></td>
            <td colspan="2"></td>
        </tr>
    </table>

    <h2>Top demandeurs (période)</h2>
    <table class="grid">
        <thead>
            <tr><th>Nom</th><th>E-mail</th><th>Tickets</th></tr>
        </thead>
        <tbody>
            @forelse ($report['top_requesters'] as $row)
                <tr>
                    <td>{{ $row['name'] }}</td>
                    <td>{{ $row['email'] ?? '—' }}</td>
                    <td>{{ $row['count'] }}</td>
                </tr>
            @empty
                <tr><td colspan="3">Aucun ticket sur la période.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Charge techniciens</h2>
    <table class="grid">
        <thead>
            <tr><th>Nom</th><th>E-mail</th><th>Tickets ouverts</th></tr>
        </thead>
        <tbody>
            @foreach ($report['technicians'] as $tech)
                <tr>
                    <td>{{ $tech['name'] }}</td>
                    <td>{{ $tech['email'] }}</td>
                    <td>{{ $tech['open_tickets'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
