@extends('layouts.app')

@section('title', 'Gestion : ' . ($parameterLabel ?? 'Paramètres'))

@section('content')
<!--begin::Header Layout-->
<div class="d-flex align-items-center justify-content-between mb-5">
    <div>
        <h1 class="fw-bold text-gray-900 mb-1">{{ $parameterLabel ?? 'Paramètres' }}</h1>
        <span class="text-muted fs-7">Gestion des nomenclatures et paramètres de référence</span>
    </div>
    <div>
        <a href="{{ route($routePrefix . '.create') }}" class="btn btn-primary btn-sm">
            <i class="fas fa-plus me-1"></i>Nouveau
        </a>
    </div>
</div>
<!--end::Header Layout-->

<!--begin::Statistiques Widgets-->
<div class="row g-5 mb-6">
    <!-- Total -->
    <div class="col-md-4">
        <div class="card card-flush bg-light-primary border-0 p-5 shadow-sm">
            <div class="d-flex align-items-center">
                <div class="symbol symbol-45px symbol-circle me-4 bg-primary text-white d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                    <i class="fas fa-list-ul text-white fs-4"></i>
                </div>
                <div>
                    <h3 class="fw-bold text-gray-800 mb-0 fs-2">{{ $entities->count() }}</h3>
                    <span class="text-muted fs-7 fw-semibold">Total enregistrements</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Actifs -->
    <div class="col-md-4">
        <div class="card card-flush bg-light-success border-0 p-5 shadow-sm">
            <div class="d-flex align-items-center">
                <div class="symbol symbol-45px symbol-circle me-4 bg-success text-white d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                    <i class="fas fa-check-circle text-white fs-4"></i>
                </div>
                <div>
                    <h3 class="fw-bold text-gray-800 mb-0 fs-2">{{ $entities->where('is_active', true)->count() }}</h3>
                    <span class="text-muted fs-7 fw-semibold">Actifs</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Inactifs -->
    <div class="col-md-4">
        <div class="card card-flush bg-light-danger border-0 p-5 shadow-sm">
            <div class="d-flex align-items-center">
                <div class="symbol symbol-45px symbol-circle me-4 bg-danger text-white d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                    <i class="fas fa-times-circle text-white fs-4"></i>
                </div>
                <div>
                    <h3 class="fw-bold text-gray-800 mb-0 fs-2">{{ $entities->where('is_active', false)->count() }}</h3>
                    <span class="text-muted fs-7 fw-semibold">Inactifs</span>
                </div>
            </div>
        </div>
    </div>
</div>
<!--end::Statistiques Widgets-->

<!--begin::Alerts-->
@if(session('success'))
    <div class="alert alert-success d-flex align-items-center p-5 mb-5">
        <i class="fas fa-check-circle text-success fs-2 me-4"></i>
        <div class="d-flex flex-column">
            <span class="fw-bold">{{ session('success') }}</span>
        </div>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger d-flex align-items-center p-5 mb-5">
        <i class="fas fa-exclamation-triangle text-danger fs-2 me-4"></i>
        <div class="d-flex flex-column">
            <span class="fw-bold">{{ session('error') }}</span>
        </div>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
    </div>
@endif
<!--end::Alerts-->

