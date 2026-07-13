@extends('layouts.app')

@section('title', 'Tableau de bord global')

@section('content')
<!--begin::Row - Chiffres Clés-->
<div class="row g-5 g-xl-10 mb-5 mb-xl-10">
    <!-- Total Population -->
    <div class="col-md-3 col-xl-3">
        <div class="card card-flush h-md-100 bg-primary text-white">
            <div class="card-header pt-5">
                <div class="card-title d-flex flex-column">
                    <span class="fs-2hx fw-bold text-white me-2 lh-1 ls-n2">{{ number_format($stats['total_population']) }}</span>
                    <span class="text-white opacity-75 pt-1 fw-semibold fs-6">Population Recensée</span>
                </div>
            </div>
            <div class="card-body d-flex align-items-end pt-0">
                <div class="d-flex align-items-center w-100">
                    <span class="text-white-50 fw-bold fs-7">Répartition : {{ $stats['homme_ratio'] }}% Hommes / {{ $stats['femme_ratio'] }}% Femmes</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Ménages -->
    <div class="col-md-3">
        <div class="card bg-body border-0 shadow-sm h-md-100">
            <div class="card-body d-flex flex-column justify-content-between">
                <div class="d-flex flex-stack justify-content-between mb-3">
                    <span class="fs-6 fw-semibold text-gray-500">Ménages Enregistrés</span>
                    <div class="symbol symbol-40px symbol-circle bg-light-success">
                        <span class="symbol-label">
                            {!! $theme->getSvgIcon('duotune/communication/com013.svg', 'svg-icon-2x text-success') !!}
                        </span>
                    </div>
                </div>
                <div class="d-flex flex-column">
                    <span class="fs-2hx fw-bold text-gray-900 lh-1 ls-n2">{{ number_format($stats['total_menages']) }}</span>
                    <span class="text-muted fs-7 mt-1">Saisies étanches par enquêteur</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Habitations -->
    <div class="col-md-3">
        <div class="card bg-body border-0 shadow-sm h-md-100">
            <div class="card-body d-flex flex-column justify-content-between">
                <div class="d-flex flex-stack justify-content-between mb-3">
                    <span class="fs-6 fw-semibold text-gray-500">Habitations Identifiées</span>
                    <div class="symbol symbol-40px symbol-circle bg-light-primary">
                        <span class="symbol-label">
                            {!! $theme->getSvgIcon('duotune/general/gen001.svg', 'svg-icon-2x text-primary') !!}
                        </span>
                    </div>
                </div>
                <div class="d-flex flex-column">
                    <span class="fs-2hx fw-bold text-gray-900 lh-1 ls-n2">{{ number_format($stats['total_habitations']) }}</span>
                    <span class="text-muted fs-7 mt-1">Parcelles d'habitations géo-référencées</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Opérateurs -->
    <div class="col-md-3">
        <div class="card bg-body border-0 shadow-sm h-md-100">
            <div class="card-body d-flex flex-column justify-content-between">
                <div class="d-flex flex-stack justify-content-between mb-3">
                    <span class="fs-6 fw-semibold text-gray-500">Opérateurs Économiques</span>
                    <div class="symbol symbol-40px symbol-circle bg-light-warning">
                        <span class="symbol-label">
                            {!! $theme->getSvgIcon('duotune/finance/fin006.svg', 'svg-icon-2x text-warning') !!}
                        </span>
                    </div>
                </div>
                <div class="d-flex flex-column">
                    <span class="fs-2hx fw-bold text-gray-900 lh-1 ls-n2">{{ number_format($stats['total_entreprises']) }}</span>
                    <span class="text-muted fs-7 mt-1">Commerces & industries d'activité</span>
                </div>
            </div>
        </div>
    </div>
</div>
<!--end::Row-->

