@extends('layouts.app')

@section('title', 'Tableau de Bord Décisionnel Fiscal')

@section('content')
<!-- Filter Year Header -->
<div class="d-flex flex-stack mb-5">
    <h1 class="fs-2x fw-bolder text-gray-900">Tableau de Bord Décisionnel - Fiscalité Municipale {{ $annee }}</h1>
    <form method="GET" action="{{ route('fiscalite.dashboard') }}" class="d-flex align-items-center gap-2">
        <select name="annee" class="form-select form-select-solid w-150px" onchange="this.form.submit()">
            @foreach([2026, 2025, 2024] as $yr)
                <option value="{{ $yr }}" {{ $annee == $yr ? 'selected' : '' }}>Année {{ $yr }}</option>
            @endforeach
        </select>
    </form>
</div>

<!-- Row 1: KPI Cards -->
<div class="row g-5 g-xl-10 mb-5 mb-xl-10">
    <!-- Montant Attendu -->
    <div class="col-md-3">
        <div class="card card-flush bg-body border-0 shadow-sm h-md-100">
            <div class="card-body d-flex flex-column justify-content-between">
                <div class="d-flex flex-stack justify-content-between mb-3">
                    <span class="fs-6 fw-semibold text-gray-500">Montant Attendu</span>
                    <div class="symbol symbol-40px symbol-circle bg-light-primary">
                        <span class="symbol-label">{!! $theme->getSvgIcon('duotune/finance/fin008.svg', 'svg-icon-2x text-primary') !!}</span>
                    </div>
                </div>
                <span class="fs-2hx fw-bold text-gray-900 lh-1 ls-n2">{{ number_format($montantAttendu) }} FCFA</span>
                <span class="text-muted fs-7 mt-2">Potentiel fiscal estimé</span>
            </div>
        </div>
    </div>

    <!-- Montant Encaissé -->
    <div class="col-md-3">
        <div class="card card-flush bg-success text-white h-md-100 shadow-sm">
            <div class="card-body d-flex flex-column justify-content-between">
                <div class="d-flex flex-stack justify-content-between mb-3">
                    <span class="fs-6 fw-semibold opacity-75 text-white">Montant Encaissé</span>
                    <div class="symbol symbol-40px symbol-circle bg-white bg-opacity-20">
                        <span class="symbol-label">{!! $theme->getSvgIcon('duotune/finance/fin002.svg', 'svg-icon-2x text-white') !!}</span>
                    </div>
                </div>
                <span class="fs-2hx fw-bold text-white lh-1 ls-n2">{{ number_format($montantEncaisse) }} FCFA</span>
                <span class="text-white opacity-75 fs-7 mt-2">Taux de recouvrement : <strong>{{ $tauxRecouvrement }}%</strong></span>
            </div>
        </div>
    </div>

    <!-- Montant Restant à Recouvrer -->
    <div class="col-md-3">
        <div class="card card-flush bg-body border-0 shadow-sm h-md-100">
            <div class="card-body d-flex flex-column justify-content-between">
                <div class="d-flex flex-stack justify-content-between mb-3">
                    <span class="fs-6 fw-semibold text-gray-500">Reste à Payer</span>
                    <div class="symbol symbol-40px symbol-circle bg-light-danger">
                        <span class="symbol-label">{!! $theme->getSvgIcon('duotune/general/gen044.svg', 'svg-icon-2x text-danger') !!}</span>
                    </div>
                </div>
                <span class="fs-2hx fw-bold text-danger lh-1 ls-n2">{{ number_format($montantRestant) }} FCFA</span>
                <span class="text-muted fs-7 mt-2">{{ $taxesImpayees }} taxes restant à régulariser</span>
            </div>
        </div>
    </div>

    <!-- Opérateurs à Jour vs En Retard -->
    <div class="col-md-3">
        <div class="card card-flush bg-body border-0 shadow-sm h-md-100">
            <div class="card-body d-flex flex-column justify-content-between">
                <div class="d-flex flex-stack justify-content-between mb-3">
                    <span class="fs-6 fw-semibold text-gray-500">Civisme Fiscal</span>
                    <div class="symbol symbol-40px symbol-circle bg-light-warning">
                        <span class="symbol-label">{!! $theme->getSvgIcon('duotune/communication/com013.svg', 'svg-icon-2x text-warning') !!}</span>
                    </div>
                </div>
                <div class="d-flex flex-column">
                    <span class="fs-2hx fw-bold text-gray-900 lh-1 ls-n2">{{ $operateursAJour }} / {{ $totalOperateurs }}</span>
                    <span class="text-danger fw-bold fs-7 mt-1">{{ $operateursEnRetard }} opérateur(s) en retard de paiement</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Row 2: Graphiques ApexCharts -->
