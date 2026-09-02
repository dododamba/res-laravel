@extends('layouts.app')

@section('title', 'Taxes Municipales')
@section('page_title', 'Nomenclature des Taxes Municipales')
@section('page_subtitle', 'Barème officiel, modes de calcul et arrêtés tarifaires communaux')

@section('actions')
    <a href="{{ route('taxes.create') }}" class="btn btn-sm btn-primary d-flex align-items-center">
        {!! $theme->getSvgIcon('duotune/arrows/arr075.svg', 'svg-icon-2 text-white me-2') !!}
        Nouvelle Taxe Municipale
    </a>
@endsection

@section('content')

<!--begin::Card-->
<div class="card card-flush shadow-sm">
    <!--begin::Card Header-->
    <div class="card-header align-items-center py-5 gap-2 gap-md-5">
        <form method="GET" action="{{ route('taxes.index') }}" class="d-flex flex-wrap align-items-center gap-3 w-100 justify-content-between">
            <div class="d-flex align-items-center position-relative my-1">
                <span class="svg-icon svg-icon-1 position-absolute ms-4">
                    {!! $theme->getSvgIcon('duotune/general/gen021.svg', 'svg-icon-2') !!}
                </span>
                <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-solid w-250px ps-14" placeholder="Rechercher une taxe..." />
            </div>

            <div class="d-flex flex-wrap align-items-center gap-3">
                <select name="categorie" class="form-select form-select-solid w-200px" onchange="this.form.submit()">
                    <option value="">Toutes les catégories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}" {{ request('categorie') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                    @endforeach
                </select>

                @if(request()->anyFilled(['q', 'categorie']))
                    <a href="{{ route('taxes.index') }}" class="btn btn-sm btn-light-danger me-2" title="Réinitialiser">
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
                        <th class="min-w-100px">Code</th>
                        <th class="min-w-200px">Intitulé de la Taxe</th>
                        <th class="min-w-120px">Catégorie</th>
                        <th class="min-w-120px text-end">Montant Base</th>
                        <th class="min-w-120px text-center">Mode Calcul</th>
                        <th class="min-w-100px text-center">Périodicité</th>
                        <th class="min-w-80px text-center">Statut</th>
                        <th class="text-end min-w-100px">Actions</th>
                    </tr>
                </thead>
                <tbody class="fw-semibold text-gray-600">
                    @forelse($taxes as $taxe)
                        <tr>
                            <td>
                                <span class="badge badge-light-dark fs-7 fw-bold">{{ $taxe->code }}</span>
                            </td>
                            <td>
                                <div class="d-flex flex-column">
                                    <span class="text-gray-800 fw-bold fs-6">{{ $taxe->nom }}</span>
                                    <span class="text-muted fs-7">{{ Str::limit($taxe->description, 60) }}</span>
                                </div>
                            </td>
                            <td>
                                <span class="text-gray-700 fw-semibold">{{ $taxe->categorie }}</span>
                            </td>
                            <td class="text-end">
                                <span class="text-gray-900 fw-bold fs-6">{{ number_format($taxe->montant) }} FCFA</span>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-light-info fs-7">{{ $taxe->mode_calcul->label() }}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-light-primary fs-7">{{ $taxe->periodicite->label() }}</span>
                            </td>
                            <td class="text-center">
                                @if($taxe->actif)
                                    <span class="badge badge-light-success">Actif</span>
                                @else
                                    <span class="badge badge-light-danger">Inactif</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <form method="POST" action="{{ route('taxes.toggle', $taxe) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm me-1" title="Activer / Désactiver">
                                        {!! $theme->getSvgIcon('duotune/general/gen019.svg', 'svg-icon-3') !!}
                                    </button>
                                </form>
                                <a href="{{ route('taxes.edit', $taxe) }}" class="btn btn-icon btn-bg-light btn-active-color-warning btn-sm me-1" title="Modifier">
                                    {!! $theme->getSvgIcon('duotune/art/art005.svg', 'svg-icon-3') !!}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                Aucune taxe municipale configurée pour l'instant.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-end mt-5">
            {{ $taxes->links() }}
        </div>
    </div>
</div>
@endsection
