@extends('layouts.app')

@section('title', 'Détails du Quartier : ' . $entity->nom)

@section('content')
<!--begin::Header Layout-->
<div class="d-flex align-items-center justify-content-between mb-5">
    <div>
        <h1 class="fw-bold text-gray-900 mb-1">Quartier : {{ $entity->nom }}</h1>
        <span class="text-muted fs-7">Administration territoriale et zonage cartographique</span>
    </div>
    <div class="d-flex gap-3">
        <a href="{{ route('quartier.index') }}" class="btn btn-light btn-sm">
            <i class="fas fa-arrow-left me-1"></i>Retour à la liste
        </a>
        <a href="{{ route('quartier.index') }}/{{ $entity->id }}/edit" class="btn btn-primary btn-sm">
            <i class="fas fa-pencil-alt me-1"></i>Modifier
        </a>
        <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal">
            <i class="fas fa-trash me-1"></i>Archiver
        </button>
    </div>
</div>
<!--end::Header Layout-->

<!--begin::Delete Confirmation Modal-->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
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
                <p class="text-muted fs-7">Cette action retirera le quartier de la saisie terrain active.</p>
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

<!--begin::Délégué de Quartier Card-->
<div class="card card-flush shadow-sm mb-6 border border-dashed border-primary">
    <div class="card-header py-4">
        <h3 class="card-title fw-bold text-gray-900">
            <i class="fas fa-user-shield text-primary me-2"></i>Délégué Communal Assigné
        </h3>
    </div>
    <div class="card-body pt-0">
        @if($entity->delegue)
            <div class="d-flex align-items-center">
                <div class="symbol symbol-60px symbol-circle bg-light-primary text-primary d-flex align-items-center justify-content-center fw-bold fs-1 me-5" style="width: 60px; height: 60px;">
                    {{ substr($entity->delegue->personne->prenom, 0, 1) }}{{ substr($entity->delegue->personne->nom, 0, 1) }}
                </div>
                <div class="d-flex flex-column">
                    <span class="fs-4 fw-bold text-gray-900 mb-1">{{ $entity->delegue->personne->prenom }} {{ $entity->delegue->personne->nom }}</span>
                    <div class="d-flex flex-wrap gap-5 text-gray-700">
                        <span class="fs-6 me-2">
                            <i class="fas fa-id-badge text-muted me-1"></i>Matricule : <strong>{{ $entity->delegue->matricule }}</strong>
                        </span>
                        <span class="fs-6 me-2">
                            <i class="fas fa-phone text-muted me-1"></i>{{ $entity->delegue->personne->telephone ?? 'Pas de téléphone' }}
                        </span>
                        <span class="fs-6">
                            <i class="fas fa-envelope text-muted me-1"></i>{{ $entity->delegue->personne->email ?? 'Pas d\'email' }}
                        </span>
                    </div>
                </div>
            </div>
        @else
            <div class="alert alert-dismissible bg-light-warning d-flex align-items-center p-5 rounded border border-warning border-dashed">
                <i class="fas fa-exclamation-triangle text-warning fs-2hx me-4"></i>
                <div class="d-flex flex-column">
                    <h5 class="fw-bold mb-1 text-warning">Aucun Délégué Assigné</h5>
                    <span>Pour assurer la supervision locale, veuillez associer un agent à ce quartier.</span>
                </div>
                <a href="{{ route('quartier.index') }}/{{ $entity->id }}/edit" class="btn btn-sm btn-warning ms-auto">Assigner un délégué</a>
            </div>
        @endif
    </div>
</div>
<!--end::Délégué de Quartier Card-->

