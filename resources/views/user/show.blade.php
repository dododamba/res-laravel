@extends('layouts.app')

@section('title', 'Profil de ' . $user->firstname . ' ' . $user->lastname)

@section('content')
<!--begin::Toolbar-->
<div class="d-flex align-items-center justify-content-between mb-5">
    <div>
        <h1 class="fw-bold text-gray-900 mb-1">Détails de l'Utilisateur</h1>
        <span class="text-muted fs-7">Fiche d'habilitation RBAC et audit de sécurité</span>
    </div>
    <div class="d-flex gap-3">
        <a href="{{ route('user.index') }}" class="btn btn-sm btn-light">
            <i class="fas fa-arrow-left me-1"></i>Retour à la liste
        </a>
        <a href="{{ route('user.edit', $user) }}" class="btn btn-sm btn-primary">
            <i class="fas fa-pencil-alt me-1"></i>Modifier l'Habilitation
        </a>
    </div>
</div>
<!--end::Toolbar-->

<div class="row g-6">
    <!-- Colonne Gauche: Résumé Profil & Agent -->
    <div class="col-xl-4">
        <!-- Profile summary card -->
        <div class="card card-flush shadow-sm mb-6 p-6 text-center">
            <div class="d-flex flex-column align-items-center mb-5">
                <div class="symbol symbol-100px symbol-circle mb-4 bg-light-primary text-primary fw-bold fs-2hx d-flex align-items-center justify-content-center" style="width:100px; height:100px;">
                    {{ substr($user->firstname ?? 'U', 0, 1) }}{{ substr($user->lastname ?? 'S', 0, 1) }}
                </div>
                
                <h3 class="fw-bold text-gray-900 mb-1">{{ $user->firstname }} {{ $user->lastname }}</h3>
                <span class="text-muted fs-7 mb-4 d-block">{{ $user->email }}</span>

                <!-- Badges de rôles -->
                <div class="d-flex flex-wrap justify-content-center gap-1 mb-4">
                    @forelse($user->roles as $role)
                        <span class="badge badge-light-primary fw-bold fs-8 px-3 py-1">{{ str_replace('ROLE_', '', $role->slug) }}</span>
                    @empty
                        <span class="badge badge-light-secondary fs-8">Aucun rôle</span>
                    @endforelse
                </div>

                <!-- Status de vérification -->
                @if($user->is_verified)
                    <span class="badge badge-light-success d-flex align-items-center gap-1 fs-8 px-3 py-2">
                        <i class="fas fa-check-circle text-success fs-7"></i>Compte Vérifié & Actif
                    </span>
                @else
                    <span class="badge badge-light-warning d-flex align-items-center gap-1 fs-8 px-3 py-2">
                        <i class="fas fa-exclamation-triangle text-warning fs-7"></i>En attente de vérification
                    </span>
                @endif
            </div>

            <div class="separator separator-dashed my-5"></div>

            <div class="text-start fs-7 text-gray-800">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted fw-semibold">ID Système :</span>
                    <span class="fw-bold">#{{ $user->id }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted fw-semibold">Compte créé le :</span>
                    <span class="fw-bold">{{ $user->created_at->format('d/m/Y H:i') }}</span>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="text-muted fw-semibold">Statut système :</span>
                    <span class="badge @if(($user->status ?? 'active') == 'active') bg-light-success text-success @else bg-light-danger text-danger @endif py-1 px-2 fs-8">{{ strtoupper($user->status ?? 'active') }}</span>
                </div>
            </div>
        </div>

        <!-- Profil Agent (One-to-One s'il existe) -->
        <div class="card card-flush shadow-sm border border-dashed border-primary">
            <div class="card-header py-4">
                <h3 class="card-title fw-bold text-gray-900">
                    <i class="fas fa-id-card text-primary me-2"></i>Liaison Agent de Terrain
                </h3>
            </div>
            <div class="card-body pt-0">
                @if($user->agent)
                    <div class="d-flex align-items-center mb-4">
                        <div class="symbol symbol-45px symbol-circle bg-light-info text-info fw-bold fs-4 d-flex align-items-center justify-content-center me-3" style="width:45px; height:45px;">
                            <i class="fas fa-user-tie text-info"></i>
                        </div>
                        <div class="d-flex flex-column">
                            <span class="text-gray-900 fw-bold fs-6">{{ $user->agent->personne->prenom }} {{ $user->agent->personne->nom }}</span>
                            <span class="text-muted fs-8">Matricule : {{ $user->agent->matricule }}</span>
                        </div>
                    </div>
                    <div class="separator separator-dashed my-4"></div>
                    <div class="fs-7 text-gray-800">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Fonction :</span>
                            <span class="fw-bold">{{ $user->agent->fonction?->nom ?? 'Non définie' }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Sexe de l'Agent :</span>
                            <span class="fw-bold">{{ $user->agent->sexe ?? 'S/D' }}</span>
                        </div>
                    </div>
                @else
                    <div class="text-center text-muted py-4 fs-7">
                        <i class="fas fa-user-slash text-gray-300 fs-1 mb-2 d-block"></i>
                        Aucun profil Agent n'est lié à ce compte utilisateur municipal.
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Colonne Droite: Détails et matrice des droits -->
    <div class="col-xl-8">
        <!-- Informations personnelles -->
        <div class="card card-flush shadow-sm mb-6">
            <div class="card-header">
                <h3 class="card-title fw-bold text-gray-900">Informations Personnelles du Compte</h3>
            </div>
            <div class="card-body pt-0 text-gray-800">
                <div class="row mb-5 border-bottom pb-3">
                    <label class="col-lg-4 fw-semibold text-muted">Prénom :</label>
                    <div class="col-lg-8 fw-bold fs-6">{{ $user->firstname ?? 'Non défini' }}</div>
                </div>
                <div class="row mb-5 border-bottom pb-3">
                    <label class="col-lg-4 fw-semibold text-muted">Nom de famille :</label>
                    <div class="col-lg-8 fw-bold fs-6">{{ $user->lastname ?? 'Non défini' }}</div>
                </div>
                <div class="row mb-5 border-bottom pb-3">
                    <label class="col-lg-4 fw-semibold text-muted">Adresse Email :</label>
                    <div class="col-lg-8 fw-bold fs-6">{{ $user->email }}</div>
                </div>
                <div class="row mb-5">
                    <label class="col-lg-4 fw-semibold text-muted">Numéro de Téléphone :</label>
                    <div class="col-lg-8 fw-bold fs-6">{{ $user->telephone ?? 'Non défini' }}</div>
                </div>
            </div>
        </div>

        <!-- Matrice de Sécurité (RBAC) -->
        <div class="card card-flush shadow-sm">
            <div class="card-header">
                <h3 class="card-title fw-bold text-gray-900">
                    <i class="fas fa-key text-warning me-2"></i>Matrice d'Habilitations Système (RBAC & Surcharges)
                </h3>
            </div>
            <div class="card-body pt-0">
                <p class="text-muted fs-7 mb-5">Vue consolidée des rôles et des surcharges individuelles prioritaires appliquées à ce compte utilisateur (Surcharges de voter Symfony).</p>

                <!-- Rôles existants -->
                <div class="mb-5">
                    <h5 class="fw-bold fs-6 mb-3 text-gray-800">Rôles d'accès cumulés :</h5>
                    <div class="d-flex flex-wrap gap-2">
                        @forelse($user->roles as $role)
                            <div class="badge badge-light-primary d-flex flex-column align-items-start p-3 rounded w-200px border">
                                <span class="fw-bold text-gray-900 fs-7">{{ $role->name }}</span>
                                <span class="text-muted fs-8 font-monospace mt-1">{{ $role->slug }}</span>
                            </div>
                        @empty
                            <span class="text-muted fs-7">Aucun rôle attribué.</span>
                        @endforelse
                    </div>
                </div>

                <!-- Surcharges utilisateur explicites -->
                <div class="border-top pt-5">
                    <h5 class="fw-bold fs-6 mb-3 text-gray-800">Surcharges de sécurité individuelles (Priorité haute) :</h5>
                    @if($user->permissions->count() > 0)
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed fs-7 gy-3">
                                <thead>
                                    <tr class="text-start text-muted fw-bold fs-8 text-uppercase gs-0">
                                        <th>Permission</th>
                                        <th>Catégorie</th>
                                        <th class="text-center">Surcharge explicite</th>
                                    </tr>
                                </thead>
                                <tbody class="text-gray-600 fw-semibold">
                                    @foreach($user->permissions as $perm)
                                        <tr>
                                            <td>
                                                <div class="fw-bold text-gray-800">{{ $perm->name }}</div>
                                                <span class="text-muted fs-8">{{ $perm->description }}</span>
                                            </td>
                                            <td><span class="badge badge-light-dark">{{ $perm->category ?? 'Général' }}</span></td>
                                            <td class="text-center">
                                                @if($perm->pivot->is_granted)
                                                    <span class="badge bg-light-success text-success py-1 px-3 fs-8"><i class="fas fa-check me-1"></i>ACCORDÉ</span>
                                                @else
                                                    <span class="badge bg-light-danger text-danger py-1 px-3 fs-8"><i class="fas fa-times me-1"></i>EXCLU</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert bg-light-secondary border text-muted py-4 px-5 fs-7 rounded border-dashed text-center my-2">
                            <i class="fas fa-info-circle me-1 text-muted"></i>Aucune surcharge de sécurité n'a été spécifiée pour cet utilisateur. Les droits de ses rôles pivots s'appliquent de manière standard.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
