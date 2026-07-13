@extends('layouts.app')

@section('title', 'Détails du Carré : ' . $entity->nom)

@section('content')
<!--begin::Header Layout-->
<div class="d-flex align-items-center justify-content-between mb-5">
    <div>
        <h1 class="fw-bold text-gray-900 mb-1">Carré : {{ $entity->nom }}</h1>
        <span class="text-muted fs-7">Sous-division et secteur géographique de contrôle</span>
    </div>
    <div class="d-flex gap-3">
        <a href="{{ route('carre.index') }}" class="btn btn-light btn-sm">
            <i class="fas fa-arrow-left me-1"></i>Retour à la liste
        </a>
        <a href="{{ route('carre.index') }}/{{ $entity->id }}/edit" class="btn btn-primary btn-sm">
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
                <h5>Êtes-vous sûr de vouloir archiver le carré</h5>
                <p class="lead fw-bold mb-3">{{ $entity->nom }} ?</p>
                <p class="text-muted fs-7">Cette action retirera le carré géographique de la saisie terrain active.</p>
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

<div class="row g-6 mb-6">
    <!-- Colonne Gauche : Superviseur (Chef de Carré) -->
    <div class="col-lg-6">
        <div class="card card-flush shadow-sm h-100 border border-dashed border-warning">
            <div class="card-header py-4">
                <h3 class="card-title fw-bold text-gray-900">
                    <i class="fas fa-users-cog text-warning me-2"></i>Superviseur Actif (Chef de Carré)
                </h3>
            </div>
            <div class="card-body pt-0">
                @if($entity->chef_carre)
                    <div class="d-flex align-items-center">
                        <div class="symbol symbol-60px symbol-circle bg-light-warning text-warning d-flex align-items-center justify-content-center fw-bold fs-1 me-5" style="width: 60px; height: 60px;">
                            {{ substr($entity->chef_carre->personne->prenom, 0, 1) }}{{ substr($entity->chef_carre->personne->nom, 0, 1) }}
                        </div>
                        <div class="d-flex flex-column">
                            <span class="fs-4 fw-bold text-gray-900 mb-1">{{ $entity->chef_carre->personne->prenom }} {{ $entity->chef_carre->personne->nom }}</span>
                            <div class="d-flex flex-column text-gray-700 gap-1 mt-1">
                                <span class="fs-7">
                                    <i class="fas fa-id-badge text-muted me-1 w-20px"></i>Matricule : <strong>{{ $entity->chef_carre->matricule }}</strong>
                                </span>
                                <span class="fs-7">
                                    <i class="fas fa-phone text-muted me-1 w-20px"></i>{{ $entity->chef_carre->personne->telephone ?? 'Pas de téléphone' }}
                                </span>
                                <span class="fs-7">
                                    <i class="fas fa-envelope text-muted me-1 w-20px"></i>{{ $entity->chef_carre->personne->email ?? 'Pas d\'email' }}
                                </span>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="alert alert-dismissible bg-light-warning d-flex align-items-center p-5 rounded border border-warning border-dashed my-2">
                        <i class="fas fa-exclamation-triangle text-warning fs-2hx me-4"></i>
                        <div class="d-flex flex-column">
                            <h5 class="fw-bold mb-1 text-warning">Aucun Superviseur Assigné</h5>
                            <span class="fs-7">Attribuez un agent de contrôle à ce carré pour valider les habitations.</span>
                        </div>
                        <a href="{{ route('carre.index') }}/{{ $entity->id }}/edit" class="btn btn-sm btn-warning ms-auto">Assigner</a>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Colonne Droite : Quartier Parent & Stats -->
    <div class="col-lg-6">
        <div class="card card-flush shadow-sm h-100 border">
            <div class="card-header py-4">
                <h3 class="card-title fw-bold text-gray-900">
                    <i class="fas fa-map-marked-alt text-primary me-2"></i>Quartier de Rattachement
                </h3>
            </div>
            <div class="card-body pt-0 d-flex flex-column justify-content-between">
                <div>
                    @if($entity->quartier)
                        <div class="d-flex align-items-center mb-4">
                            <div class="symbol symbol-45px me-3 bg-light-primary text-primary d-flex align-items-center justify-content-center fw-bold fs-4" style="width:45px; height:45px;">
                                <i class="fas fa-city"></i>
                            </div>
                            <div class="d-flex flex-column">
                                <a href="{{ route('quartier.index') }}/{{ $entity->quartier->id }}" class="text-gray-900 text-hover-primary fs-5 fw-bold mb-0">
                                    {{ $entity->quartier->nom }}
                                </a>
                                <span class="text-muted fs-8">Code quartier : {{ $entity->quartier->code ?? 'N/A' }}</span>
                            </div>
                        </div>
                    @else
                        <span class="text-muted fs-7 d-block mb-4">Ce carré n'est rattaché à aucun quartier.</span>
                    @endif
                </div>

                <div class="border-top pt-4">
                    <div class="d-flex justify-content-between mb-2 fs-7">
                        <span class="fw-bold text-gray-700">Taux de couverture du carré :</span>
                        <span class="fw-bold text-gray-900">{{ $progression }}%</span>
                    </div>
                    <div class="progress h-8px w-100 bg-light-success">
                        <div class="progress-bar bg-success" role="progressbar" style="width: {{ $progression }}%"></div>
                    </div>
                    <span class="text-muted fs-8 mt-1 d-block text-end">Habitants estimés : {{ $habitants_total }} | Recensés : {{ $habitants_recenses }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!--begin::Habitations et Détails-->
<div class="card card-flush shadow-sm">
    <div class="card-header header-tabs">
        <div class="card-title">
            <ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x border-transparent fs-5 fw-bold" role="tablist">
                <li class="nav-item" role="presentation">
                    <a class="nav-link text-active-primary active" id="maisons-tab" data-bs-toggle="tab" href="#maisons_pane" role="tab">
                        <i class="fas fa-home me-2"></i>Fiches d'Habitations ({{ $entity->maisons->count() }})
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link text-active-primary" id="topography-tab" data-bs-toggle="tab" href="#topography_pane" role="tab">
                        <i class="fas fa-info-circle me-2"></i>Topographie & Notes
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <div class="card-body pt-5">
        <div class="tab-content">
            <!-- Tab Pane 1: Habitations -->
            <div class="tab-pane fade show active" id="maisons_pane" role="tabpanel">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <h4 class="fw-bold text-gray-900 m-0">Habitations recensées dans ce carré</h4>
                    <a href="{{ route('maison.create', ['carre_id' => $entity->id]) }}" class="btn btn-sm btn-light-success">
                        <i class="fas fa-plus me-1"></i>Recenser une Habitation
                    </a>
                </div>

                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed fs-6 gy-5">
                        <thead>
                            <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                <th>N° Porte</th>
                                <th>Adresse</th>
                                <th>Enquêteur</th>
                                <th class="text-center">Statut</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-600 fw-semibold">
                            @forelse($entity->maisons as $maison)
                                <tr>
                                    <td>
                                        <span class="badge badge-light-primary fw-bold fs-6">Porte {{ $maison->numero_porte }}</span>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <a href="{{ route('maison.show', $maison) }}" class="text-gray-900 text-hover-primary fw-bold fs-6">
                                                {{ $maison->adresse }}
                                            </a>
                                            @if($maison->reference_cadastrale)
                                                <span class="text-danger fs-8 fw-semibold">Réf Cadastre : {{ $maison->reference_cadastrale }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        @if($maison->enqueteur)
                                            <span class="fs-7 text-gray-800">{{ $maison->enqueteur->personne->prenom }} {{ $maison->enqueteur->personne->nom }}</span>
                                        @else
                                            <span class="text-muted fs-8">Système</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge {{ $maison->statut->badgeClass() }} py-2 px-3 fs-8">{{ $maison->statut->label() }}</span>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('maison.show', $maison) }}" class="btn btn-icon btn-light btn-active-color-primary btn-sm">
                                            <i class="fas fa-eye fs-6"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-10 text-muted">
                                        <i class="fas fa-home fs-2x mb-3 d-block"></i>
                                        Aucune habitation enregistrée pour ce carré géographique.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab Pane 2: Topography notes -->
            <div class="tab-pane fade" id="topography_pane" role="tabpanel">
                <h4 class="fw-bold text-gray-900 mb-4">Détails Topographiques & Limites</h4>
                <div class="mb-5">
                    <p class="text-gray-800 bg-light p-4 rounded border border-dashed fs-6">
                        {{ $entity->description ?? 'Aucune spécification ou description topologique n\'a été saisie pour ce carré.' }}
                    </p>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <h6 class="fw-bold text-gray-700">Code Système :</h6>
                        <span class="text-gray-900 font-monospace">{{ $entity->id }}</span>
                    </div>
                    <div class="col-md-4 mb-3">
                        <h6 class="fw-bold text-gray-700">Ordre d'affichage :</h6>
                        <span class="text-gray-900 fw-semibold">{{ $entity->ordre_affichage ?? 0 }}</span>
                    </div>
                    <div class="col-md-4 mb-3">
                        <h6 class="fw-bold text-gray-700">Créé le :</h6>
                        <span class="text-gray-900 fw-semibold">{{ $entity->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!--end::Habitations et Détails-->
@endsection