<!--begin::Statistiques du quartier-->
<div class="row g-5 mb-6">
    <!-- Total Carrés -->
    <div class="col-md-3">
        <div class="card card-flush shadow-sm text-center py-5">
            <div class="symbol symbol-45px symbol-circle me-auto ms-auto mb-3 bg-light-primary text-primary d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                <i class="fas fa-th text-primary fs-5"></i>
            </div>
            <h3 class="fw-bold fs-2 text-gray-800 mb-0">{{ $entity->carres->count() }}</h3>
            <span class="text-muted fs-8 fw-semibold">Total Carrés</span>
        </div>
    </div>

    <!-- Habitants Estimés -->
    <div class="col-md-3">
        <div class="card card-flush shadow-sm text-center py-5">
            <div class="symbol symbol-45px symbol-circle me-auto ms-auto mb-3 bg-light-success text-success d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                <i class="fas fa-users text-success fs-5"></i>
            </div>
            <h3 class="fw-bold fs-2 text-gray-800 mb-0">{{ $habitants_total ?? 0 }}</h3>
            <span class="text-muted fs-8 fw-semibold">Habitants Estimés</span>
        </div>
    </div>

    <!-- Habitants Recensés -->
    <div class="col-md-3">
        <div class="card card-flush shadow-sm text-center py-5">
            <div class="symbol symbol-45px symbol-circle me-auto ms-auto mb-3 bg-light-info text-info d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                <i class="fas fa-id-card text-info fs-5"></i>
            </div>
            <h3 class="fw-bold fs-2 text-gray-800 mb-0">{{ $habitants_recenses ?? 0 }}</h3>
            <span class="text-muted fs-8 fw-semibold">Habitants Recensés</span>
        </div>
    </div>

    <!-- Progression globale -->
    <div class="col-md-3">
        <div class="card card-flush shadow-sm text-center py-5">
            <div class="symbol symbol-45px symbol-circle me-auto ms-auto mb-3 bg-light-warning text-warning d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                <i class="fas fa-chart-line text-warning fs-5"></i>
            </div>
            <h3 class="fw-bold fs-2 text-gray-800 mb-0">{{ $progression ?? 0 }}%</h3>
            <span class="text-muted fs-8 fw-semibold">Taux de Couverture</span>
        </div>
    </div>
</div>
<!--end::Statistiques du quartier-->

<!--begin::Navigation Tabs-->
<div class="card card-flush shadow-sm">
    <div class="card-header header-tabs">
        <div class="card-title">
            <ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x border-transparent fs-5 fw-bold" role="tablist">
                <li class="nav-item" role="presentation">
                    <a class="nav-link text-active-primary active" id="carres-tab" data-bs-toggle="tab" href="#carres_pane" role="tab">
                        <i class="fas fa-th me-2"></i>Carrés (Subdivisions)
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link text-active-primary" id="desc-tab" data-bs-toggle="tab" href="#desc_pane" role="tab">
                        <i class="fas fa-info-circle me-2"></i>Informations Générales
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <div class="card-body pt-5">
        <div class="tab-content">
            <!-- Tab Pane 1: Carrés -->
            <div class="tab-pane fade show active" id="carres_pane" role="tabpanel">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <h4 class="fw-bold text-gray-900 m-0">Découpage cartographique interne</h4>
                    <a href="{{ route('carre.create', ['quartier_id' => $entity->id]) }}" class="btn btn-sm btn-light-primary">
                        <i class="fas fa-plus me-1"></i>Ajouter un Carré
                    </a>
                </div>

                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed fs-6 gy-4">
                        <thead>
                            <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                <th>#</th>
                                <th>Code Carré</th>
                                <th>Nom du Carré (Bloc)</th>
                                <th>Superviseur / Chef de Carré</th>
                                <th class="text-center">Habitations</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-600 fw-semibold">
                            @forelse($entity->carres as $carre)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <span class="badge badge-light-dark font-monospace fs-8">{{ $carre->code ?? 'CR-' . $carre->id }}</span>
                                    </td>
                                    <td>
                                        <a href="{{ route('carre.index') }}/{{ $carre->id }}" class="text-gray-900 text-hover-primary fw-bold">
                                            {{ $carre->nom }}
                                        </a>
                                    </td>
                                    <td>
                                        @if($carre->chef_carre)
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-user-circle text-primary fs-5 me-2"></i>
                                                <span>{{ $carre->chef_carre->personne->prenom }} {{ $carre->chef_carre->personne->nom }}</span>
                                            </div>
                                        @else
                                            <span class="text-muted fs-8">Non supervisé</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-light fw-bold">{{ $carre->maisons->count() }} maisons</span>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('carre.index') }}/{{ $carre->id }}" class="btn btn-icon btn-light btn-active-color-primary btn-sm">
                                            <i class="fas fa-arrow-right fs-6"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        Aucun carré enregistré dans ce quartier.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab Pane 2: Description -->
            <div class="tab-pane fade" id="desc_pane" role="tabpanel">
                <h4 class="fw-bold text-gray-900 mb-4">Informations Générales</h4>
                
                <div class="mb-5">
                    <h6 class="fw-bold text-gray-700">Description :</h6>
                    <p class="text-gray-800 bg-light p-4 rounded border border-dashed fs-6">
                        {{ $entity->description ?? 'Aucune description disponible pour ce quartier.' }}
                    </p>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <h6 class="fw-bold text-gray-700">Code Système :</h6>
                        <span class="text-gray-900 fw-semibold">{{ $entity->id }}</span>
                    </div>
                    <div class="col-md-6 mb-3">
                        <h6 class="fw-bold text-gray-700">Dernière Mise à Jour :</h6>
                        <span class="text-gray-900 fw-semibold">{{ $entity->updated_at->format('d/m/Y H:i') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!--end::Navigation Tabs-->
@endsection
