@extends('layouts.app')

@section('title', "Profil Agent : " . $agent->personne->prenom . " " . $agent->personne->nom)

@section('content')
<!--begin::Toolbar-->
<div class="d-flex align-items-center justify-content-between mb-5">
    <div>
        <h1 class="fw-bold text-gray-900 mb-1">Détails de l'Agent Territorial</h1>
        <span class="text-muted fs-7">Fiche de ressources humaines et suivi d'affectations terrain</span>
    </div>
    <div class="d-flex gap-3">
        <a href="{{ route('agent.index') }}" class="btn btn-sm btn-light">
            <i class="fas fa-arrow-left me-1"></i>Retour à la liste
        </a>
        <a href="{{ route('agent.edit', $agent) }}" class="btn btn-sm btn-primary">
            <i class="fas fa-pencil-alt me-1"></i>Modifier la fiche
        </a>
    </div>
</div>
<!--end::Toolbar-->

<div class="row g-6">
    <!-- Colonne Gauche: Résumé Identité Agent -->
    <div class="col-xl-4">
        <!-- Identity Summary Card -->
        <div class="card card-flush shadow-sm mb-6 p-6 text-center">
            <div class="d-flex flex-column align-items-center mb-5">
                <!-- Profile Avatar Circle -->
                <div class="symbol symbol-125px symbol-circle mb-4 border border-3 border-light shadow-sm bg-light-primary text-primary fw-bold fs-2hx d-flex align-items-center justify-content-center" style="width:125px; height:125px;">
                    @if($agent->user && $agent->user->avatar)
                        <img src="{{ asset('uploads/avatars/' . $agent->user->avatar) }}" alt="Avatar" class="rounded-circle" style="object-fit: cover; width:125px; height:125px;" />
                    @else
                        {{ substr($agent->personne->prenom, 0, 1) }}{{ substr($agent->personne->nom, 0, 1) }}
                    @endif
                </div>
                
                <h3 class="fw-bold text-gray-900 mb-1">{{ $agent->personne->prenom }} {{ $agent->personne->nom }}</h3>
                <span class="badge badge-light-info py-2 px-3 fw-bold fs-8 mb-4 border border-info border-dashed">{{ $agent->fonction?->nom ?? 'Non définie' }}</span>

                <!-- Badge de Statut -->
                @if($agent->statut->value == 'actif')
                    <span class="badge bg-success text-white py-1 px-4 fs-8">ACTIF / OPÉRATIONNEL</span>
                @elseif($agent->statut->value == 'suspendu')
                    <span class="badge bg-warning text-dark py-1 px-4 fs-8">SUSPENDU</span>
                @else
                    <span class="badge bg-secondary text-white py-1 px-4 fs-8">INACTIF</span>
                @endif
            </div>

            <div class="separator separator-dashed my-5"></div>

            <!-- RH Attributes List -->
            <div class="table-responsive text-start fs-7 text-gray-800">
                <table class="table align-middle table-row-bordered gy-3">
                    <tbody>
                        <tr>
                            <span class="text-muted fw-semibold">Matricule :</span>
                            <span class="fw-bold text-danger float-end font-monospace">{{ $agent->matricule }}</span>
                        </tr>
                        <tr class="d-block border-bottom py-2">
                            <span class="text-muted fw-semibold">Numéro CNI :</span>
                            <span class="fw-bold text-gray-900 float-end">{{ $agent->cni ?? 'Non spécifié' }}</span>
                        </tr>
                        <tr class="d-block border-bottom py-2">
                            <span class="text-muted fw-semibold">Sexe :</span>
                            <span class="fw-bold text-gray-900 float-end">{{ $agent->sexe == 'M' ? 'Masculin' : 'Féminin' }}</span>
                        </tr>
                        <tr class="d-block border-bottom py-2">
                            <span class="text-muted fw-semibold">Né(e) le :</span>
                            <span class="fw-bold text-gray-900 float-end">{{ $agent->date_naissance ? \Carbon\Carbon::parse($agent->date_naissance)->format('d/m/Y') : 'Non spécifiée' }}</span>
                        </tr>
                        <tr class="d-block border-bottom py-2">
                            <span class="text-muted fw-semibold">À :</span>
                            <span class="fw-bold text-gray-900 float-end">{{ $agent->lieu_naissance ?? 'Non spécifié' }}</span>
                        </tr>
                        <tr class="d-block border-bottom py-2">
                            <span class="text-muted fw-semibold">Nationalité :</span>
                            <span class="fw-bold text-gray-900 float-end">{{ $agent->nationalite ?? 'Tchadienne' }}</span>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Liaison Compte Utilisateur Card -->
        <div class="card card-flush shadow-sm border border-dashed border-primary">
            <div class="card-header py-4">
                <h3 class="card-title fw-bold text-gray-900">
                    <i class="fas fa-lock text-primary me-2"></i>Compte d'Accès Système
                </h3>
            </div>
            <div class="card-body pt-0 fs-7">
                @if($agent->user)
                    <div class="d-flex align-items-center mb-4">
                        <div class="symbol symbol-40px symbol-circle bg-light-success text-success fw-bold fs-5 d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                            <i class="fas fa-check-double text-success"></i>
                        </div>
                        <div class="d-flex flex-column">
                            <a href="{{ route('user.show', $agent->user) }}" class="text-gray-900 text-hover-primary fw-bold fs-6">Compte d'accès actif</a>
                            <span class="text-muted fs-8">Créé le : {{ $agent->user->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                    </div>
                    <div class="separator separator-dashed my-4"></div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Identifiant email :</span>
                        <span class="fw-bold text-gray-800">{{ $agent->user->email }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Autorisé à se connecter :</span>
                        <span class="badge @if($agent->user->is_active) bg-light-success text-success @else bg-light-danger text-danger @endif py-1 px-2 fs-9">@if($agent->user->is_active) OUI @else NON @endif</span>
                    </div>
                @else
                    <div class="alert alert-dismissible bg-light-warning d-flex align-items-center p-4 rounded border border-warning border-dashed my-2">
                        <i class="fas fa-exclamation-triangle text-warning fs-3 me-3"></i>
                        <div class="d-flex flex-column fs-8">
                            <h6 class="fw-bold mb-0 text-warning">Compte d'accès manquant</h6>
                            <span>Aucun login n'est lié pour les enquêtes.</span>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Colonne Droite: Onglets Informations Complémentaires & Affectations -->
    <div class="col-xl-8">
        <div class="card card-flush shadow-sm h-100">
            <!-- Header layout dynamic tab triggers -->
            <div class="card-header header-tabs">
                <div class="card-title">
                    <ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x border-transparent fs-5 fw-bold" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a class="nav-link text-active-primary active" id="details-tab" data-bs-toggle="tab" href="#details_pane" role="tab">
                                <i class="fas fa-info-circle me-2"></i>Fiche Civil & Contact
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link text-active-primary" id="affectations-tab" data-bs-toggle="tab" href="#affectations_pane" role="tab">
                                <i class="fas fa-map-marked-alt me-2"></i>Affectations Territoriales ({{ $agent->affectations->count() }})
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Tab Contents -->
            <div class="card-body pt-6">
                <div class="tab-content">
                    
                    <!-- Tab Pane 1: Civil & Contacts details -->
                    <div class="tab-pane fade show active" id="details_pane" role="tabpanel">
                        <h4 class="fw-bold text-gray-900 mb-5">Coordonnées de Contact de l'Agent</h4>
                        
                        <div class="row mb-5 border-bottom pb-3">
                            <label class="col-md-4 fw-semibold text-muted">Adresse Email :</label>
                            <div class="col-md-8 fw-bold fs-6 text-gray-800">{{ $agent->personne->email }}</div>
                        </div>

                        <div class="row mb-5 border-bottom pb-3">
                            <label class="col-md-4 fw-semibold text-muted">Téléphone Principal :</label>
                            <div class="col-md-8 fw-bold fs-6 text-gray-800">{{ $agent->personne->telephone }}</div>
                        </div>

                        <div class="row mb-5 border-bottom pb-3">
                            <label class="col-md-4 fw-semibold text-muted">Téléphone Secondaire :</label>
                            <div class="col-md-8 fw-bold fs-6 text-gray-800">{{ $agent->telephone_secondaire ?? 'Non renseigné' }}</div>
                        </div>

                        <div class="row mb-5 border-bottom pb-3">
                            <label class="col-md-4 fw-semibold text-muted">Adresse de Résidence :</label>
                            <div class="col-md-8 fw-bold fs-6 text-gray-800">{{ $agent->adresse ?? 'Non spécifiée' }}</div>
                        </div>

                        <div class="row mb-5 border-bottom pb-3">
                            <label class="col-md-4 fw-semibold text-muted">Profession :</label>
                            <div class="col-md-8 fw-bold fs-6 text-gray-800">{{ $agent->profession ?? 'Non spécifiée' }}</div>
                        </div>

                        @if($agent->observations)
                            <h4 class="fw-bold text-gray-900 mt-8 mb-4">Observations / Notes Internes</h4>
                            <p class="bg-light-warning border-start border-3 border-warning rounded p-4 text-gray-800 fs-6">
                                {{ $agent->observations }}
                            </p>
                        @endif
                    </div>

                    <!-- Tab Pane 2: Affectations lists -->
                    <div class="tab-pane fade" id="affectations_pane" role="tabpanel">
                        <div class="d-flex align-items-center justify-content-between mb-4">
                            <h4 class="fw-bold text-gray-900 m-0">Secteurs géographiques délégués</h4>
                            <span class="text-muted fs-8">Historique et attributions de collecte active</span>
                        </div>

                        @if($agent->affectations->count() > 0)
                            <div class="table-responsive">
                                <table class="table align-middle table-row-dashed fs-7 gy-4">
                                    <thead>
                                        <tr class="text-start text-muted fw-bold fs-8 text-uppercase gs-0">
                                            <th>#</th>
                                            <th>Rôle / Fonction</th>
                                            <th>Périmètre Territorial</th>
                                            <th>Début</th>
                                            <th>Fin</th>
                                            <th class="text-center">Statut</th>
                                        </tr>
                                    </thead>
                                    <tbody class="text-gray-600 fw-semibold">
                                        @foreach($agent->affectations as $aff)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>
                                                    <div class="fw-bold text-gray-800">{{ $aff->fonction?->nom ?? 'Agent de collecte' }}</div>
                                                    <span class="text-muted fs-8 font-monospace">{{ $aff->fonction?->code ?? 'ENQ' }}</span>
                                                </td>
                                                <td>
                                                    @if($aff->quartier)
                                                        <span class="badge badge-light-primary fw-bold py-1 px-2 fs-8">
                                                            <i class="fas fa-map-marked-alt text-primary me-1"></i>Quartier : {{ $aff->quartier->nom }}
                                                        </span>
                                                    @elseif($aff->carre)
                                                        <span class="badge badge-light-warning fw-bold py-1 px-2 fs-8">
                                                            <i class="fas fa-th text-warning me-1"></i>Carré : {{ $aff->carre->nom }}
                                                        </span>
                                                    @else
                                                        <span class="text-muted fs-8">Global</span>
                                                    @endif
                                                </td>
                                                <td>{{ $aff->date_debut ? \Carbon\Carbon::parse($aff->date_debut)->format('d/m/Y') : 'Immédiat' }}</td>
                                                <td>{{ $aff->date_fin ? \Carbon\Carbon::parse($aff->date_fin)->format('d/m/Y') : '-' }}</td>
                                                <td class="text-center">
                                                    @if($aff->statut == 'actif')
                                                        <span class="badge bg-light-success text-success py-1 px-3 fs-8">ACTIF</span>
                                                    @else
                                                        <span class="badge bg-light-secondary text-muted py-1 px-3 fs-8">TERMINÉ</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center text-muted py-10">
                                <i class="fas fa-map-marked fs-2hx text-gray-300 mb-3 d-block"></i>
                                Cet agent territorial n'a aucune affectation géographique (quartier/carré) active ou historique.
                            </div>
                        @endif
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection
