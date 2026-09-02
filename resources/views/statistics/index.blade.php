@extends('layouts.app')

@section('title', 'Statistiques Territoriales Hiérarchiques')
@section('page_title', 'Statistiques Territoriales Hiérarchiques')
@section('page_subtitle', 'Consolidation dynamique : Global → Quartiers → Carrés → Fiches de collecte')

@section('actions')
    <span class="badge bg-primary text-white fs-7 fw-bold px-4 py-3 shadow-sm">
        <i class="bi bi-shield-lock-fill text-white me-2"></i>
        Périmètre : {{ $globalStats['scope'] === 'global' ? 'Municipal Global' : 'Agent Affecté' }}
    </span>
@endsection

@section('content')

<!--begin::Row - KPI Cards (Niveau 1)-->
<div class="row g-5 g-xl-10 mb-5 mb-xl-10">
    <!-- Total Population -->
    <div class="col-md-4 col-xl-2">
        <div class="card bg-body border-0 shadow-sm h-md-100">
            <div class="card-body d-flex flex-column justify-content-between">
                <span class="fs-7 fw-semibold text-gray-500 text-uppercase">Population</span>
                <span class="fs-2hx fw-bolder text-gray-900 my-2">{{ number_format($globalStats['total_population']) }}</span>
                <span class="text-muted fs-8">Personnes recensées</span>
            </div>
        </div>
    </div>

    <!-- Total Ménages -->
    <div class="col-md-4 col-xl-2">
        <div class="card bg-body border-0 shadow-sm h-md-100" style="border-left: 4px solid #F2C200 !important;">
            <div class="card-body d-flex flex-column justify-content-between">
                <span class="fs-7 fw-semibold text-gray-500 text-uppercase">Ménages</span>
                <span class="fs-2hx fw-bolder text-warning my-2">{{ number_format($globalStats['total_menages']) }}</span>
                <span class="text-muted fs-8">Fiches Familles</span>
            </div>
        </div>
    </div>

    <!-- Total Habitats -->
    <div class="col-md-4 col-xl-2">
        <div class="card bg-body border-0 shadow-sm h-md-100" style="border-left: 4px solid #0033A0 !important;">
            <div class="card-body d-flex flex-column justify-content-between">
                <span class="fs-7 fw-semibold text-gray-500 text-uppercase">Habitats</span>
                <span class="fs-2hx fw-bolder text-primary my-2">{{ number_format($globalStats['total_habitats']) }}</span>
                <span class="text-muted fs-8">Parcelles & Maisons</span>
            </div>
        </div>
    </div>

    <!-- Total Opérateurs -->
    <div class="col-md-4 col-xl-2">
        <div class="card bg-body border-0 shadow-sm h-md-100" style="border-left: 4px solid #D64545 !important;">
            <div class="card-body d-flex flex-column justify-content-between">
                <span class="fs-7 fw-semibold text-gray-500 text-uppercase">Opérateurs</span>
                <span class="fs-2hx fw-bolder text-danger my-2">{{ number_format($globalStats['total_operateurs']) }}</span>
                <span class="text-muted fs-8">Commerces & Entr.</span>
            </div>
        </div>
    </div>

    <!-- Recettes Encaissées -->
    <div class="col-md-4 col-xl-2">
        <div class="card bg-body border-0 shadow-sm h-md-100" style="border-left: 4px solid #166534 !important;">
            <div class="card-body d-flex flex-column justify-content-between">
                <span class="fs-7 fw-semibold text-gray-500 text-uppercase">Recettes Taxes</span>
                <span class="fs-2hx fw-bolder text-success my-2">{{ number_format($globalStats['montant_encaisse']) }} F</span>
                <span class="text-muted fs-8">{{ number_format($globalStats['total_paiements']) }} paiements</span>
            </div>
        </div>
    </div>

    <!-- Global Progression -->
    <div class="col-md-4 col-xl-2">
        <div class="card bg-body border-0 shadow-sm h-md-100">
            <div class="card-body d-flex flex-column justify-content-between">
                <span class="fs-7 fw-semibold text-gray-500 text-uppercase">Progression</span>
                <span class="fs-2hx fw-bolder text-info my-2">{{ $globalStats['progression'] }}%</span>
                <div class="progress h-6px w-100 bg-light-info">
                    <div class="progress-bar bg-info" role="progressbar" style="width: {{ $globalStats['progression'] }}%"></div>
                </div>
            </div>
        </div>
    </div>
</div>
<!--end::Row - KPI Cards-->