<!--begin::Main Card-->
<div class="card card-flush shadow-sm">
    <!--begin::Card Header-->
    <div class="card-header align-items-center py-5 gap-2 gap-md-5">
        <div class="card-title">
            <!--begin::Search-->
            <form method="GET" action="{{ route($routePrefix . '.index') }}" class="d-flex align-items-center position-relative my-1">
                <i class="fas fa-search fs-3 position-absolute ms-4 text-gray-500"></i>
                <input type="text" name="q" value="{{ $searchTerm }}" class="form-control form-control-solid w-250px ps-12" placeholder="Rechercher..." />
                @if($showArchived)
                    <input type="hidden" name="archived" value="true" />
                @endif
            </form>
            <!--end::Search-->
        </div>
        <div class="card-toolbar d-flex gap-3">
            <!-- Archivés Toggle -->
            @if($showArchived)
                <a href="{{ route($routePrefix . '.index') }}" class="btn btn-sm btn-light-primary">
                    <i class="fas fa-eye me-1"></i>Voir actifs
                </a>
            @else
                <a href="{{ route($routePrefix . '.index', ['archived' => 'true']) }}" class="btn btn-sm btn-light-danger">
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
                        <th class="min-w-200px">Nom</th>
                        <th class="min-w-100px">Code</th>
                        <th class="min-w-100px text-center">Couleur</th>
                        <th class="min-w-80px text-center">Ordre</th>
                        <th class="min-w-80px text-center">Statut</th>
                        <th class="text-end min-w-120px">Actions</th>
                    </tr>
                </thead>
                <tbody class="text-gray-600 fw-semibold">
                    @forelse($entities as $entity)
                        <tr>
                            <td>
                                <span class="text-gray-400 fs-8">{{ $loop->iteration }}</span>
                            </td>
                            <td>
                                <div class="d-flex flex-column">
                                    <span class="text-gray-900 fs-5 fw-bold mb-1">{{ $entity->nom }}</span>
                                    @if($entity->description)
                                        <span class="text-muted fs-8">{{ Str::limit($entity->description, 60) }}</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                @if($entity->code)
                                    <span class="badge badge-light-info fw-bold fs-7 px-3 py-2">{{ $entity->code }}</span>
                                @else
                                    <span class="text-muted fs-8">—</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($entity->couleur)
                                    <div class="d-flex align-items-center justify-content-center gap-2">
                                        <span class="bullet bullet-dot h-15px w-15px rounded-circle" style="background-color: {{ $entity->couleur }}"></span>
                                        <span class="text-muted fs-8">{{ $entity->couleur }}</span>
                                    </div>
                                @else
                                    <span class="text-muted fs-8">—</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge badge-light fw-bold fs-7">{{ $entity->ordre_affichage ?? 0 }}</span>
                            </td>
                            <td class="text-center">
                                @if($entity->is_active)
                                    <span class="badge badge-light-success fw-bold fs-8 px-3 py-2">Actif</span>
                                @else
                                    <span class="badge badge-light-danger fw-bold fs-8 px-3 py-2">Inactif</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    @if($showArchived)
                                        <!-- Restaurer -->
                                        <form method="POST" action="{{ route($routePrefix . '.restore', $entity->id) }}" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-icon btn-light-success btn-sm" title="Restaurer">
                                                <i class="fas fa-undo fs-6"></i>
                                            </button>
                                        </form>
                                    @else
                                        <!-- Toggle Actif/Inactif -->
                                        <form method="POST" action="{{ route($routePrefix . '.toggle', $entity->id) }}" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-icon btn-light btn-active-color-warning btn-sm" title="{{ $entity->is_active ? 'Désactiver' : 'Activer' }}">
                                                <i class="fas {{ $entity->is_active ? 'fa-toggle-on text-success' : 'fa-toggle-off text-muted' }} fs-6"></i>
                                            </button>
                                        </form>

                                        <!-- Modifier -->
                                        <a href="{{ route($routePrefix . '.edit', $entity->id) }}" class="btn btn-icon btn-light btn-active-color-primary btn-sm" title="Modifier">
                                            <i class="fas fa-pencil-alt fs-6"></i>
                                        </a>

                                        <!-- Dupliquer -->
                                        <form method="POST" action="{{ route($routePrefix . '.duplicate', $entity->id) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-icon btn-light btn-active-color-info btn-sm" title="Dupliquer">
                                                <i class="fas fa-copy fs-6"></i>
                                            </button>
                                        </form>

                                        <!-- Archiver -->
                                        <button class="btn btn-icon btn-light-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal{{ $entity->id }}" title="Archiver">
                                            <i class="fas fa-trash fs-6"></i>
                                        </button>
                                    @endif
                                </div>

                                @unless($showArchived)
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
                                                <h5>Êtes-vous sûr de vouloir archiver</h5>
                                                <p class="lead fw-bold mb-3">{{ $entity->nom }} ?</p>
                                                <p class="text-muted fs-7">Cette action désactivera cet enregistrement mais conservera l'historique.</p>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Annuler</button>
                                                <form method="POST" action="{{ route($routePrefix . '.destroy', $entity->id) }}" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger">Confirmer l'archivage</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!--end::Delete Confirmation Modal-->
                                @endunless
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-10 text-muted">
                                <i class="fas fa-folder-open fs-2x mb-3 d-block"></i>
                                Aucun enregistrement trouvé.
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
