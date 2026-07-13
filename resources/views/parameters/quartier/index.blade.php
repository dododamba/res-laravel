@extends('layouts.app')

@section('title', 'Gestion des Quartiers')

@section('content')
<!--begin::Header Layout-->
<div class="d-flex align-items-center justify-content-between mb-5">
    <div>
        <h1 class="fw-bold text-gray-900 mb-1">Gestion des Quartiers</h1>
        <span class="text-muted fs-7">Zonage et administration territoriale (Nomenclatures)</span>
    </div>
    <div>
        <a href="{{ route('quartier.create') }}" class="btn btn-primary btn-sm">
            <i class="fas fa-plus me-1"></i>Nouveau Quartier
        </a>
    </div>
</div>
<!--end::Header Layout-->

<!--begin::Statistiques Widgets-->
<div class="row g-5 mb-6">
    <!-- Total Quartiers -->
    <div class="col-md-3">
        <div class="card card-flush bg-light-primary border-0 p-5 shadow-sm">
            <div class="d-flex align-items-center">
                <div class="symbol symbol-45px symbol-circle me-4 bg-primary text-white d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                    <i class="fas fa-map-marked-alt text-white fs-4"></i>
                </div>
                <div>
                    <h3 class="fw-bold text-gray-800 mb-0 fs-2">{{ $stats['total_quartiers'] }}</h3>
                    <span class="text-muted fs-7 fw-semibold">Total Quartiers</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Carrés -->
    <div class="col-md-3">
        <div class="card card-flush bg-light-success border-0 p-5">
            <div class="d-flex align-items-center">
                <div class="symbol symbol-45px symbol-circle me-3 bg-success text-white d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                    <i class="fas fa-th text-white fs-4"></i>
                </div>
                <div>
                    <h3 class="fw-bold text-gray-800 mb-0 fs-2">{{ $stats['total_carres'] }}</h3>
                    <span class="text-muted fs-7 fw-semibold">Total Carrés</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Délégués -->
    <div class="col-md-3">
        <div class="card card-flush bg-light-info border-0 p-5">
            <div class="d-flex align-items-center">
                <div class="symbol symbol-45px symbol-circle me-3 bg-info text-white d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="fas fa-user-shield text-white fs-4"></i>
                </div>
                <div>
                    <h3 class="fw-bold text-gray-800 mb-0 fs-2">{{ $stats['total_delegues'] }}</h3>
                    <span class="text-muted fs-7 fw-semibold">Délégués Actifs</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Chefs de Carrés -->
    <div class="col-md-3">
        <div class="card card-flush bg-light-warning border-0 p-5">
            <div class="d-flex align-items-center">
                <div class="symbol symbol-45px symbol-circle me-3 bg-warning text-white d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="fas fa-users-cog text-white fs-4"></i>
                </div>
                <div>
                    <h3 class="fw-bold text-gray-800 mb-0 fs-2">{{ $stats['total_chefs'] }}</h3>
                    <span class="text-muted fs-7 fw-semibold">Chefs de Carrés</span>
                </div>
            </div>
        </div>
    </div>
</div>
<!--end::Statistiques Widgets-->

