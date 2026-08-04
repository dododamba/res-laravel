@extends('layouts.app')

@section('title', 'Saisie d\'un Encaissement Taxe')

@section('content')
<div class="row g-5 g-xl-10">
    <div class="col-xl-8">
        <div class="card card-flush shadow-sm mb-5 mb-xl-10">
            <div class="card-header">
                <h3 class="card-title fw-bold text-gray-900">Écran d'Encaissement de Taxe Municipale</h3>
            </div>

            <form method="POST" action="{{ route('paiements.store') }}" enctype="multipart/form-data" class="form">
                @csrf
                <div class="card-body">
                    <!-- Sélection de la taxe affectée à l'opérateur -->
                    <div class="mb-7">
                        <label class="required form-label fw-bold">Sélectionner la taxe concernée</label>
                        <select id="taxe_operateur_id" name="taxe_operateur_id" class="form-select form-select-solid form-select-lg" required onchange="updateFinancialSummary(this)">
                            <option value="">-- Sélectionner l'opérateur et sa taxe dûe --</option>
                            @if($selectedOperateur)
                                @foreach($selectedOperateur->taxesAffectees as $txOp)
                                    <option value="{{ $txOp->id }}" 
                                            data-attendu="{{ $txOp->montant_attendu }}" 
                                            data-paye="{{ $txOp->montant_paye }}" 
                                            data-reste="{{ $txOp->reste_a_payer }}"
                                            data-statut="{{ $txOp->statut?->label() }}"
                                            data-code="{{ $txOp->taxe?->code }}"
                                            data-nom="{{ $txOp->taxe?->nom }}"
                                            {{ ($selectedTaxeOp && $selectedTaxeOp->id == $txOp->id) ? 'selected' : '' }}>
                                        [{{ $txOp->taxe?->code }}] {{ $selectedOperateur->nom_entreprise ?? $selectedOperateur->nom_commercial }} — Reste : {{ number_format($txOp->reste_a_payer) }} FCFA (Statut: {{ $txOp->statut?->label() }})
                                    </option>
                                @endforeach
                            @else
                                @foreach($operateurs as $op)
                                    @foreach($op->taxesAffectees as $txOp)
                                        <option value="{{ $txOp->id }}" 
                                                data-attendu="{{ $txOp->montant_attendu }}" 
                                                data-paye="{{ $txOp->montant_paye }}" 
                                                data-reste="{{ $txOp->reste_a_payer }}"
                                                data-statut="{{ $txOp->statut?->label() }}"
                                                data-code="{{ $txOp->taxe?->code }}"
                                                data-nom="{{ $txOp->taxe?->nom }}"
                                                {{ ($selectedTaxeOp && $selectedTaxeOp->id == $txOp->id) ? 'selected' : '' }}>
                                            [{{ $txOp->taxe?->code }}] {{ $op->nom_entreprise ?? $op->nom_commercial }} — Reste : {{ number_format($txOp->reste_a_payer) }} FCFA
                                        </option>
                                    @endforeach
                                @endforeach
                            @endif
                        </select>
                    </div>

                    <!-- Synthèse financière dynamique -->
                    <div class="row g-5 mb-8">
                        <div class="col-md-4">
                            <div class="bg-light-primary rounded p-5 text-center">
                                <span class="fs-7 text-muted fw-bold d-block text-uppercase">Montant Attendu</span>
                                <span id="summary_attendu" class="fs-2x fw-bolder text-primary">0 FCFA</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="bg-light-success rounded p-5 text-center">
                                <span class="fs-7 text-muted fw-bold d-block text-uppercase">Déjà Payé</span>
                                <span id="summary_paye" class="fs-2x fw-bolder text-success">0 FCFA</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="bg-light-danger rounded p-5 text-center">
                                <span class="fs-7 text-muted fw-bold d-block text-uppercase">Reste à Payer</span>
                                <span id="summary_reste" class="fs-2x fw-bolder text-danger">0 FCFA</span>
                            </div>
                        </div>
                    </div>

                    <!-- Détails de la transaction -->
                    <div class="row g-5 mb-5">
                        <div class="col-md-6">
                            <label class="required form-label fw-bold">Montant à Encaisser (FCFA)</label>
                            <input type="number" step="0.01" id="montant" name="montant" value="{{ old('montant') }}" class="form-control form-control-solid form-control-lg" placeholder="Montant perçu en FCFA" required />
                        </div>
                        <div class="col-md-6">
                            <label class="required form-label fw-bold">Mode de Paiement</label>
                            <select name="mode_paiement" class="form-select form-select-solid form-select-lg" required>
                                @foreach(\App\Enums\ModePaiement::cases() as $mode)
                                    <option value="{{ $mode->value }}">{{ $mode->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row g-5 mb-5">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Référence Transaction / Chèque / Mobile</label>
                            <input type="text" name="reference" value="{{ old('reference') }}" class="form-control form-control-solid" placeholder="Ex: N° Transaction Wave/MTN, N° Chèque..." />
                        </div>
                        <div class="col-md-6">
                            <label class="required form-label fw-semibold">Date de règlement</label>
                            <input type="datetime-local" name="date_paiement" value="{{ old('date_paiement', now()->format('Y-m-d\TH:i')) }}" class="form-control form-control-solid" required />
                        </div>
                    </div>

                    <div class="row g-5 mb-5">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Agent Encaisseur / Guichetier</label>
                            <select name="agent_id" class="form-select form-select-solid">
                                <option value="">-- Guichet Central Web --</option>
                                @foreach($agents as $ag)
                                    <option value="{{ $ag->id }}">{{ $ag->personne?->prenom }} {{ $ag->personne?->nom }} ({{ $ag->matricule }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Justificatif / Reçu scanné (PDF, Image)</label>
                            <input type="file" name="justificatif" class="form-control form-control-solid" accept="application/pdf,image/*" />
                        </div>
                    </div>

                    <div class="mb-5">
                        <label class="form-label fw-semibold">Observation ou note complémentaire</label>
                        <textarea name="observation" class="form-control form-control-solid" rows="2" placeholder="Note éventuelle sur l'encaissement..."></textarea>
                    </div>
                </div>

                <div class="card-footer d-flex justify-content-end gap-3">
                    <a href="{{ route('paiements.index') }}" class="btn btn-light">Annuler</a>
                    <button type="submit" class="btn btn-success btn-lg">
                        {!! $theme->getSvgIcon('duotune/finance/fin008.svg', 'svg-icon-2 text-white me-2') !!}
                        Valider l'Encaissement et Générer le Reçu
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Panneau latéral d'information -->
    <div class="col-xl-4">
        <div class="card card-flush shadow-sm bg-light-info">
            <div class="card-header pt-5">
                <h3 class="card-title fw-bold text-info">Règles d'Encaissement</h3>
            </div>
            <div class="card-body">
                <ul class="text-gray-700 fs-6 lh-lg">
                    <li>Chaque encaissement génère automatiquement un <strong>numéro unique de reçu</strong>.</li>
                    <li>Un <strong>reçu officiel PDF imprimable</strong> est disponible immédiatement après validation.</li>
                    <li>Le statut de la taxe passe automatiquement à <strong>Soldé</strong> une fois le reste à payer nul.</li>
                    <li>Toute taxe au statut <strong>Soldé</strong> est verrouillée pour préserver la traçabilité comptable.</li>
                    <li>L'opération est journalisée dans l'<strong>Audit Log</strong> système.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function updateFinancialSummary(selectElem) {
        const option = selectElem.options[selectElem.selectedIndex];
        if (!option || !option.value) {
            document.getElementById('summary_attendu').innerText = '0 FCFA';
            document.getElementById('summary_paye').innerText = '0 FCFA';
            document.getElementById('summary_reste').innerText = '0 FCFA';
            document.getElementById('montant').value = '';
            return;
        }

        const attendu = parseFloat(option.getAttribute('data-attendu') || 0);
        const paye = parseFloat(option.getAttribute('data-paye') || 0);
        const reste = parseFloat(option.getAttribute('data-reste') || 0);

        document.getElementById('summary_attendu').innerText = new Intl.NumberFormat('fr-FR').format(attendu) + ' FCFA';
        document.getElementById('summary_paye').innerText = new Intl.NumberFormat('fr-FR').format(paye) + ' FCFA';
        document.getElementById('summary_reste').innerText = new Intl.NumberFormat('fr-FR').format(reste) + ' FCFA';

        document.getElementById('montant').value = reste;
    }

    document.addEventListener('DOMContentLoaded', function() {
        const select = document.getElementById('taxe_operateur_id');
        if (select && select.value) {
            updateFinancialSummary(select);
        }
    });
</script>
@endpush
@endsection
