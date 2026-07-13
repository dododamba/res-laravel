@extends('layouts.app')

@section('title', 'Gestion des Carrés (Blocs)')

@section('content')
<!--begin::Header Layout-->
<div class="d-flex align-items-center justify-content-between mb-5">
    <div>
        <h1 class="fw-bold text-gray-900 mb-1">Gestion des Carrés (Subdivisions)</h1>
        <span class="text-muted fs-7">Zonage urbain par blocs et îlots administratifs (Nomenclatures)</span>
    </div>
    <div>
        <a href="{{ route('carre.create') }}" class="btn btn-primary btn-sm">
            <i class="fas fa-plus me-1"></i>Nouveau Carré
        </a>
    </div>
</div>
<!--end::Header Layout-->

<!--begin::Main Card-->
<div class="card card-flush shadow-sm">
    <!--begin::Card Header-->
    <div class="card-header align-items-center py-5 gap-2 gap-md-5">
        <div class="card-title">
            <form method="GET" action="{{ route('carre.index') }}" class="d-flex align-items-center position-relative my-1">
                <i class="fas fa-search fs-3 position-absolute ms-4 text-gray-500"></i>
                <input type="text" name="q" value="{{ $searchTerm }}" class="form-control form-control-solid w-250px ps-12" placeholder="Rechercher un carré..." />
                @if($showArchived)
                    <input type="hidden" name="archived" value="true" />
                @endif
            </form>
        </div>
        <div class="card-toolbar d-flex gap-3">
            @if($showArchived)
                <a href="{{ route('carre.index') }}" class="btn btn-sm btn-light-primary">
                    <i class="fas fa-eye me-1"></i>Voir actifs
                </a>
            @else
                <a href="{{ route('carre.index', ['archived' => 'true']) }}" class="btn btn-sm btn-light-danger">
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
                        <th class="min-w-150px">Nom du Carré (Îlot)</th>
                        <th class="min-w-150px">Quartier Parent</th>
                        <th class="min-w-150px">Superviseur (Chef de Carré)</th>
                        <th class="min-w-100px text-center">Habitations</th>
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
                                    <a href="{{ route('carre.index') }}/{{ $entity->id }}" class="text-gray-900 text-hover-primary fs-5 fw-bold mb-1">
                                        {{ $entity->nom }}
                                    </a>
                                    <span class="text-muted fs-8 font-monospace">Code : {{ $entity->code ?? 'N/A' }}</span>
                                </div>
                            </td>
                            <td>
                                @if($entity->quartier)
                                    <a href="{{ route('quartier.index') }}/{{ $entity->quartier->id }}" class="text-gray-800 text-hover-primary fw-bold fs-6">
                                        <i class="fas fa-map-marked-alt text-muted me-1"></i>{{ $entity->quartier->nom }}
                                    </a>
                                @else
                                    <span class="text-muted fs-7">Aucun quartier</span>
                                @endif
                            </td>
                            <td>
                                @if($entity->chef_carre)
                                    <div class="d-flex align-items-center">
                                        <div class="symbol symbol-35px symbol-circle me-3 bg-light-warning text-warning d-flex align-items-center justify-content-center fw-bold fs-7" style="width:35px; height:35px;">
                                            {{ substr($entity->chef_carre->personne->prenom, 0, 1) }}{{ substr($entity->chef_carre->personne->nom, 0, 1) }}
                                        </div>
                                        <div class="d-flex flex-column">
                                            <span class="text-gray-800 fw-bold fs-6">{{ $entity->chef_carre->personne->prenom }} {{ $entity->chef_carre->personne->nom }}</span>
                                            <span class="text-muted fs-8">Chef de Carré</span>
                                        </div>
                                    </div>
                                @else
                                    <span class="badge badge-light-warning text-warning fw-bold fs-8 px-3 py-2">Non supervisé</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge badge-light fw-bold fs-7 py-2 px-3">{{ $entity->maisons->count() }} fiches</span>
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('carre.index') }}/{{ $entity->id }}" class="btn btn-icon btn-light btn-active-color-primary btn-sm" title="Détails">
                                        <i class="fas fa-eye fs-6"></i>
                                    </a>
                                    <a href="{{ route('carre.index') }}/{{ $entity->id }}/edit" class="btn btn-icon btn-light btn-active-color-primary btn-sm" title="Modifier">
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
                                                <h5>Êtes-vous sûr de vouloir archiver le carré</h5>
                                                <p class="lead fw-bold mb-3">{{ $entity->nom }} ?</p>
                                                <p class="text-muted fs-7">Cette action retirera le carré de la liste de saisie urbaine active.</p>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Annuler</button>
                                                <form method="POST" action="{{ route('carre.destroy', $entity->id) }}" class="d-inline">
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
                                <i class="fas fa-th fs-2x mb-3 d-block"></i>
                                Aucun carré géographique trouvé.
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