<!--begin::Main Card-->
<div class="card card-flush shadow-sm">
    <!--begin::Card Header-->
    <div class="card-header align-items-center py-5 gap-2 gap-md-5">
        <div class="card-title">
            <!--begin::Search-->
            <form method="GET" action="{{ route('quartier.index') }}" class="d-flex align-items-center position-relative my-1">
                <i class="fas fa-search fs-3 position-absolute ms-4 text-gray-500"></i>
                <input type="text" name="q" value="{{ $searchTerm }}" class="form-control form-control-solid w-250px ps-12" placeholder="Rechercher un quartier..." />
                @if($showArchived)
                    <input type="hidden" name="archived" value="true" />
                @endif
            </form>
            <!--end::Search-->
        </div>
        <div class="card-toolbar d-flex gap-3">
            <!-- Archivés Toggle -->
            @if($showArchived)
                <a href="{{ route('quartier.index') }}" class="btn btn-sm btn-light-primary">
                    <i class="fas fa-eye me-1"></i>Voir actifs
                </a>
            @else
                <a href="{{ route('quartier.index', ['archived' => 'true']) }}" class="btn btn-sm btn-light-danger">
                    <i class="fas fa-archive me-1"></i>Voir archivés
                </a>
            @endif
        </div>
    </div>
    <!--end::Card Header-->

    <!--begin::Card Body-->
    <div class="card-body pt-0">
        <div class="table-responsive">
            <table class="table align-middle table-row-dashed fs-6 gy-5">
                <thead>
                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                        <th class="w-10px pe-2">#</th>
                        <th class="min-w-150px">Nom du Quartier</th>
                        <th class="min-w-150px">Délégué de Quartier</th>
                        <th class="min-w-100px text-center">Carrés rattachés</th>
                        <th class="min-w-150px">Progression Collecte</th>
                        <th class="text-end min-w-100px">Actions</th>
                    </tr>
                </thead>
                <tbody class="text-gray-600 fw-semibold">
                    @forelse($entities as $entity)
                        <tr>
                            <td>
                                <span class="text-gray-400 fs-8">{{ loop->iteration }}</span>
                            </td>
                            <td>
                                <div class="d-flex flex-column">
                                    <a href="{{ route('quartier.index') }}/{{ $entity->id }}" class="text-gray-900 text-hover-primary fs-5 fw-bold mb-1">
                                        {{ $entity->nom }}
                                    </a>
                                    <span class="text-muted fs-8">Code : {{ $entity->code ?? 'N/A' }}</span>
                                </div>
                            </td>
                            <td>
                                @if($entity->delegue)
                                    <div class="d-flex align-items-center">
                                        <div class="symbol symbol-35px symbol-circle me-3 bg-light-primary text-primary d-flex align-items-center justify-content-center fw-bold fs-6" style="width:35px; height:35px;">
                                            {{ substr($entity->delegue->personne->prenom, 0, 1) }}{{ substr($entity->delegue->personne->nom, 0, 1) }}
                                        </div>
                                        <div class="d-flex flex-column">
                                            <span class="text-gray-800 fw-bold text-hover-primary fs-6">{{ $entity->delegue->personne->prenom }} {{ $entity->delegue->personne->nom }}</span>
                                            <span class="text-muted fs-8">Matricule : {{ $entity->delegue->matricule }}</span>
                                        </div>
                                    </div>
                                @else
                                    <span class="badge badge-light-warning text-warning fw-bold fs-8 px-3 py-2">Délégué non assigné</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge badge-light fw-bold fs-7 py-2 px-3">{{ $entity->carres->count() }} carrés</span>
                            </td>
                            <td>
                                @php $progress = $progressions[$entity->id] ?? 10; @endphp
                                <div class="d-flex flex-column w-100">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="text-muted fs-8 fw-bold">Dossiers transmis</span>
                                        <span class="text-gray-800 fs-8 fw-bold">{{ $progress }}%</span>
                                    </div>
                                    <div class="progress h-6px w-100 bg-light-success">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: {{ $progress }}%"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('quartier.index') }}/{{ $entity->id }}" class="btn btn-icon btn-light btn-active-color-primary btn-sm" title="Détails">
                                        <i class="fas fa-eye fs-6"></i>
                                    </a>
                                    <a href="{{ route('quartier.index') }}/{{ $entity->id }}/edit" class="btn btn-icon btn-light btn-active-color-primary btn-sm" title="Modifier">
                                        <i class="fas fa-pencil-alt fs-6"></i>
                                    </a>
                                    <button class="btn btn-icon btn-light-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal{{ $entity->id }}" title="Archiver">
                                        <i class="fas fa-trash fs-6"></i>
                                    </button>
                                </div>

                                <!--begin::Delete Confirmation Modal-->
                                <div class="modal fade text-start" id="deleteModal{{ $entity->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Confirmation d'archivage</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body text-center p-5">
                                                <i class="fas fa-exclamation-triangle text-warning fs-3x mb-4"></i>
                                                <h5>Êtes-vous sûr de vouloir archiver le quartier</h5>
                                                <p class="lead fw-bold mb-3">{{ $entity->nom }} ?</p>
                                                <p class="text-muted fs-7">Cette action désactivera le quartier pour la saisie terrain, mais conservera l'historique d'enquêtes.</p>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Annuler</button>
                                                <form method="POST" action="{{ route('quartier.destroy', $entity->id) }}" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger">Confirmer l'archivage</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!--end::Delete Confirmation Modal-->
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-10 text-muted">
                                <i class="fas fa-folder-open fs-2x mb-3 d-block"></i>
                                Aucun quartier trouvé.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <!--end::Card Body-->
</div>
<!--end::Main Card-->
@endsection
