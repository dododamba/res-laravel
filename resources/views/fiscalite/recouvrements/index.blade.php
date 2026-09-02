@extends('layouts.app')

@section('title', 'Gestion des Recouvrements & Relances')

@section('content')
<!--begin::Header Layout-->
<div class="d-flex align-items-center justify-content-between mb-5">
    <div>
        <h1 class="fw-bold text-gray-900 mb-1">Gestion des Recouvrements & Relances</h1>
        <span class="text-muted fs-7">Suivi contentieux, sommations de payer et relances des impayés fiscaux</span>
    </div>
    <div>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal_relance">
            {!! $theme->getSvgIcon('duotune/arrows/arr075.svg', 'svg-icon-2 text-white me-1') !!}
            Enregistrer une Relance
        </button>
    </div>
</div>
<!--end::Header Layout-->

<div class="row g-5 g-xl-10">
    <div class="col-12">
        <div class="card card-flush shadow-sm">
            <div class="card-header align-items-center py-5">
                <h3 class="card-title fw-bold text-gray-900">Historique des Relances & Sommations</h3>
            </div>

            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed fs-6 gy-5">
                        <thead>
                            <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                <th>Date Relance</th>
                                <th>Opérateur Économique</th>
                                <th>Taxe Concerne</th>
                                <th class="text-end">Reste à Payer</th>
                                <th class="text-center">Statut Relance</th>
                                <th>Commentaires</th>
                            </tr>
                        </thead>
                        <tbody class="fw-semibold text-gray-600">
                            @forelse($recouvrements as $r)
                                <tr>
                                    <td>
                                        <span class="text-gray-900 fw-bold fs-7">{{ $r->date_relance ? $r->date_relance->format('d/m/Y H:i') : '-' }}</span>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span class="text-gray-800 fw-bold fs-6">{{ $r->taxeOperateur?->operateur?->nom_entreprise ?? $r->taxeOperateur?->operateur?->nom_commercial }}</span>
                                            <span class="text-muted fs-7">{{ $r->taxeOperateur?->operateur?->telephone }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-light-dark fs-7">{{ $r->taxeOperateur?->taxe?->code }}</span>
                                    </td>
                                    <td class="text-end">
                                        <span class="text-danger fw-bold fs-6">{{ number_format($r->taxeOperateur?->reste_a_payer) }} FCFA</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-light-warning fs-7 fw-bold">{{ $r->statut }}</span>
                                    </td>
                                    <td>
                                        <span class="text-gray-700 fs-7">{{ Str::limit($r->commentaires, 50) }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-5">
                                        Aucune relance enregistrée pour le moment.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-end mt-5">
                    {{ $recouvrements->links() }}
                </div>
            </div>
        </div>
    </div>

    <!-- Modal d'enregistrement de relance -->
    <div class="modal fade" id="modal_relance" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <form method="POST" action="{{ route('recouvrements.store') }}">
                    @csrf
                    <div class="modal-header">
                        <h2 class="fw-bold">Nouveau Suivi de Recouvrement</h2>
                        <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                            {!! $theme->getSvgIcon('duotune/arrows/arr061.svg', 'svg-icon-1') !!}
                        </div>
                    </div>
                    <div class="modal-body py-5">
                        <div class="mb-5">
                            <label class="required form-label fw-bold">Taxe en retard ou impayée</label>
                            <select name="taxe_operateur_id" class="form-select form-select-solid" required>
                                <option value="">-- Sélectionner l'opérateur en retard --</option>
                                @foreach($taxesOverdue as $txOp)
                                    <option value="{{ $txOp->id }}">
                                        [{{ $txOp->taxe?->code }}] {{ $txOp->operateur?->nom_entreprise ?? $txOp->operateur?->nom_commercial }} — Impayé : {{ number_format($txOp->reste_a_payer) }} FCFA (Date limite: {{ $txOp->date_limite?->format('d/m/Y') }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="row g-5 mb-5">
                            <div class="col-md-6">
                                <label class="required form-label fw-bold">Statut de la relance</label>
                                <select name="statut" class="form-select form-select-solid" required>
                                    <option value="Relance 1">Relance 1 (Avis amiable)</option>
                                    <option value="Relance 2">Relance 2 (Mise en demeure)</option>
                                    <option value="Sommation">Sommation de payer avec pénalités</option>
                                    <option value="Compte bloqué">Procédure Contentieuse</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="required form-label fw-bold">Date d'exécution relance</label>
                                <input type="datetime-local" name="date_relance" value="{{ now()->format('Y-m-d\TH:i') }}" class="form-control form-control-solid" required />
                            </div>
                        </div>

                        <div class="row g-5 mb-5">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Prochaine Échéance de relance</label>
                                <input type="date" name="prochaine_relance" value="{{ now()->addDays(7)->format('Y-m-d') }}" class="form-control form-control-solid" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Agent Recouvreur</label>
                                <select name="agent_id" class="form-select form-select-solid">
                                    <option value="">-- Agent Système --</option>
                                    @foreach($agents as $ag)
                                        <option value="{{ $ag->id }}">{{ $ag->personne?->prenom }} {{ $ag->personne?->nom }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="mb-5">
                            <label class="required form-label fw-bold">Commentaires et réponses du contribuable</label>
                            <textarea name="commentaires" class="form-control form-control-solid" rows="3" placeholder="Engagements de paiement du promoteur, contestations ou observations terrain..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Fermer</button>
                        <button type="submit" class="btn btn-primary">Enregistrer la Relance</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
