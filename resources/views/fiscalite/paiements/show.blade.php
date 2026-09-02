@extends('layouts.app')

@section('title', 'Détails du Paiement N° ' . $paiement->numero_recu)
@section('page_title', 'Quittance de Paiement N° ' . $paiement->numero_recu)
@section('page_subtitle', 'Certificat d\'encaissement et situation fiscale de l\'opérateur')

@section('actions')
    <a href="{{ route('paiements.recu', $paiement) }}" target="_blank" class="btn btn-sm btn-primary d-flex align-items-center me-2">
        {!! $theme->getSvgIcon('duotune/general/gen005.svg', 'svg-icon-2 text-white me-2') !!}
        Imprimer le Reçu Officiel
    </a>
    <a href="{{ route('paiements.index') }}" class="btn btn-sm btn-light">Retour à la liste</a>
@endsection

@section('content')
<div class="card card-flush shadow-sm">

    <div class="card-body pt-5">
        <div class="row g-5 mb-8">
            <!-- Informations sur l'Opérateur -->
            <div class="col-md-6">
                <div class="border border-dashed rounded p-6 bg-light">
                    <h4 class="fw-bold text-gray-800 mb-4">Opérateur Économique</h4>
                    <div class="fs-6 text-gray-700 fw-semibold mb-2">
                        <span class="text-muted">Raison Sociale :</span> 
                        <strong class="text-gray-900">{{ $paiement->taxeOperateur?->operateur?->nom_entreprise ?? $paiement->taxeOperateur?->operateur?->nom_commercial }}</strong>
                    </div>
                    <div class="fs-6 text-gray-700 fw-semibold mb-2">
                        <span class="text-muted">Promoteur :</span> {{ $paiement->taxeOperateur?->operateur?->promoteur_prenom }} {{ $paiement->taxeOperateur?->operateur?->promoteur_nom }}
                    </div>
                    <div class="fs-6 text-gray-700 fw-semibold mb-2">
                        <span class="text-muted">RCCM :</span> {{ $paiement->taxeOperateur?->operateur?->rccm ?? '-' }} | <span class="text-muted">NIF :</span> {{ $paiement->taxeOperateur?->operateur?->nif ?? '-' }}
                    </div>
                    <div class="fs-6 text-gray-700 fw-semibold">
                        <span class="text-muted">Localisation :</span> {{ $paiement->taxeOperateur?->operateur?->quartier?->nom ?? 'Quartier non défini' }}, {{ $paiement->taxeOperateur?->operateur?->adresse }}
                    </div>
                </div>
            </div>

            <!-- Informations sur la Taxe & Règlement -->
            <div class="col-md-6">
                <div class="border border-dashed rounded p-6 bg-light-primary">
                    <h4 class="fw-bold text-primary mb-4">Détails de la Taxe & Encaissement</h4>
                    <div class="fs-6 text-gray-700 fw-semibold mb-2">
                        <span class="text-muted">Taxe Municipale :</span> 
                        <strong class="text-gray-900">[{{ $paiement->taxeOperateur?->taxe?->code }}] {{ $paiement->taxeOperateur?->taxe?->nom }}</strong>
                    </div>
                    <div class="fs-6 text-gray-700 fw-semibold mb-2">
                        <span class="text-muted">Montant Encaissé :</span> 
                        <strong class="text-success fs-4">{{ number_format($paiement->montant) }} FCFA</strong>
                    </div>
                    <div class="fs-6 text-gray-700 fw-semibold mb-2">
                        <span class="text-muted">Mode de Règlement :</span> {{ is_object($paiement->mode_paiement) ? $paiement->mode_paiement->value : $paiement->mode_paiement }}
                    </div>
                    <div class="fs-6 text-gray-700 fw-semibold mb-2">
                        <span class="text-muted">Référence Transaction :</span> {{ $paiement->reference ?? 'En espèces directes' }}
                    </div>
                    <div class="fs-6 text-gray-700 fw-semibold">
                        <span class="text-muted">Date & Heure :</span> {{ $paiement->date_paiement ? $paiement->date_paiement->format('d/m/Y à H:i') : '-' }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Solde Actuel de la Taxe après paiement -->
        <div class="row g-5 mb-8">
            <div class="col-md-4">
                <div class="bg-light p-4 rounded text-center">
                    <span class="fs-7 text-muted fw-bold d-block text-uppercase">Total Attendu Année {{ $paiement->taxeOperateur?->annee_fiscale }}</span>
                    <span class="fs-3 fw-bold text-gray-900">{{ number_format($paiement->taxeOperateur?->montant_attendu) }} FCFA</span>
                </div>
            </div>
            <div class="col-md-4">
                <div class="bg-light p-4 rounded text-center">
                    <span class="fs-7 text-muted fw-bold d-block text-uppercase">Cumul Payé à ce jour</span>
                    <span class="fs-3 fw-bold text-success">{{ number_format($paiement->taxeOperateur?->montant_paye) }} FCFA</span>
                </div>
            </div>
            <div class="col-md-4">
                <div class="bg-light p-4 rounded text-center">
                    <span class="fs-7 text-muted fw-bold d-block text-uppercase">Nouveau Reste à Payer</span>
                    <span class="fs-3 fw-bold text-danger">{{ number_format($paiement->taxeOperateur?->reste_a_payer) }} FCFA</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