<!--begin::Row - Tableaux récents-->
<div class="row g-5 g-xl-10 mb-5">
    <!-- Recensements Récents -->
    <div class="col-xl-6">
        <div class="card card-flush shadow-sm">
            <div class="card-header border-0 pt-5">
                <h3 class="card-title align-items-start flex-column">
                    <span class="card-label fw-bold text-gray-900 fs-4">Derniers Ménages Recensés</span>
                    <span class="text-muted mt-1 fw-semibold fs-7">Saisies de terrain en direct</span>
                </h3>
            </div>
            <div class="card-body py-3">
                <div class="table-responsive">
                    <table class="table align-middle gs-0 gy-4">
                        <thead>
                            <tr class="fw-bold text-muted bg-light">
                                <th class="ps-4 min-w-150px rounded-start">Chef de ménage</th>
                                <th class="min-w-100px">Membres</th>
                                <th class="min-w-120px">Carré</th>
                                <th class="min-w-100px text-end pe-4 rounded-end">Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentRecensements as $rec)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center ps-4">
                                            <div class="d-flex flex-column">
                                                <a href="{{ route('recensement.show', $rec) }}" class="text-gray-900 fw-bold text-hover-primary fs-6">{{ $rec->chef_prenom }} {{ $rec->chef_nom }}</a>
                                                <span class="text-muted fw-semibold fs-7">{{ $rec->chef_telephone }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="text-gray-900 fw-bold fs-6">{{ $rec->nombre_personnes }} personnes</span>
                                        <span class="text-muted fw-semibold d-block fs-7">{{ $rec->nombre_enfants }} enfant(s)</span>
                                    </td>
                                    <td>
                                        <span class="text-gray-900 fw-bold d-block fs-6">{{ $rec->carre?->nom ?? '-' }}</span>
                                        <span class="text-muted fw-semibold fs-7">{{ $rec->quartier?->nom ?? 'Quartier libre' }}</span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <span class="badge {{ $rec->statut->badgeClass() }} fs-7 fw-bold">{{ $rec->statut->label() }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-5">Aucun ménage recensé pour l'instant.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Habitations Récentes -->
    <div class="col-xl-6">
        <div class="card card-flush shadow-sm">
            <div class="card-header border-0 pt-5">
                <h3 class="card-title align-items-start flex-column">
                    <span class="card-label fw-bold text-gray-900 fs-4">Dernières Habitations Relevées</span>
                    <span class="text-muted mt-1 fw-semibold fs-7">Saisies de parcelles cadastrales</span>
                </h3>
            </div>
            <div class="card-body py-3">
                <div class="table-responsive">
                    <table class="table align-middle gs-0 gy-4">
                        <thead>
                            <tr class="fw-bold text-muted bg-light">
                                <th class="ps-4 min-w-150px rounded-start">Adresse</th>
                                <th class="min-w-100px">N° Porte</th>
                                <th class="min-w-120px">Enquêteur</th>
                                <th class="min-w-100px text-end pe-4 rounded-end">Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentMaisons as $maison)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center ps-4">
                                            <div class="d-flex flex-column">
                                                <a href="{{ route('maison.show', $maison) }}" class="text-gray-900 fw-bold text-hover-primary fs-6">{{ $maison->adresse }}</a>
                                                <span class="text-muted fw-semibold fs-7">Cadastre : {{ $maison->reference_cadastrale ?? 'Non renseigné' }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="text-gray-900 fw-bold fs-6">Porte {{ $maison->numero_porte }}</span>
                                        <span class="text-muted fw-semibold d-block fs-7">Bloc : {{ $maison->carre?->nom ?? '-' }}</span>
                                    </td>
                                    <td>
                                        <span class="text-gray-900 fw-bold d-block fs-6">{{ $maison->enqueteur?->personne->prenom }} {{ $maison->enqueteur?->personne->nom }}</span>
                                        <span class="text-muted fw-semibold fs-7">Matricule : {{ $maison->enqueteur?->matricule ?? 'S/D' }}</span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <span class="badge {{ $maison->statut->badgeClass() }} fs-7 fw-bold">{{ $maison->statut->label() }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-5">Aucune habitation relevée pour l'instant.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<!--end::Row-->
@endsection

@push('scripts')
<script>
    console.log("Intégration du thème Metronic avec Blade accomplie avec succès !");
</script>
@endpush
