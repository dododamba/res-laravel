@extends('layouts.app')

@section('title', 'Mon Profil Agent')

@section('content')
<!--begin::Toolbar-->
<div class="d-flex align-items-center justify-content-between mb-5">
    <div>
        <h1 class="fw-bold text-gray-900 mb-1">Mon Profil Agent</h1>
        <span class="text-muted fs-7">Vos informations personnelles et historique de vos affectations de terrain</span>
    </div>
    <div>
        <a href="{{ route('profile.edit') }}" class="btn btn-sm btn-primary">
            <i class="fas fa-user-cog me-1"></i>Paramètres du compte
        </a>
    </div>
</div>
<!--end::Toolbar-->

<div class="row g-6">
    <!-- Colonne Gauche: Résumé Profil -->
    <div class="col-xl-4">
        <div class="card card-flush shadow-sm mb-6 p-6 text-center">
            <div class="d-flex flex-column align-items-center mb-5">
                <!-- Avatar -->
                <div class="symbol symbol-125px symbol-circle mb-4 border border-3 border-light shadow-sm bg-light-primary text-primary fw-bold fs-2hx d-flex align-items-center justify-content-center" style="width:125px; height:125px;">
                    @if($user->avatar)
                        <img src="{{ asset('uploads/avatars/' . $user->avatar) }}" alt="Avatar" class="rounded-circle" style="object-fit: cover; width:125px; height:125px;" />
                    @else
                        {{ substr($user->firstname ?? 'A', 0, 1) }}{{ substr($user->lastname ?? 'G', 0, 1) }}
                    @endif
                </div>
                
                <h3 class="fw-bold text-gray-900 mb-1">{{ $user->firstname }} {{ $user->lastname }}</h3>
                <span class="text-muted fs-7 mb-4 d-block">{{ $user->email }}</span>

                <!-- Rôles système -->
                <div class="d-flex flex-wrap justify-content-center gap-1 mb-4">
                    @forelse($user->roles as $role)
                        <span class="badge badge-light-primary fw-bold fs-8 px-3 py-1 border">{{ $role->name }}</span>
                    @empty
                        <span class="badge badge-light-secondary fs-8">Aucun rôle système</span>
                    @endforelse
                </div>

                <!-- Liaison Agent -->
                @if($user->agent)
                    <span class="badge badge-light-info d-flex align-items-center gap-1 fs-8 px-3 py-2 border border-info border-dashed">
                        <i class="fas fa-id-card text-info fs-7"></i> Agent Matricule : {{ $user->agent->matricule }}
                    </span>
                @else
                    <span class="badge badge-light-warning d-flex align-items-center gap-1 fs-8 px-3 py-2 border border-warning border-dashed">
                        <i class="fas fa-exclamation-triangle text-warning fs-7"></i> Compte Administratif (Hors Terrain)
                    </span>
                @endif
            </div>

            <div class="separator separator-dashed my-5"></div>

            <div class="text-start fs-7 text-gray-800">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted fw-semibold">Identifiant Système :</span>
                    <span class="fw-bold font-monospace">#{{ $user->id }}</span>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="text-muted fw-semibold">Inscrit depuis le :</span>
                    <span class="fw-bold">{{ $user->created_at->format('d/m/Y') }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Colonne Droite: Onglets Informations & Affectations -->
    <div class="col-xl-8">
        <div class="card card-flush shadow-sm h-100">
            <!-- Navigation par onglets -->
            <div class="card-header header-tabs">
                <div class="card-title">
                    <ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x border-transparent fs-5 fw-bold" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a class="nav-link text-active-primary active" id="general-tab" data-bs-toggle="tab" href="#general_pane" role="tab">
                                <i class="fas fa-info-circle me-2"></i>Mes Informations
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link text-active-primary" id="affectations-tab" data-bs-toggle="tab" href="#affectations_pane" role="tab">
                                <i class="fas fa-map-marked-alt me-2"></i>Mes Affectations de Terrain
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="card-body pt-6">
                <div class="tab-content">
                    
                    <!-- Onglet 1: Informations Générales -->
                    <div class="tab-pane fade show active" id="general_pane" role="tabpanel">
                        <h4 class="fw-bold text-gray-900 mb-5">Informations du Compte Municipal</h4>
                        
                        <div class="row mb-5 border-bottom pb-3">
                            <label class="col-md-4 fw-semibold text-muted">Prénom :</label>
                            <div class="col-md-8 fw-bold fs-6 text-gray-800">{{ $user->firstname ?? 'Non défini' }}</div>
                        </div>

                        <div class="row mb-5 border-bottom pb-3">
                            <label class="col-md-4 fw-semibold text-muted">Nom de famille :</label>
                            <div class="col-md-8 fw-bold fs-6 text-gray-800">{{ $user->lastname ?? 'Non défini' }}</div>
                        </div>

                        <div class="row mb-5 border-bottom pb-3">
                            <label class="col-md-4 fw-semibold text-muted">Adresse Email :</label>
                            <div class="col-md-8 fw-bold fs-6 text-gray-800">{{ $user->email }}</div>
                        </div>

                        <div class="row mb-5 border-bottom pb-3">
                            <label class="col-md-4 fw-semibold text-muted">Numéro de Téléphone :</label>
                            <div class="col-md-8 fw-bold fs-6 text-gray-800">{{ $user->telephone ?? 'Non défini' }}</div>
                        </div>

                        @if($user->agent)
                            <h4 class="fw-bold text-gray-900 mt-8 mb-5">Fiche d'Agent Territorial</h4>
                            
                            <div class="row mb-5 border-bottom pb-3">
                                <label class="col-md-4 fw-semibold text-muted">Matricule Officiel :</label>
                                <div class="col-md-8 fw-bold fs-6 text-gray-800">{{ $user->agent->matricule }}</div>
                            </div>

                            <div class="row mb-5 border-bottom pb-3">
                                <label class="col-md-4 fw-semibold text-muted">Sexe :</label>
                                <div class="col-md-8 fw-bold fs-6 text-gray-800">{{ $user->agent->sexe == 'M' ? 'Masculin' : ($user->agent->sexe == 'F' ? 'Féminin' : 'S/D') }}</div>
                            </div>

                            <div class="row mb-5">
                                <label class="col-md-4 fw-semibold text-muted">Fonction d'Affectation :</label>
                                <div class="col-md-8 fw-bold fs-6 text-gray-800">{{ $user->agent->fonction?->nom ?? 'Non définie' }}</div>
                            </div>
                        @endif
                    </div>

                    <!-- Onglet 2: Affectations de terrain (Secteurs) -->
                    <div class="tab-pane fade" id="affectations_pane" role="tabpanel">
                        <h4 class="fw-bold text-gray-900 mb-4">Mes Affectations & Périmètres géographiques</h4>
                        <p class="text-muted fs-7 mb-6">Liste historique et active de vos secteurs géographiques et rôles d'exploitation délégués.</p>

                        @if($user->agent && $user->agent->affectations->count() > 0)
                            <div class="table-responsive">
                                <table class="table align-middle table-row-dashed fs-7 gy-4">
                                    <thead>
                                        <tr class="text-start text-muted fw-bold fs-8 text-uppercase gs-0">
                                            <th>#</th>
                                            <th>Fonction</th>
                                            <th>Secteur / Territoire assigné</th>
                                            <th>Date de Début</th>
                                            <th>Date de Fin</th>
                                            <th class="text-center">Statut</th>
                                        </tr>
                                    </thead>
                                    <tbody class="text-gray-600 fw-semibold">
                                        @foreach($user->agent->affectations as $aff)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>
                                                    <div class="fw-bold text-gray-800">{{ $aff->fonction?->nom ?? 'Agent' }}</div>
                                                    <span class="text-muted fs-8 font-monospace">{{ $aff->fonction?->code ?? 'N/A' }}</span>
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
                                                        <span class="text-muted">Global / Non géographique</span>
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
                                <i class="fas fa-map-signs fs-2hx text-gray-300 mb-3 d-block"></i>
                                Aucune affectation géographique ou fonctionnelle enregistrée pour votre profil.
                            </div>
                        @endif
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection
