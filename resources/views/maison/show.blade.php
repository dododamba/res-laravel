@extends('layouts.app')

@section('title', "Fiche d'Habitation N°" . $maison->numero_porte)

@section('content')
@inject('workflow', 'App\Services\Workflow\MaisonWorkflowService')

<!--begin::Header Layout-->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-5 gap-3">
    <div>
        <h1 class="fw-bold text-gray-900 mb-1">Fiche d'Habitation : #{{ $maison->id }}</h1>
        <span class="text-muted fs-7">Saisie terrain et suivi d'urbanisme</span>
    </div>
    <div class="d-flex align-items-center gap-3">
        <a href="{{ route('maison.index') }}" class="btn btn-light">
            <i class="fas fa-arrow-left me-2"></i>Retour à la liste
        </a>
        @can('update', $maison)
            <a href="{{ route('maison.edit', $maison) }}" class="btn btn-primary">
                <i class="fas fa-edit me-2"></i>Modifier la fiche
            </a>
        @endcan
    </div>
</div>
<!--end::Header Layout-->

<!--begin::En-tête de Statut & Transitions-->
<div class="card card-flush shadow-sm mb-6 border-start border-5 border-{{ $maison->statut == \App\Enums\MaisonStatut::VALIDE ? 'success' : ($maison->statut == \App\Enums\MaisonStatut::REJETE ? 'danger' : ($maison->statut == \App\Enums\MaisonStatut::CONTROLE ? 'warning' : 'info')) }}">
    <div class="card-body p-6">
        <div class="row align-items-center">
            <div class="col-md-7">
                <h3 class="fw-bold text-dark mb-1">Adresse : {{ $maison->adresse }} (Porte {{ $maison->numero_porte }})</h3>
                @if($maison->reference_cadastrale)
                    <div class="text-danger fw-bold fs-7 mb-2">Référence Cadastrale : {{ $maison->reference_cadastrale }}</div>
                @endif
                <div class="mt-2 d-flex align-items-center gap-2">
                    <span class="text-gray-700">Statut actuel :</span>
                    <span class="badge {{ $maison->statut->badgeClass() }} fs-7 fw-bold py-2 px-3">{{ $maison->statut->label() }}</span>
                </div>
            </div>
            
            <div class="col-md-5 text-md-end mt-4 mt-md-0">
                <h5 class="fw-bold mb-3 fs-8 text-uppercase text-muted">Validation Technique & Workflow</h5>
                
                <div class="d-flex flex-wrap gap-2 justify-content-md-end">
                    <!-- Brouillon -> Soumis -->
                    @if($workflow->canTransition($maison, \App\Enums\MaisonStatut::SOUMIS))
                        <form method="POST" action="{{ route('maison.transition', $maison) }}">
                            @csrf
                            <input type="hidden" name="target_status" value="soumis">
                            <button type="submit" class="btn btn-sm btn-info text-white">
                                <i class="fas fa-paper-plane me-1"></i>Soumettre le dossier
                            </button>
                        </form>
                    @endif

                    <!-- Soumis -> Contrôlé -->
                    @if($workflow->canTransition($maison, \App\Enums\MaisonStatut::CONTROLE))
                        <form method="POST" action="{{ route('maison.transition', $maison) }}">
                            @csrf
                            <input type="hidden" name="target_status" value="controle">
                            <button type="submit" class="btn btn-sm btn-warning text-dark">
                                <i class="fas fa-clipboard-check me-1"></i>Valider le Contrôle de terrain
                            </button>
                        </form>
                    @endif

                    <!-- Contrôlé -> Validé -->
                    @if($maison->statut == \App\Enums\MaisonStatut::CONTROLE)
                        @if($workflow->canTransition($maison, \App\Enums\MaisonStatut::VALIDE))
                            <form method="POST" action="{{ route('maison.transition', $maison) }}" onsubmit="return confirm('Valider définitivement cette habitation pour le cadastre municipal ?');">
                                @csrf
                                <input type="hidden" name="target_status" value="valide">
                                <button type="submit" class="btn btn-sm btn-success">
                                    <i class="fas fa-check-double me-1"></i>Valider Définitivement (SIG)
                                </button>
                            </form>
                        @else
                            <button class="btn btn-sm btn-success" disabled title="Coordonnées GPS requises pour la validation SIG">
                                <i class="fas fa-check-double me-1"></i>Valider (GPS Requis)
                            </button>
                        @endif
                    @endif

                    <!-- Soumis / Contrôlé -> Rejeté -->
                    @if($workflow->canTransition($maison, \App\Enums\MaisonStatut::REJETE))
                        <button class="btn btn-sm btn-danger" type="button" data-bs-toggle="collapse" data-bs-target="#collapseRejet" aria-expanded="false" aria-controls="collapseRejet">
                            <i class="fas fa-times me-1"></i>Rejeter
                        </button>
                    @endif
                </div>

                <!-- Panel de motif de rejet (collapsed) -->
                <div class="collapse mt-3 text-start" id="collapseRejet">
                    <div class="card card-body bg-light-danger border border-danger p-4 rounded">
                        <h5 class="fw-bold text-danger mb-2 fs-6">Spécifiez le motif de rejet technique</h5>
                        <form method="POST" action="{{ route('maison.transition', $maison) }}">
                            @csrf
                            <input type="hidden" name="target_status" value="rejete">
                            <div class="input-group input-group-sm">
                                <input type="text" name="motif" class="form-control" placeholder="ex: Erreur d'alignement, photo de façade floue..." required>
                                <button type="submit" class="btn btn-danger">Confirmer le Rejet</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!--end::En-tête de Statut & Transitions-->

