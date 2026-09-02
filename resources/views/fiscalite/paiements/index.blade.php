@extends('layouts.app')

@section('title', 'Journal des Paiements & Encaissements')

@section('content')
<!--begin::Header Layout-->
<div class="d-flex align-items-center justify-content-between mb-5">
    <div>
        <h1 class="fw-bold text-gray-900 mb-1">Journal des Paiements & Encaissements</h1>
        <span class="text-muted fs-7">Historique certifié des quittances, reçus d'encaissement et règlements fiscaux</span>
    </div>
    <div>
        <a href="{{ route('paiements.create') }}" class="btn btn-primary d-flex align-items-center">
            {!! $theme->getSvgIcon('duotune/arrows/arr075.svg', 'svg-icon-2 text-white me-2') !!}
            Nouvel Encaissement
        </a>
    </div>
</div>
<!--end::Header Layout-->

<!--begin::Card-->
<div class="card card-flush shadow-sm">
    <!--begin::Card Header-->
    <div class="card-header align-items-center py-5 gap-2 gap-md-5">
        <form method="GET" action="{{ route('paiements.index') }}" class="d-flex flex-wrap align-items-center gap-3 w-100 justify-content-between">
            <div class="d-flex align-items-center position-relative my-1">
                <span class="svg-icon svg-icon-1 position-absolute ms-4">
                    {!! $theme->getSvgIcon('duotune/general/gen021.svg', 'svg-icon-2') !!}
                </span>
                <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-solid w-250px ps-14" placeholder="N° Reçu, Référence, Opérateur..." />
            </div>

            <div class="d-flex flex-wrap align-items-center gap-2">
                <select name="annee" class="form-select form-select-solid w-130px" onchange="this.form.submit()">
                    <option value="">Toutes années</option>
                    @foreach([2026, 2025, 2024] as $yr)
                        <option value="{{ $yr }}" {{ request('annee', date('Y')) == $yr ? 'selected' : '' }}>{{ $yr }}</option>
                    @endforeach
                </select>

                <select name="taxe_id" class="form-select form-select-solid w-180px" onchange="this.form.submit()">
                    <option value="">Toutes les taxes</option>
                    @foreach($taxes as $t)
                        <option value="{{ $t->id }}" {{ request('taxe_id') == $t->id ? 'selected' : '' }}>{{ $t->code }} - {{ $t->nom }}</option>
                    @endforeach
                </select>

                <select name="quartier_id" class="form-select form-select-solid w-150px" onchange="this.form.submit()">
                    <option value="">Tous quartiers</option>
                    @foreach($quartiers as $q)
                        <option value="{{ $q->id }}" {{ request('quartier_id') == $q->id ? 'selected' : '' }}>{{ $q->nom }}</option>
                    @endforeach
                </select>

                <select name="mode_paiement" class="form-select form-select-solid w-150px" onchange="this.form.submit()">
                    <option value="">Tous les modes</option>
                    @foreach(\App\Enums\ModePaiement::cases() as $mode)
                        <option value="{{ $mode->value }}" {{ request('mode_paiement') == $mode->value ? 'selected' : '' }}>{{ $mode->value }}</option>
                    @endforeach
                </select>

                @if(request()->anyFilled(['q', 'taxe_id', 'quartier_id', 'mode_paiement']) || (request('annee') && request('annee') != date('Y')))
                    <a href="{{ route('paiements.index') }}" class="btn btn-sm btn-light-danger me-2" title="Réinitialiser">
                        <i class="fas fa-undo me-1"></i>Effacer
                    </a>
                @endif
            </div>
        </form>
    </div>
    <!--end::Card Header-->

    <div class="card-body pt-0">
        <div class="table-responsive">
            <table class="table align-middle table-row-dashed fs-6 gy-5">
                <thead>
                    <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                        <th class="min-w-140px">N° Reçu</th>
                        <th class="min-w-120px">Date & Heure</th>
                        <th class="min-w-180px">Opérateur Économique</th>
                        <th class="min-w-150px">Taxe Concerne</th>
                        <th class="min-w-120px text-end">Montant Payé</th>
                        <th class="min-w-120px text-center">Mode Paiement</th>
                        <th class="min-w-130px">Agent Encaisseur</th>
                        <th class="text-end min-w-100px">Actions</th>
                    </tr>
                </thead>
                <tbody class="fw-semibold text-gray-600">
                    @forelse($paiements as $p)
                        <tr>
                            <td>
                                <a href="{{ route('paiements.show', $p) }}" class="badge badge-light-primary fs-7 fw-bold text-hover-primary">{{ $p->numero_recu }}</a>
                                @if($p->reference)
                                    <span class="d-block text-muted fs-8">Réf: {{ $p->reference }}</span>
                                @endif
                            </td>
                            <td>
                                <span class="text-gray-900 fw-bold fs-7">{{ $p->date_paiement ? $p->date_paiement->format('d/m/Y H:i') : '-' }}</span>
                            </td>
                            <td>
                                <div class="d-flex flex-column">
                                    <span class="text-gray-800 fw-bold fs-6">{{ $p->taxeOperateur?->operateur?->nom_entreprise ?? $p->taxeOperateur?->operateur?->nom_commercial }}</span>
                                    <span class="text-muted fs-7">Quartier: {{ $p->taxeOperateur?->operateur?->quartier?->nom ?? 'S/D' }}</span>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-light-dark fs-7">{{ $p->taxeOperateur?->taxe?->code }}</span>
                                <span class="d-block text-gray-800 fs-7 fw-semibold">{{ $p->taxeOperateur?->taxe?->nom }}</span>
                            </td>
                            <td class="text-end">
                                <span class="text-success fw-bolder fs-6">+ {{ number_format($p->montant) }} FCFA</span>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-light-info fs-7">{{ is_object($p->mode_paiement) ? $p->mode_paiement->value : $p->mode_paiement }}</span>
                            </td>
                            <td>
                                <span class="text-gray-800 fw-bold fs-7">{{ $p->agent?->personne?->prenom }} {{ $p->agent?->personne?->nom }}</span>
                                <span class="text-muted fs-8 d-block">{{ $p->user?->email ?? ($p->agent?->matricule ?? 'Web') }}</span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('paiements.recu', $p) }}" target="_blank" class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm me-1" title="Imprimer le reçu PDF">
                                    {!! $theme->getSvgIcon('duotune/general/gen005.svg', 'svg-icon-3') !!}
                                </a>
                                <a href="{{ route('paiements.show', $p) }}" class="btn btn-icon btn-bg-light btn-active-color-info btn-sm" title="Détails du paiement">
                                    {!! $theme->getSvgIcon('duotune/general/gen019.svg', 'svg-icon-3') !!}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                Aucun encaissement trouvé selon les critères de recherche.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-end mt-5">
            {{ $paiements->links() }}
        </div>
    </div>
</div>
@endsection