<div class="row g-5 g-xl-10 mb-5 mb-xl-10">
    <!-- Évolution mensuelle -->
    <div class="col-xl-8">
        <div class="card card-flush shadow-sm h-md-100">
            <div class="card-header pt-5">
                <h3 class="card-title align-items-start flex-column">
                    <span class="card-label fw-bold text-gray-900 fs-3">Évolution Mensuelle des Recettes</span>
                    <span class="text-muted mt-1 fw-semibold fs-7">Encaissements réalisés mois par mois sur l'année {{ $annee }}</span>
                </h3>
            </div>
            <div class="card-body pt-0">
                <div id="kt_apexcharts_monthly" style="height: 350px;"></div>
            </div>
        </div>
    </div>

    <!-- Répartition par Catégorie -->
    <div class="col-xl-4">
        <div class="card card-flush shadow-sm h-md-100">
            <div class="card-header pt-5">
                <h3 class="card-title align-items-start flex-column">
                    <span class="card-label fw-bold text-gray-900 fs-3">Répartition par Catégorie</span>
                    <span class="text-muted mt-1 fw-semibold fs-7">Structure des recettes par type de taxe</span>
                </h3>
            </div>
            <div class="card-body pt-0 d-flex align-items-center justify-content-center">
                <div id="kt_apexcharts_categories" style="height: 320px; width: 100%;"></div>
            </div>
        </div>
    </div>
</div>

<!-- Row 3: Tops et Classements -->
<div class="row g-5 g-xl-10 mb-5">
    <!-- Top Taxes -->
    <div class="col-xl-4">
        <div class="card card-flush shadow-sm h-md-100">
            <div class="card-header border-0 pt-5">
                <h3 class="card-title fw-bold text-gray-900 fs-4">Top 5 Taxes les Plus Rentables</h3>
            </div>
            <div class="card-body py-3">
                <div class="table-responsive">
                    <table class="table align-middle gs-0 gy-3">
                        <thead>
                            <tr class="fw-bold text-muted bg-light">
                                <th class="ps-3 rounded-start">Code</th>
                                <th>Taxe</th>
                                <th class="text-end pe-3 rounded-end">Encaissement</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($topTaxes as $tx)
                                <tr>
                                    <td class="ps-3"><span class="badge badge-light-primary">{{ $tx->code }}</span></td>
                                    <td class="fw-bold text-gray-800">{{ Str::limit($tx->nom, 20) }}</td>
                                    <td class="text-end pe-3 fw-bolder text-success">{{ number_format($tx->total_encaisse) }} FCFA</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Quartiers -->
    <div class="col-xl-4">
        <div class="card card-flush shadow-sm h-md-100">
            <div class="card-header border-0 pt-5">
                <h3 class="card-title fw-bold text-gray-900 fs-4">Top Quartiers Recouvrement</h3>
            </div>
            <div class="card-body py-3">
                <div class="table-responsive">
                    <table class="table align-middle gs-0 gy-3">
                        <thead>
                            <tr class="fw-bold text-muted bg-light">
                                <th class="ps-3 rounded-start">Quartier</th>
                                <th class="text-end pe-3 rounded-end">Encaissement</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($topQuartiers as $tq)
                                <tr>
                                    <td class="ps-3 fw-bold text-gray-800">{{ $tq->nom }}</td>
                                    <td class="text-end pe-3 fw-bolder text-primary">{{ number_format($tq->total_encaisse) }} FCFA</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Agents Encaisseurs -->
    <div class="col-xl-4">
        <div class="card card-flush shadow-sm h-md-100">
            <div class="card-header border-0 pt-5">
                <h3 class="card-title fw-bold text-gray-900 fs-4">Top Agents Recouvreurs</h3>
            </div>
            <div class="card-body py-3">
                <div class="table-responsive">
                    <table class="table align-middle gs-0 gy-3">
                        <thead>
                            <tr class="fw-bold text-muted bg-light">
                                <th class="ps-3 rounded-start">Agent</th>
                                <th class="text-end pe-3 rounded-end">Encaissements</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($topAgents as $tag)
                                <tr>
                                    <td class="ps-3 fw-bold text-gray-800">{{ $tag->prenom }} {{ $tag->nom }}</td>
                                    <td class="text-end pe-3 fw-bolder text-success">{{ number_format($tag->total_encaisse) }} FCFA</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Chart 1: Évolution Mensuelle
        var optionsMonthly = {
            series: [{
                name: 'Recettes Encaissées (FCFA)',
                data: @json($monthlyEvolution)
            }],
            chart: {
                type: 'area',
                height: 350,
                toolbar: { show: false }
            },
            colors: ['#50cd89'],
            dataLabels: { enabled: false },
            stroke: { curve: 'smooth', width: 3 },
            xaxis: {
                categories: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sept', 'Oct', 'Nov', 'Déc']
            },
            yaxis: {
                labels: {
                    formatter: function(val) {
                        return new Intl.NumberFormat('fr-FR').format(val) + ' F';
                    }
                }
            },
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.7,
                    opacityTo: 0.2
                }
            }
        };
        var chartMonthly = new ApexCharts(document.querySelector("#kt_apexcharts_monthly"), optionsMonthly);
        chartMonthly.render();

        // Chart 2: Categories Donut
        var catLabels = @json($taxesByCategorie->pluck('categorie'));
        var catSeries = @json($taxesByCategorie->pluck('total_paye'));

        var optionsCategories = {
            series: catSeries.length > 0 ? catSeries : [1],
            labels: catLabels.length > 0 ? catLabels : ['Aucune donnée'],
            chart: {
                type: 'donut',
                height: 320
            },
            colors: ['#009ef7', '#50cd89', '#ffc700', '#f1416c', '#7239ea'],
            legend: { position: 'bottom' }
        };
        var chartCategories = new ApexCharts(document.querySelector("#kt_apexcharts_categories"), optionsCategories);
        chartCategories.render();
    });
</script>
@endpush
@endsection
