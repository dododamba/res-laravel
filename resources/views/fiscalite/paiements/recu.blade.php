<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Reçu de Paiement - {{ $paiement->numero_recu }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
            background-color: #fff;
        }
        .receipt-box {
            max-width: 800px;
            margin: auto;
            border: 2px solid #2b3a4a;
            padding: 30px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.15);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #2b3a4a;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header-title {
            text-align: center;
        }
        .header-title h2 {
            margin: 0;
            font-size: 18px;
            text-transform: uppercase;
            color: #1b2a4a;
        }
        .header-title h3 {
            margin: 5px 0 0 0;
            font-size: 14px;
            font-weight: normal;
            color: #555;
        }
        .receipt-title {
            text-align: center;
            background: #1b2a4a;
            color: #fff;
            padding: 10px;
            font-size: 18px;
            font-weight: bold;
            letter-spacing: 1px;
            margin-bottom: 25px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
        }
        .info-box {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            padding: 15px;
            border-radius: 4px;
        }
        .info-box h4 {
            margin-top: 0;
            margin-bottom: 10px;
            font-size: 14px;
            color: #1b2a4a;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 6px;
            font-size: 13px;
        }
        .info-label {
            color: #6c757d;
        }
        .info-value {
            font-weight: bold;
            color: #212529;
        }
        table.table-details {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        table.table-details th, table.table-details td {
            border: 1px solid #dee2e6;
            padding: 10px;
            text-align: left;
            font-size: 13px;
        }
        table.table-details th {
            background-color: #f1f3f5;
            color: #1b2a4a;
        }
        .amount-box {
            text-align: right;
            font-size: 16px;
            font-weight: bold;
            background: #e8f5e9;
            padding: 12px;
            border: 1px solid #c8e6c9;
            border-radius: 4px;
            margin-bottom: 30px;
        }
        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
            padding-top: 20px;
        }
        .sig-block {
            width: 45%;
            text-align: center;
            font-size: 12px;
        }
        .sig-space {
            height: 70px;
            border-bottom: 1px dashed #aaa;
            margin-top: 10px;
        }
        @media print {
            body { padding: 0; }
            .receipt-box { border: none; box-shadow: none; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

<div class="no-print" style="max-width: 800px; margin: 0 auto 15px auto; text-align: right;">
    <button onclick="window.print()" style="padding: 10px 20px; background: #28a745; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; font-weight: bold;">Imprimer ce reçû (PDF)</button>
</div>

<div class="receipt-box">
    <div class="header">
        <div>
            <strong style="font-size: 14px;">RÉPUBLIQUE DE CÔTE D'IVOIRE</strong><br>
            <span style="font-size: 12px; color: #666;">Union - Discipline - Travail</span>
        </div>
        <div class="header-title">
            <h2>MAIRIE DE LA COMMUNE</h2>
            <h3>Direction des Services Financiers & Recette Municipale</h3>
        </div>
        <div style="text-align: right;">
            <strong style="font-size: 13px;">QUITTANCE DE PAIEMENT</strong><br>
            <span style="font-size: 11px; color: #666;">Original Contribuable</span>
        </div>
    </div>

    <div class="receipt-title">
        REÇU UNIFIÉ DE PAIEMENT FISCAL MUNICIPAL
    </div>

    <div class="info-grid">
        <div class="info-box">
            <h4>IDENTIFICATION DU CONTRIBUABLE</h4>
            <div class="info-row"><span class="info-label">Raison Sociale:</span> <span class="info-value">{{ $paiement->taxeOperateur?->operateur?->nom_entreprise ?? $paiement->taxeOperateur?->operateur?->nom_commercial }}</span></div>
            <div class="info-row"><span class="info-label">Promoteur:</span> <span class="info-value">{{ $paiement->taxeOperateur?->operateur?->promoteur_prenom }} {{ $paiement->taxeOperateur?->operateur?->promoteur_nom }}</span></div>
            <div class="info-row"><span class="info-label">N° RCCM:</span> <span class="info-value">{{ $paiement->taxeOperateur?->operateur?->rccm ?? 'Non renseigné' }}</span></div>
            <div class="info-row"><span class="info-label">N° NIF:</span> <span class="info-value">{{ $paiement->taxeOperateur?->operateur?->nif ?? 'Non renseigné' }}</span></div>
            <div class="info-row"><span class="info-label">Quartier:</span> <span class="info-value">{{ $paiement->taxeOperateur?->operateur?->quartier?->nom ?? '-' }}</span></div>
        </div>

        <div class="info-box">
            <h4>RÉFÉRENCES DU PAIEMENT</h4>
            <div class="info-row"><span class="info-label">N° Reçu Unique:</span> <span class="info-value">{{ $paiement->numero_recu }}</span></div>
            <div class="info-row"><span class="info-label">Date du Paiement:</span> <span class="info-value">{{ $paiement->date_paiement ? $paiement->date_paiement->format('d/m/Y H:i') : '-' }}</span></div>
            <div class="info-row"><span class="info-label">Mode de Règlement:</span> <span class="info-value">{{ is_object($paiement->mode_paiement) ? $paiement->mode_paiement->value : $paiement->mode_paiement }}</span></div>
            <div class="info-row"><span class="info-label">Réf. Transaction:</span> <span class="info-value">{{ $paiement->reference ?? 'Espèces Directes' }}</span></div>
            <div class="info-row"><span class="info-label">Année Fiscale:</span> <span class="info-value">{{ $paiement->taxeOperateur?->annee_fiscale }}</span></div>
        </div>
    </div>

    <table class="table-details">
        <thead>
            <tr>
                <th>Code Taxe</th>
                <th>Désignation de la Taxe Municipale</th>
                <th style="text-align: right;">Montant Attendu</th>
                <th style="text-align: right;">Montant Réglé Ce Jour</th>
                <th style="text-align: right;">Reste à Payer</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>{{ $paiement->taxeOperateur?->taxe?->code }}</strong></td>
                <td>{{ $paiement->taxeOperateur?->taxe?->nom }}</td>
                <td style="text-align: right;">{{ number_format($paiement->taxeOperateur?->montant_attendu) }} FCFA</td>
                <td style="text-align: right; color: #28a745; font-weight: bold;">{{ number_format($paiement->montant) }} FCFA</td>
                <td style="text-align: right; color: #dc3545;">{{ number_format($paiement->taxeOperateur?->reste_a_payer) }} FCFA</td>
            </tr>
        </tbody>
    </table>

    <div class="amount-box">
        MONTANT NET PERÇU: <span style="color: #28a745; font-size: 20px;">{{ number_format($paiement->montant) }} FCFA</span>
    </div>

    <div class="signatures">
        <div class="sig-block">
            <strong>Le Contribuable ou Son Représentant</strong><br>
            <i>Signature & Empreinte</i>
            <div class="sig-space"></div>
        </div>
        <div class="sig-block">
            <strong>Pour la Recette Municipale</strong><br>
            <i>L'Agent Encaisseur / Cachet Officiel</i>
            <div class="sig-space">
                <div style="font-weight: bold; color: #1b2a4a; padding-top: 10px;">
                    {{ $paiement->agent?->personne?->prenom }} {{ $paiement->agent?->personne?->nom }}
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