<!--begin::Row - Quartiers Breakdown (Niveau 2)-->
<div class="row g-5 g-xl-10 mb-5 mb-xl-10">
    <div class="col-12">
        <div class="card card-flush shadow-sm">
            <div class="card-header border-0 pt-5">
                <h3 class="card-title align-items-start flex-column">
                    <span class="card-label fw-bold text-gray-900 fs-3">Consolidation par Quartier (Niveau 2)</span>
                    <span class="text-muted mt-1 fw-semibold fs-7">Agrégations territoriales calculées côté serveur SQL</span>
                </h3>
            </div>
            <div class="card-body py-3">
                <div class="table-responsive">
                    <table class="table table-hover align-middle gs-0 gy-4">
                        <thead>
                            <tr class="fw-bold text-muted bg-light">
                                <th class="ps-4 min-w-150px rounded-start">Quartier</th>
                                <th class="min-w-90px text-center">Ménages</th>
                                <th class="min-w-90px text-center">Habitats</th>
                                <th class="min-w-90px text-center">Opérateurs</th>
                                <th class="min-w-100px text-center">Total Fiches</th>
                                <th class="min-w-120px text-center">Recouvrement</th>
                                <th class="min-w-120px text-center">Progression</th>
                                <th class="min-w-100px text-end pe-4 rounded-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($quartierStats['items'] as $q)
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center">
                                            <div class="symbol symbol-40px symbol-circle bg-light-primary me-3">
                                                <span class="symbol-label fw-bolder text-primary">{{ substr($q['nom'], 0, 2) }}</span>
                                            </div>
                                            <div class="d-flex flex-column">
                                                <span class="text-gray-900 fw-bold fs-6">{{ $q['nom'] }}</span>
                                                <span class="text-muted fs-7">Code : {{ $q['code'] ?: 'N/A' }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light-warning text-warning-shade fs-7 fw-bold px-3 py-2" style="color: #B45309; background: #FEF3C7;">
                                            {{ number_format($q['menages']) }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light-primary text-primary fs-7 fw-bold px-3 py-2">
                                            {{ number_format($q['habitats']) }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light-danger text-danger fs-7 fw-bold px-3 py-2">
                                            {{ number_format($q['operateurs']) }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="text-gray-900 fw-bolder fs-6">{{ number_format($q['fiches_collectees']) }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="text-success fw-bold d-block fs-6">{{ number_format($q['montantEncaisse']) }} F</span>
                                        <span class="text-muted fs-8">{{ $q['paiements'] }} paiements</span>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex align-items-center justify-content-center flex-column">
                                            <span class="text-gray-900 fw-bold fs-7 mb-1">{{ $q['progression'] }}%</span>
                                            <div class="progress h-6px w-80px bg-light-primary">
                                                <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $q['progression'] }}%"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-end pe-4">
                                        <button type="button" class="btn btn-sm btn-light-primary" onclick="loadCarres({{ $q['id'] }}, '{{ addslashes($q['nom']) }}')">
                                            <i class="bi bi-diagram-3 me-1"></i> Voir Carrés (Niveau 3)
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-5">Aucun quartier disponible dans votre périmètre d'accès.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<!--end::Row - Quartiers Breakdown-->

<!--begin::Modal - Carrés (Niveau 3)-->
<div class="modal fade" id="carresModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title text-white" id="carresModalTitle">Carrés du Quartier</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-6">
                <div id="carresLoading" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                    <p class="text-muted mt-2">Chargement des données du carré...</p>
                </div>
                <div id="carresContent" class="d-none">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle gs-0 gy-4">
                            <thead>
                                <tr class="fw-bold text-muted bg-light">
                                    <th class="ps-4">Nom Carré</th>
                                    <th class="text-center">Ménages</th>
                                    <th class="text-center">Habitats</th>
                                    <th class="text-center">Opérateurs</th>
                                    <th class="text-center">Total Fiches</th>
                                    <th class="text-center">Montant Encaissé</th>
                                    <th class="text-center">Progression</th>
                                </tr>
                            </thead>
                            <tbody id="carresTableBody">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>
<!--end::Modal - Carrés-->

@endsection

@push('scripts')
<script>
    function loadCarres(quartierId, quartierNom) {
        document.getElementById('carresModalTitle').innerText = 'Statistiques Carrés (Niveau 3) — Quartier ' + quartierNom;
        document.getElementById('carresLoading').classList.remove('d-none');
        document.getElementById('carresContent').classList.add('d-none');

        var modal = new bootstrap.Modal(document.getElementById('carresModal'));
        modal.show();

        fetch('/statistics/quartiers/' + quartierId + '/carres', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(res => {
            document.getElementById('carresLoading').classList.add('d-none');
            document.getElementById('carresContent').classList.remove('d-none');
            
            var tbody = document.getElementById('carresTableBody');
            tbody.innerHTML = '';

            if (res.success && res.data.items.length > 0) {
                res.data.items.forEach(function(c) {
                    var tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td class="ps-4 fw-bold text-gray-900">${c.nom} ${c.code ? '<span class="text-muted fs-8">(' + c.code + ')</span>' : ''}</td>
                        <td class="text-center"><span class="badge bg-light-warning text-dark">${c.menages}</span></td>
                        <td class="text-center"><span class="badge bg-light-primary text-primary">${c.habitats}</span></td>
                        <td class="text-center"><span class="badge bg-light-danger text-danger">${c.operateurs}</span></td>
                        <td class="text-center fw-bold">${c.fiches_collectees}</td>
                        <td class="text-center text-success fw-bold">${new Intl.NumberFormat().format(c.montantEncaisse)} FCFA</td>
                        <td class="text-center">
                            <span class="fw-bold">${c.progression}%</span>
                            <div class="progress h-6px bg-light-primary mt-1">
                                <div class="progress-bar bg-primary" style="width: ${c.progression}%"></div>
                            </div>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4">Aucun carré répertorié dans ce quartier.</td></tr>';
            }
        })
        .catch(err => {
            document.getElementById('carresLoading').classList.add('d-none');
            alert('Erreur lors du chargement des carrés');
        });
    }
</script>
@endpush
