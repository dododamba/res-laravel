@extends('layouts.app')

@section('title', 'Fiche Opérateur : ' . ($operateur->nom_entreprise ?? $operateur->nom_commercial))

@section('content')
<div class="d-flex flex-column flex-xl-row gap-7 gap-lg-10">
    <!-- Colonne Gauche : Synthèse Opérateur -->
    <div class="flex-column flex-lg-row-auto w-100 w-xl-350px">
        <div class="card card-flush shadow-sm mb-5">
            <div class="card-body pt-9 text-center">
                <div class="symbol symbol-100px symbol-circle mb-5">
                    <span class="symbol-label bg-light-primary text-primary fs-2x fw-bold">
                        {{ strtoupper(substr($operateur->nom_entreprise ?? $operateur->nom_commercial, 0, 2)) }}
                    </span>
                </div>

                <a href="#" class="fs-3 text-gray-800 text-hover-primary fw-bold d-block mb-1">{{ $operateur->nom_entreprise ?? $operateur->nom_commercial }}</a>
                <span class="badge {{ $operateur->statut->badgeClass() }} fs-7 fw-bold mb-5">{{ $operateur->statut->label() }}</span>

                <div class="d-flex flex-stack fs-6 text-gray-600 text-start border-top pt-4">
                    <span class="fw-semibold">Promoteur :</span>
                    <span class="fw-bold text-gray-800">{{ $operateur->promoteur_prenom }} {{ $operateur->promoteur_nom }}</span>
                </div>
                <div class="d-flex flex-stack fs-6 text-gray-600 text-start pt-2">
                    <span class="fw-semibold">Téléphone :</span>
                    <span class="fw-bold text-gray-800">{{ $operateur->telephone ?? '-' }}</span>
                </div>
                <div class="d-flex flex-stack fs-6 text-gray-600 text-start pt-2">
                    <span class="fw-semibold">RCCM :</span>
                    <span class="fw-bold text-gray-800">{{ $operateur->rccm ?? '-' }}</span>
                </div>
                <div class="d-flex flex-stack fs-6 text-gray-600 text-start pt-2">
                    <span class="fw-semibold">NIF :</span>
                    <span class="fw-bold text-gray-800">{{ $operateur->nif ?? '-' }}</span>
                </div>
                <div class="d-flex flex-stack fs-6 text-gray-600 text-start pt-2">
                    <span class="fw-semibold">Taille :</span>
                    <span class="badge badge-light-info">{{ $operateur->taille?->value ?? 'Micro' }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Colonne Droite : Onglets d'Information et Fiscalité -->
    <div class="flex-lg-row-fluid">
        <!-- Tabs Navigation -->
        <ul class="nav nav-custom nav-tabs nav-line-tabs nav-line-tabs-2x border-0 fs-4 fw-bold mb-5">
            <li class="nav-item">
                <a class="nav-link text-active-primary pb-4 active" data-bs-toggle="tab" href="#kt_tab_general">Informations Générales</a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-active-primary pb-4" data-bs-toggle="tab" href="#kt_tab_taxes">
                    Taxes Municipales & Fiscalité 
                    <span class="badge badge-sm badge-circle badge-danger ms-2">{{ $operateur->taxesAffectees->where('reste_a_payer', '>', 0)->count() }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-active-primary pb-4" data-bs-toggle="tab" href="#kt_tab_historique">Historique & Timeline</a>
            </li>
        </ul>

        <div class="tab-content" id="myTabContent">
            <!-- TAB 1 : Général -->
            <div class="tab-pane fade show active" id="kt_tab_general" role="tabpanel">
                <div class="card card-flush shadow-sm">
                    <div class="card-body">
                        <h4 class="fw-bold text-gray-800 mb-5">Localisation & Effectifs</h4>
                        <div class="row g-5 mb-5">
                            <div class="col-md-6">
                                <span class="text-muted fw-semibold d-block">Quartier :</span>
                                <span class="fw-bold text-gray-900 fs-6">{{ $operateur->quartier?->nom ?? 'Non défini' }}</span>
                            </div>
                            <div class="col-md-6">
                                <span class="text-muted fw-semibold d-block">Carré / Bloc :</span>
                                <span class="fw-bold text-gray-900 fs-6">{{ $operateur->carre?->nom ?? 'Non défini' }}</span>
                            </div>
                        </div>
                        <div class="row g-5 mb-5">
                            <div class="col-md-6">
                                <span class="text-muted fw-semibold d-block">Effectif Total :</span>
                                <span class="fw-bold text-gray-900 fs-6">{{ $operateur->effectif_total }} salariés</span>
                            </div>
                            <div class="col-md-6">
                                <span class="text-muted fw-semibold d-block">Permanents / Temporaires :</span>
                                <span class="fw-bold text-gray-900 fs-6">{{ $operateur->effectif_permanents }} perm. / {{ $operateur->effectif_temporaires }} temp.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 2 : TAXES MUNICIPALES (NOUVEAU MODULE) -->
            <div class="tab-pane fade" id="kt_tab_taxes" role="tabpanel">
                <!-- Synthese Financiere Opérateur -->
                <div class="row g-5 mb-5">
                    <div class="col-md-3">
                        <div class="bg-light-primary rounded p-4 text-center">
                            <span class="fs-7 text-muted fw-bold d-block text-uppercase">Total Dû</span>
                            <span class="fs-2x fw-bolder text-primary">{{ number_format($operateur->total_du) }} FCFA</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="bg-light-success rounded p-4 text-center">
                            <span class="fs-7 text-muted fw-bold d-block text-uppercase">Total Payé</span>
                            <span class="fs-2x fw-bolder text-success">{{ number_format($operateur->total_paye) }} FCFA</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="bg-light-danger rounded p-4 text-center">
                            <span class="fs-7 text-muted fw-bold d-block text-uppercase">Reste à Payer</span>
                            <span class="fs-2x fw-bolder text-danger">{{ number_format($operateur->reste_a_payer) }} FCFA</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="bg-light-warning rounded p-4 text-center">
                            <span class="fs-7 text-muted fw-bold d-block text-uppercase">Taux Recouvrement</span>
                            <span class="fs-2x fw-bolder text-warning">{{ $operateur->taux_recouvrement }}%</span>
                        </div>
                    </div>
                </div>

                <!-- Card Liste des Taxes Affectees -->
                <div class="card card-flush shadow-sm mb-5">
                    <div class="card-header align-items-center">
                        <h3 class="card-title fw-bold text-gray-900 fs-4">Taxes Municipales Affectées ({{ date('Y') }})</h3>
                        <div class="card-toolbar">
                            <a href="{{ route('paiements.create', ['operateur_id' => $operateur->id]) }}" class="btn btn-success btn-sm">
                                {!! $theme->getSvgIcon('duotune/finance/fin008.svg', 'svg-icon-2 text-white me-1') !!}
                                Encaisser un Paiement
                            </a>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed fs-6 gy-4">
                                <thead>
                                    <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                        <th>Code & Taxe</th>
                                        <th class="text-end">Montant Dû</th>
                                        <th class="text-end">Montant Payé</th>
                                        <th class="text-end">Reste à Payer</th>
                                        <th class="text-center">Échéance</th>
                                        <th class="text-center">Statut</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($operateur->taxesAffectees as $txOp)
                                        <tr>
                                            <td>
                                                <span class="badge badge-light-dark fw-bold me-2">{{ $txOp->taxe?->code }}</span>
                                                <span class="text-gray-900 fw-bold">{{ $txOp->taxe?->nom }}</span>
                                            </td>
                                            <td class="text-end fw-bold text-gray-800">{{ number_format($txOp->montant_attendu) }} FCFA</td>
                                            <td class="text-end fw-bold text-success">{{ number_format($txOp->montant_paye) }} FCFA</td>
                                            <td class="text-end fw-bold text-danger">{{ number_format($txOp->reste_a_payer) }} FCFA</td>
                                            <td class="text-center">
                                                <span class="fs-7 text-muted">{{ $txOp->date_limite?->format('d/m/Y') }}</span>
                                                @if($txOp->jours_retard > 0)
                                                    <span class="badge badge-light-danger ms-1">+{{ $txOp->jours_retard }}j retard</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <span class="badge {{ $txOp->statut?->badgeClass() }} fs-7 fw-bold">{{ $txOp->statut?->label() }}</span>
                                            </td>
                                            <td class="text-end">
                                                @if(!$txOp->est_solde)
                                                    <a href="{{ route('paiements.create', ['taxe_operateur_id' => $txOp->id]) }}" class="btn btn-sm btn-primary">
                                                        Payer
                                                    </a>
                                                @else
                                                    <span class="badge badge-light-success">Soldé</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4">Aucune taxe affectée à cet opérateur.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Historique des Encaissements -->
                <div class="card card-flush shadow-sm">
                    <div class="card-header">
                        <h3 class="card-title fw-bold text-gray-900 fs-4">Historique des Paiements Effectués</h3>
                    </div>
                    <div class="card-body pt-0">
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed fs-6 gy-4">
                                <thead>
                                    <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                        <th>N° Reçu</th>
                                        <th>Date</th>
                                        <th>Mode</th>
                                        <th class="text-end">Montant Encaissé</th>
                                        <th class="text-end">Reçu PDF</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($operateur->paiements as $p)
                                        <tr>
                                            <td><span class="badge badge-light-primary fw-bold">{{ $p->numero_recu }}</span></td>
                                            <td>{{ $p->date_paiement ? $p->date_paiement->format('d/m/Y H:i') : '-' }}</td>
                                            <td><span class="badge badge-light-info">{{ is_object($p->mode_paiement) ? $p->mode_paiement->value : $p->mode_paiement }}</span></td>
                                            <td class="text-end fw-bolder text-success">+ {{ number_format($p->montant) }} FCFA</td>
                                            <td class="text-end">
                                                <a href="{{ route('paiements.recu', $p) }}" target="_blank" class="btn btn-icon btn-sm btn-light-primary" title="Imprimer le reçu">
                                                    {!! $theme->getSvgIcon('duotune/general/gen005.svg', 'svg-icon-3') !!}
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-4">Aucun encaissement répertorié.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 3 : Historique -->
            <div class="tab-pane fade" id="kt_tab_historique" role="tabpanel">
                <div class="card card-flush shadow-sm">
                    <div class="card-body">
                        <div class="timeline-label">
                            @forelse($operateur->historiques as $hist)
                                <div class="timeline-item">
                                    <div class="timeline-label fw-bold text-gray-800 fs-6">{{ $hist->created_at->format('H:i') }}</div>
                                    <div class="timeline-badge">
                                        <i class="fa fa-genderless text-success fs-1"></i>
                                    </div>
                                    <div class="timeline-content fw-mormal text-gray-800 ps-3">
                                        <span class="fw-bold">{{ strtoupper($hist->action) }}</span> — {{ $hist->details['message'] ?? 'Action effectuée' }}
                                        <span class="text-muted fs-7 d-block">Par {{ $hist->user_identifier }} le {{ $hist->created_at->format('d/m/Y') }}</span>
                                    </div>
                                </div>
                            @empty
                                <div class="text-muted">Aucun historique disponible.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
