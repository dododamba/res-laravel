@extends('layouts.app')

@section('title', 'Gestion des Exonérations Fiscales')
@section('page_title', 'Gestion des Exonérations Fiscales')
@section('page_subtitle', 'Registre des dégrèvements, avis d\'exonération et arrêtés d\'exemption municipale')

@section('actions')
    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modal_exoneration">
        {!! $theme->getSvgIcon('duotune/arrows/arr075.svg', 'svg-icon-2 text-white me-1') !!}
        Accorder une Exonération
    </button>
@endsection

@section('content')

<div class="row g-5 g-xl-10">
    <div class="col-12">
        <div class="card card-flush shadow-sm">
            <div class="card-header align-items-center py-5">
                <h3 class="card-title fw-bold text-gray-900">Avis d'Exonération et Dégrèvements Accordés</h3>
            </div>

            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed fs-6 gy-5">
                        <thead>
                            <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                <th>Date Avis</th>
                                <th>Opérateur Économique</th>
                                <th>Taxe Exonérée</th>
                                <th class="text-end">Montant Exonéré</th>
                                <th>Autorité Décisionnaire</th>
                                <th>Motif Exonération</th>
                            </tr>
                        </thead>
                        <tbody class="fw-semibold text-gray-600">
                            @forelse($exonerations as $ex)
                                <tr>
                                    <td>
                                        <span class="text-gray-900 fw-bold fs-7">{{ $ex->date_exoneration ? $ex->date_exoneration->format('d/m/Y') : '-' }}</span>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span class="text-gray-800 fw-bold fs-6">{{ $ex->taxeOperateur?->operateur?->nom_entreprise ?? $ex->taxeOperateur?->operateur?->nom_commercial }}</span>
                                            <span class="text-muted fs-7">NIF: {{ $ex->taxeOperateur?->operateur?->nif ?? '-' }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-light-dark fs-7">{{ $ex->taxeOperateur?->taxe?->code }}</span>
                                    </td>
                                    <td class="text-end">
                                        <span class="text-info fw-bold fs-6">- {{ number_format($ex->montant_exonere) }} FCFA</span>
                                    </td>
                                    <td>
                                        <span class="text-gray-800 fw-bold fs-7">{{ $ex->autorite }}</span>
                                    </td>
                                    <td>
                                        <span class="text-gray-700 fs-7">{{ Str::limit($ex->motif, 50) }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-5">
                                        Aucune exonération fiscale accordée pour l'instant.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-end mt-5">
                    {{ $exonerations->links() }}
                </div>
            </div>
        </div>
    </div>

    <!-- Modal d'enregistrement d'exonération -->
    <div class="modal fade" id="modal_exoneration" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <form method="POST" action="{{ route('exonerations.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h2 class="fw-bold">Accorder un Dégrèvement / Exonération</h2>
                        <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                            {!! $theme->getSvgIcon('duotune/arrows/arr061.svg', 'svg-icon-1') !!}
                        </div>
                    </div>
                    <div class="modal-body py-5">
                        <div class="mb-5">
                            <label class="required form-label fw-bold">Sélectionner la taxe de l'opérateur concerné</label>
                            <select name="taxe_operateur_id" class="form-select form-select-solid" required>
                                <option value="">-- Choisir une taxe non soldée --</option>
                                @foreach($taxesOperateurs as $txOp)
                                    <option value="{{ $txOp->id }}">
                                        [{{ $txOp->taxe?->code }}] {{ $txOp->operateur?->nom_entreprise ?? $txOp->operateur?->nom_commercial }} — Montant dû : {{ number_format($txOp->montant_attendu) }} FCFA (Reste: {{ number_format($txOp->reste_a_payer) }} FCFA)
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="row g-5 mb-5">
                            <div class="col-md-6">
                                <label class="required form-label fw-bold">Montant Exonéré (FCFA)</label>
                                <input type="number" step="0.01" name="montant_exonere" class="form-control form-control-solid" placeholder="Montant dégrevé" required />
                            </div>
                            <div class="col-md-6">
                                <label class="required form-label fw-bold">Date de décision</label>
                                <input type="date" name="date_exoneration" value="{{ date('Y-m-d') }}" class="form-control form-control-solid" required />
                            </div>
                        </div>

                        <div class="row g-5 mb-5">
                            <div class="col-md-6">
                                <label class="required form-label fw-bold">Autorité Décisionnaire / Arrêté</label>
                                <input type="text" name="autorite" class="form-control form-control-solid" placeholder="Ex: Décision Maire, Arrêté Municipal N°..." required />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Document / Arrêté officiel (PDF/Image)</label>
                                <input type="file" name="document" class="form-control form-control-solid" accept="application/pdf,image/*" />
                            </div>
                        </div>

                        <div class="mb-5">
                            <label class="required form-label fw-bold">Motif légal ou justification sociale/économique</label>
                            <textarea name="motif" class="form-control form-control-solid" rows="3" placeholder="Dispositions légales, exonération temporaire jeune entreprise, sinistre..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Valider l'Exonération</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
