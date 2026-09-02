@extends('layouts.app')

@section('title', 'Opérateurs Économiques')

@section('content')
<!--begin::Header Layout-->
<div class="d-flex align-items-center justify-content-between mb-5">
    <div>
        <h1 class="fw-bold text-gray-900 mb-1">Opérateurs Économiques & Entreprises</h1>
        <span class="text-muted fs-7">Registre des commerces, établissements artisanaux et contribuables municipaux</span>
    </div>
    <div>
        @can('can', 'OPERATEUR_CREATE')
            <a href="{{ route('operateur.create') }}" class="btn btn-primary d-flex align-items-center">
                {!! $theme->getSvgIcon('duotune/arrows/arr075.svg', 'svg-icon-2 text-white me-2') !!}
                Saisir un opérateur
            </a>
        @endcan
    </div>
</div>
<!--end::Header Layout-->

<!--begin::Card-->
<div class="card card-flush shadow-sm">
    <!--begin::Card Header-->
    <div class="card-header align-items-center py-5 gap-2 gap-md-5">
        <form method="GET" action="{{ route('operateur.index') }}" class="d-flex flex-wrap align-items-center gap-3 w-100 justify-content-between">
            <div class="d-flex align-items-center position-relative my-1">
                <span class="svg-icon svg-icon-1 position-absolute ms-4">
                    {!! $theme->getSvgIcon('duotune/general/gen021.svg', 'svg-icon-2') !!}
                </span>
                <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-solid w-250px ps-14" placeholder="Raison sociale, NIF..." />
            </div>

            <div class="d-flex flex-wrap align-items-center gap-3">
                <select name="statut" class="form-select form-select-solid w-180px" onchange="this.form.submit()">
                    <option value="">Tous les statuts</option>
                    @foreach(\App\Enums\OperateurStatut::cases() as $status)
                        <option value="{{ $status->value }}" {{ request('statut') == $status->value ? 'selected' : '' }}>{{ $status->label() }}</option>
                    @endforeach
                </select>

                @if(request()->anyFilled(['q', 'statut']))
                    <a href="{{ route('operateur.index') }}" class="btn btn-sm btn-light-danger me-2" title="Réinitialiser">
                        <i class="fas fa-undo me-1"></i>Effacer
                    </a>
                @endif
            </div>
        </form>
    </div>
    <!--end::Card Header-->

    <!--begin::Card Body-->
    <div class="card-body pt-0">
        <!--begin::Table-->
        <div class="table-responsive">
            <table class="table align-middle table-row-dashed fs-6 gy-5">
                <thead>
                    <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                        <th class="min-w-150px">Opérateur / Raison sociale</th>
                        <th class="min-w-120px">Promoteur</th>
                        <th class="min-w-100px text-center">Effectif total</th>
                        <th class="min-w-120px">RCCM / NIF</th>
                        <th class="min-w-100px text-center">Statut</th>
                        <th class="text-end min-w-100px">Actions</th>
                    </tr>
                </thead>
                <tbody class="fw-semibold text-gray-600">
                    @forelse($operateurs as $op)
                        <tr>
                            <td>
                                <div class="d-flex flex-column">
                                    <a href="{{ route('operateur.show', $op) }}" class="text-gray-800 text-hover-primary fw-bold fs-6">{{ $op->nom_entreprise ?? $op->nom_commercial }}</a>
                                    <span class="text-muted fs-7">{{ $op->adresse }}</span>
                                </div>
                            </td>
                            <td>
                                <span class="text-gray-800 fw-bold d-block fs-6">{{ $op->promoteur_prenom }} {{ $op->promoteur_nom }}</span>
                                <span class="text-muted fs-7">{{ $op->telephone }}</span>
                            </td>
                            <td class="text-center">
                                <span class="text-gray-800 fw-bold fs-6">{{ $op->effectif_total }} salariés</span>
                                <span class="text-muted fs-7">P: {{ $op->effectif_permanents }} / T: {{ $op->effectif_temporaires }}</span>
                            </td>
                            <td>
                                <div class="d-flex flex-column">
                                    <span class="text-gray-800 fw-bold">RCCM : {{ $op->rccm ?? '-' }}</span>
                                    <span class="text-muted fs-7">NIF : {{ $op->nif ?? '-' }}</span>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="badge {{ $op->statut->badgeClass() }} fs-7 fw-bold">{{ $op->statut->label() }}</span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('operateur.show', $op) }}" class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm me-1" title="Voir les détails">
                                    {!! $theme->getSvgIcon('duotune/general/gen019.svg', 'svg-icon-3') !!}
                                </a>
                                @can('can', 'OPERATEUR_EDIT')
                                    <a href="{{ route('operateur.edit', $op) }}" class="btn btn-icon btn-bg-light btn-active-color-warning btn-sm me-1" title="Modifier">
                                        {!! $theme->getSvgIcon('duotune/art/art005.svg', 'svg-icon-3') !!}
                                    </a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                Aucun opérateur économique enregistré correspondant aux critères.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <!--end::Table-->

        <!--begin::Pagination-->
        <div class="d-flex justify-content-end mt-5">
            {{ $operateurs->links() }}
        </div>
        <!--end::Pagination-->
    </div>
    <!--end::Card Body-->
</div>
<!--end::Card-->
@endsection