<div class="row g-6">
    <!-- Colonne Gauche : Identification, Services & GPS -->
    <div class="col-lg-5">
        <!-- Caractéristiques de l'Habitation -->
        <div class="card card-flush shadow-sm mb-6">
            <div class="card-header">
                <h3 class="card-title fw-bold text-dark">
                    <i class="fas fa-home text-primary me-2"></i>Caractéristiques Urbaines
                </h3>
            </div>
            <div class="card-body pt-0">
                <div class="d-flex flex-column gap-3 text-gray-800">
                    <div class="d-flex justify-content-between border-bottom pb-2">
                        <span class="fw-semibold">Carré Géographique (Bloc) :</span>
                        <span class="fw-bold">{{ $maison->carre?->nom ?? 'Non défini' }}</span>
                    </div>
                    
                    @if($maison->carre?->quartier)
                        <div class="d-flex justify-content-between border-bottom pb-2">
                            <span class="fw-semibold">Quartier Parent :</span>
                            <span class="fw-bold">{{ $maison->carre->quartier->nom }}</span>
                        </div>
                    @endif

                    <div class="d-flex justify-content-between border-bottom pb-2">
                        <span class="fw-semibold">Créée par :</span>
                        <span>
                            @if($maison->enqueteur)
                                <span class="fw-bold text-gray-900">{{ $maison->enqueteur->personne->prenom }} {{ $maison->enqueteur->personne->nom }}</span>
                                <span class="text-muted d-block fs-8 text-end">Matricule : {{ $maison->enqueteur->matricule }}</span>
                            @else
                                <span class="text-muted">Système / Inconnu</span>
                            @endif
                        </span>
                    </div>

                    @if($maison->controleur)
                        <div class="d-flex justify-content-between border-bottom pb-2">
                            <span class="fw-semibold">Contrôleur :</span>
                            <span class="fw-bold text-gray-900">{{ $maison->controleur->personne->prenom }} {{ $maison->controleur->personne->nom }}</span>
                        </div>
                    @endif

                    @if($maison->validateur)
                        <div class="d-flex justify-content-between border-bottom pb-2">
                            <span class="fw-semibold">Validateur :</span>
                            <span class="fw-bold text-gray-900">{{ $maison->validateur->personne->prenom }} {{ $maison->validateur->personne->nom }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Démographie de base -->
        <div class="card card-flush shadow-sm mb-6">
            <div class="card-header">
                <h3 class="card-title fw-bold text-dark">
                    <i class="fas fa-users text-primary me-2"></i>Population Estimée
                </h3>
            </div>
            <div class="card-body pt-0">
                <div class="row text-center g-4">
                    <div class="col-4">
                        <div class="p-3 bg-light rounded border border-dashed">
                            <div class="fs-4 fw-bold text-gray-900">{{ $maison->nombre_hommes ?? 0 }}</div>
                            <span class="text-muted fs-7">Hommes</span>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-3 bg-light rounded border border-dashed">
                            <div class="fs-4 fw-bold text-gray-900">{{ $maison->nombre_femmes ?? 0 }}</div>
                            <span class="text-muted fs-7">Femmes</span>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-3 bg-light rounded border border-dashed">
                            <div class="fs-4 fw-bold text-gray-900">{{ $maison->nombre_enfants ?? 0 }}</div>
                            <span class="text-muted fs-7">Enfants</span>
                        </div>
                    </div>
                </div>
                <div class="mt-4 p-3 bg-light-primary rounded border border-primary border-dashed text-center">
                    <span class="fs-7 text-primary fw-bold">Total estimé : {{ ($maison->nombre_hommes ?? 0) + ($maison->nombre_femmes ?? 0) + ($maison->nombre_enfants ?? 0) }} résidents</span>
                </div>
            </div>
        </div>

        <!-- Géolocalisation & SIG -->
        <div class="card card-flush shadow-sm">
            <div class="card-header">
                <h3 class="card-title fw-bold text-dark">
                    <i class="fas fa-satellite text-primary me-2"></i>Géolocalisation & Coordonnées SIG
                </h3>
            </div>
            <div class="card-body pt-0">
                <div class="d-flex flex-column gap-3 text-gray-800">
                    <div class="d-flex justify-content-between border-bottom pb-2">
                        <span class="fw-semibold">GPS Latitude :</span>
                        <span class="fw-bold">{{ $maison->gps_latitude ?? 'Non géolocalisée' }}</span>
                    </div>
                    <div class="d-flex justify-content-between border-bottom pb-2">
                        <span class="fw-semibold">GPS Longitude :</span>
                        <span class="fw-bold">{{ $maison->gps_longitude ?? 'Non géolocalisée' }}</span>
                    </div>
                </div>
                @if($maison->gps_latitude && $maison->gps_longitude)
                    <div class="mt-4 p-3 bg-light-success rounded border border-success border-dashed text-center">
                        <span class="fs-7 text-success fw-bold"><i class="fas fa-check-circle me-1 text-success"></i>Prêt pour le couplage SIG municipal</span>
                    </div>
                @else
                    <div class="mt-4 p-3 bg-light-warning rounded border border-warning border-dashed text-center">
                        <span class="fs-7 text-warning fw-bold"><i class="fas fa-exclamation-triangle me-1 text-warning"></i>Coordonnées GPS manquantes</span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Colonne Droite : Galerie Photos, Pièces jointes, Timeline d'audit -->
    <div class="col-lg-7">
        <div class="card card-flush shadow-sm h-100">
            <!-- Header with Tabs Navigation -->
            <div class="card-header header-tabs">
                <div class="card-title">
                    <ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x border-transparent fs-5 fw-bold" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a class="nav-link text-active-primary active" id="photos-tab" data-bs-toggle="tab" href="#photos_pane" role="tab">
                                <i class="fas fa-image me-2"></i>Photos
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link text-active-primary" id="docs-tab" data-bs-toggle="tab" href="#docs_pane" role="tab">
                                <i class="fas fa-file-pdf me-2"></i>Documents
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link text-active-primary" id="audit-tab" data-bs-toggle="tab" href="#audit_pane" role="tab">
                                <i class="fas fa-history me-2"></i>Historique & Audit
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Tab Content -->
            <div class="card-body pt-5">
                <div class="tab-content">
                    
                    <!-- Tab Pane 1: Photos -->
                    <div class="tab-pane fade show active" id="photos_pane" role="tabpanel">
                        <h4 class="fw-bold text-gray-900 mb-4">Galerie Photos</h4>
                        
                        @if($maison->hasMedia('photos_habitation'))
                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <div class="card border border-dashed rounded p-3 text-center">
                                        <img src="{{ $maison->getFirstMediaUrl('photos_habitation') }}" alt="Photo de la Façade" class="img-fluid rounded border mb-2 shadow-sm" style="max-height: 250px; object-fit: cover;" />
                                        <span class="fw-bold text-gray-800">Photo de la Façade</span>
                                        <span class="text-muted fs-8 d-block">Téléversé le : {{ $maison->getMedia('photos_habitation')->first()->created_at->format('d/m/Y H:i') }}</span>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="text-center text-muted py-10">
                                <i class="fas fa-images fs-2hx text-gray-300 mb-3 d-block"></i>
                                Aucune photo enregistrée pour cette habitation.
                            </div>
                        @endif
                    </div>

                    <!-- Tab Pane 2: Documents -->
                    <div class="tab-pane fade" id="docs_pane" role="tabpanel">
                        <h4 class="fw-bold text-gray-900 mb-4">Documents Joints</h4>
                        
                        @if($maison->hasMedia('documents_cadastre'))
                            <div class="row">
                                @foreach($maison->getMedia('documents_cadastre') as $doc)
                                    <div class="col-md-6 mb-4">
                                        <div class="card border border-dashed rounded p-4 h-100 d-flex flex-column justify-content-between">
                                            <div>
                                                <div class="d-flex align-items-center mb-3">
                                                    <i class="fas fa-file-contract text-primary fs-2hx me-3"></i>
                                                    <div>
                                                        <h6 class="fw-bold text-gray-800 mb-1">Document de Cadastre</h6>
                                                        <span class="text-muted fs-8">Taille : {{ number_format($doc->size / (1024 * 1024), 2) }} Mo</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="pt-3">
                                                <a href="{{ $doc->getUrl() }}" target="_blank" class="btn btn-sm btn-light-primary w-100">
                                                    <i class="fas fa-eye me-1"></i>Visualiser le document
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center text-muted py-10">
                                <i class="fas fa-file-signature fs-2hx text-gray-300 mb-3 d-block"></i>
                                Aucun document de cadastre ou justificatif joint.
                            </div>
                        @endif
                    </div>

                    <!-- Tab Pane 3: Audit & History -->
                    <div class="tab-pane fade" id="audit_pane" role="tabpanel">
                        <h4 class="fw-bold text-gray-900 mb-4">Timeline des Transitions & Actions d'Audit</h4>
                        
                        <div class="timeline timeline-border-dashed">
                            @forelse($maison->historiques as $log)
                                <!--begin::Timeline item-->
                                <div class="timeline-item">
                                    <!--begin::Timeline line-->
                                    <div class="timeline-line"></div>
                                    <!--end::Timeline line-->
                                    
                                    <!--begin::Timeline icon-->
                                    <div class="timeline-icon">
                                        <i class="fas fa-circle-notch text-primary fs-5"></i>
                                    </div>
                                    <!--end::Timeline icon-->
                                    
                                    <!--begin::Timeline content-->
                                    <div class="timeline-content mb-5 mt-n1">
                                        <!--begin::Timeline details-->
                                        <div class="pe-3 mb-1">
                                            <div class="d-flex align-items-center justify-content-between flex-wrap mb-1">
                                                <span class="badge badge-light-secondary text-capitalize fw-bold fs-8 py-1 px-2">{{ $log->action }}</span>
                                                <span class="text-muted fs-8 fw-semibold">{{ $log->created_at->format('d/m/Y H:i') }}</span>
                                            </div>
                                            
                                            <div class="p-3 bg-light rounded shadow-none">
                                                <div class="fw-bold text-gray-800 fs-6">{{ $log->details['message'] ?? 'Action sur la fiche' }}</div>
                                                
                                                @if(isset($log->details['ancien_statut']) || isset($log->details['nouveau_statut']))
                                                    <div class="text-muted fs-7 mt-1">
                                                        Status : <span class="text-danger fw-semibold">{{ $log->details['ancien_statut'] ?? '-' }}</span> &rarr; <span class="text-success fw-semibold">{{ $log->details['nouveau_statut'] ?? '-' }}</span>
                                                    </div>
                                                @endif

                                                @if(!empty($log->details['motif']))
                                                    <div class="text-danger border-start border-3 border-danger ps-3 mt-2 fs-7">
                                                        <strong>Motif du rejet :</strong> {{ $log->details['motif'] }}
                                                    </div>
                                                @endif
                                                
                                                <div class="text-muted fs-8 mt-2">
                                                    Opérateur : <span class="fw-semibold">{{ $log->user_identifier }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <!--end::Timeline details-->
                                    </div>
                                    <!--end::Timeline content-->
                                </div>
                                <!--end::Timeline item-->
                            @empty
                                <div class="text-center text-muted py-10">
                                    <i class="fas fa-history fs-2hx text-gray-300 mb-3 d-block"></i>
                                    Aucun événement ou log d'audit enregistré pour cette habitation.
                                </div>
                            @endforelse
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection
