@extends('layouts.app')

@section('title', 'Recensement des Ménages')

@section('content')
<!--begin::Card-->
<div class="card card-flush shadow-sm">
    <!--begin::Card Header-->
    <div class="card-header align-items-center py-5 gap-2 gap-md-5">
        <!--begin::Card Title (Recherche)-->
        <div class="card-title">
            <form method="GET" action="{{ route('recensement.index') }}" class="d-flex align-items-center position-relative my-1">
                <span class="svg-icon svg-icon-1 position-absolute ms-4">
                    {!! $theme->getSvgIcon('duotune/general/gen021.svg', 'svg-icon-2') !!}
                </span>
                <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-solid w-250px ps-14" placeholder="Rechercher un chef..." />
                @if(request('quartier_id'))
                    <input type="hidden" name="quartier_id" value="{{ request('quartier_id') }}" />
                @endif
                @if(request('statut'))
                    <input type="hidden" name="statut" value="{{ request('statut') }}" />
                @endif
            </form>
        </div>
        <!--end::Card Title-->

        <!--begin::Card Toolbar (Filtres et Création)-->
        <div class="card-toolbar d-flex flex-stack gap-3">
            <!--Filtres-->
            <form method="GET" action="{{ route('recensement.index') }}" class="d-flex align-items-center gap-2">
                @if(request('q'))
                    <input type="hidden" name="q" value="{{ request('q') }}" />
                @endif
                
                <select name="quartier_id" class="form-select form-select-solid w-150px" onchange="this.form.submit()">
                    <option value="">Tous les quartiers</option>
                    @foreach($quartiers as $q)
                        <option value="{{ $q->id }}" {{ request('quartier_id') == $q->id ? 'selected' : '' }}>{{ $q->nom }}</option>
                    @endforeach
                </select>

                <select name="statut" class="form-select form-select-solid w-150px" onchange="this.form.submit()">
                    <option value="">Tous les statuts</option>
                    @foreach(\App\Enums\RecensementStatut::cases() as $status)
                        <option value="{{ $status->value }}" {{ request('statut') == $status->value ? 'selected' : '' }}>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </form>

            @can('can', 'RECENSEMENT_CREATE')
                <!--Bouton Nouveau-->
                <a href="{{ route('recensement.create') }}" class="btn btn-primary d-flex align-items-center">
                    {!! $theme->getSvgIcon('duotune/arrows/arr075.svg', 'svg-icon-2 text-white me-2') !!}
                    Nouveau ménage
                </a>
            @endcan
        </div>
        <!--end::Card Toolbar-->
    </div>
    <!--end::Card Header-->

    <!--begin::Card Body-->
    <div class="card-body pt-0">
        <!--begin::Table-->
        <div class="table-responsive">
            <table class="table align-middle table-row-dashed fs-6 gy-5" id="kt_recensements_table">
                <thead>
                    <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                        <th class="min-w-150px">Chef de ménage</th>
                        <th class="min-w-100px text-center">Membres</th>
                        <th class="min-w-150px">Découpage (Carré/Quartier)</th>
                        <th class="min-w-120px">Enquêteur</th>
                        <th class="min-w-100px text-center">Statut</th>
                        <th class="text-end min-w-100px">Actions</th>
                    </tr>
                </thead>
                <tbody class="fw-semibold text-gray-600">
                    @forelse($recensements as $rec)
                        <tr>
                            <td>
                                <div class="d-flex flex-column">
                                    <a href="{{ route('recensement.show', $rec) }}" class="text-gray-800 text-hover-primary fw-bold fs-6">{{ $rec->chef_prenom }} {{ $rec->chef_nom }}</a>
                                    <span class="text-muted fs-7">{{ $rec->chef_telephone }}</span>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="text-gray-800 fw-bold d-block fs-6">{{ $rec->nombre_personnes }}</span>
                                <span class="text-muted fs-7">H: {{ $rec->nombre_hommes }} / F: {{ $rec->nombre_femmes }}</span>
                            </td>
                            <td>
                                <div class="d-flex flex-column">
                                    <span class="text-gray-800 fw-bold">{{ $rec->carre?->nom ?? '-' }}</span>
                                    <span class="text-muted fs-7">{{ $rec->quartier?->nom ?? '-' }}</span>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex flex-column">
                                    <span class="text-gray-800 fw-bold">{{ $rec->enqueteur?->personne->prenom }} {{ $rec->enqueteur?->personne->nom }}</span>
                                    <span class="text-muted fs-7">Matricule : {{ $rec->enqueteur?->matricule ?? 'S/D' }}</span>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="badge {{ $rec->statut->badgeClass() }} fs-7 fw-bold">{{ $rec->statut->label() }}</span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('recensement.show', $rec) }}" class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm me-1" title="Voir les détails">
                                    {!! $theme->getSvgIcon('duotune/general/gen019.svg', 'svg-icon-3') !!}
                                </a>
                                @can('can', 'RECENSEMENT_EDIT')
                                    <a href="{{ route('recensement.edit', $rec) }}" class="btn btn-icon btn-bg-light btn-active-color-warning btn-sm me-1" title="Modifier">
                                        {!! $theme->getSvgIcon('duotune/art/art005.svg', 'svg-icon-3') !!}
                                    </a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                Aucun ménage enregistré correspondant aux filtres.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <!--end::Table-->

        <!--begin::Pagination-->
        <div class="d-flex justify-content-end mt-5">
            {{ $recensements->links() }}
        </div>
        <!--end::Pagination-->
    </div>
    <!--end::Card Body-->
</div>
<!--end::Card-->
@endsection
