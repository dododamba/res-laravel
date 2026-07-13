@extends('layouts.app')

@section('title', 'Fiches des Habitations')

@section('content')
<!--begin::Card-->
<div class="card card-flush shadow-sm">
    <!--begin::Card Header-->
    <div class="card-header align-items-center py-5 gap-2 gap-md-5">
        <!--begin::Card Title (Recherche)-->
        <div class="card-title">
            <form method="GET" action="{{ route('maison.index') }}" class="d-flex align-items-center position-relative my-1">
                <span class="svg-icon svg-icon-1 position-absolute ms-4">
                    {!! $theme->getSvgIcon('duotune/general/gen021.svg', 'svg-icon-2') !!}
                </span>
                <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-solid w-250px ps-14" placeholder="Rechercher une adresse..." />
            </form>
        </div>
        <!--end::Card Title-->

        <!--begin::Card Toolbar (Filtres et Création)-->
        <div class="card-toolbar d-flex flex-stack gap-3">
            <!--Filtres-->
            <form method="GET" action="{{ route('maison.index') }}" class="d-flex align-items-center gap-2">
                <select name="carre_id" class="form-select form-select-solid w-150px" onchange="this.form.submit()">
                    <option value="">Tous les carrés</option>
                    @foreach($carres as $c)
                        <option value="{{ $c->id }}" {{ request('carre_id') == $c->id ? 'selected' : '' }}>{{ $c->nom }}</option>
                    @endforeach
                </select>

                <select name="statut" class="form-select form-select-solid w-150px" onchange="this.form.submit()">
                    <option value="">Tous les statuts</option>
                    @foreach(\App\Enums\MaisonStatut::cases() as $status)
                        <option value="{{ $status->value }}" {{ request('statut') == $status->value ? 'selected' : '' }}>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </form>

            @can('create', App\Models\Maison::class)
                <!--Bouton Nouveau-->
                <a href="{{ route('maison.create') }}" class="btn btn-primary d-flex align-items-center">
                    {!! $theme->getSvgIcon('duotune/arrows/arr075.svg', 'svg-icon-2 text-white me-2') !!}
                    Saisir une habitation
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
            <table class="table align-middle table-row-dashed fs-6 gy-5">
                <thead>
                    <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                        <th class="min-w-150px">Adresse</th>
                        <th class="min-w-100px text-center">N° Porte</th>
                        <th class="min-w-120px">Carré</th>
                        <th class="min-w-120px">Enquêteur</th>
                        <th class="min-w-100px text-center">Statut</th>
                        <th class="text-end min-w-100px">Actions</th>
                    </tr>
                </thead>
                <tbody class="fw-semibold text-gray-600">
                    @forelse($maisons as $maison)
                        <tr>
                            <td>
                                <div class="d-flex flex-column">
                                    <a href="{{ route('maison.show', $maison) }}" class="text-gray-800 text-hover-primary fw-bold fs-6">{{ $maison->adresse }}</a>
                                    <span class="text-muted fs-7">Cadastre : {{ $maison->reference_cadastrale ?? 'Non renseigné' }}</span>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="text-gray-800 fw-bold fs-6">Porte {{ $maison->numero_porte }}</span>
                            </td>
                            <td>
                                <span class="text-gray-800 fw-bold d-block fs-6">{{ $maison->carre?->nom ?? '-' }}</span>
                            </td>
                            <td>
                                <div class="d-flex flex-column">
                                    <span class="text-gray-800 fw-bold">{{ $maison->enqueteur?->personne->prenom }} {{ $maison->enqueteur?->personne->nom }}</span>
                                    <span class="text-muted fs-7">Matricule : {{ $maison->enqueteur?->matricule ?? 'S/D' }}</span>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="badge {{ $maison->statut->badgeClass() }} fs-7 fw-bold">{{ $maison->statut->label() }}</span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('maison.show', $maison) }}" class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm me-1" title="Voir les détails">
                                    {!! $theme->getSvgIcon('duotune/general/gen019.svg', 'svg-icon-3') !!}
                                </a>
                                @can('update', $maison)
                                    <a href="{{ route('maison.edit', $maison) }}" class="btn btn-icon btn-bg-light btn-active-color-warning btn-sm me-1" title="Modifier">
                                        {!! $theme->getSvgIcon('duotune/art/art005.svg', 'svg-icon-3') !!}
                                    </a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                Aucune habitation enregistrée correspondant aux critères.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <!--end::Table-->

        <!--begin::Pagination-->
        <div class="d-flex justify-content-end mt-5">
            {{ $maisons->links() }}
        </div>
        <!--end::Pagination-->
    </div>
    <!--end::Card Body-->
</div>
<!--end::Card-->
@endsection
