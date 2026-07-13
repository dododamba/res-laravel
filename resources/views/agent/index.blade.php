@extends('layouts.app')

@section('title', 'Gestion des Agents')

@section('content')
<!--begin::Toolbar-->
<div class="d-flex align-items-center justify-content-between mb-5">
    <div>
        <h1 class="fw-bold text-gray-900 mb-1">Gestion des Agents</h1>
        <span class="text-muted fs-7">Ressources humaines et affectations des agents territoriaux d'enquêtes</span>
    </div>
    <div>
        <a href="{{ route('agent.create') }}" class="btn btn-sm btn-primary">
            <i class="fas fa-plus me-1"></i>Ajouter un Agent
        </a>
    </div>
</div>
<!--end::Toolbar-->

<!--begin::Filters-->
<div class="card card-flush shadow-sm mb-6">
    <div class="card-body py-5">
        <form method="GET" action="{{ route('agent.index') }}" class="row g-4 align-items-end">
            <!-- Recherche textuelle -->
            <div class="col-md-4">
                <label class="form-label fs-7 fw-semibold text-gray-700">Rechercher</label>
                <div class="position-relative">
                    <i class="fas fa-search fs-5 position-absolute ms-4" style="top: 50%; transform: translateY(-50%);"></i>
                    <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-solid ps-11" placeholder="Nom, e-mail, matricule..." />
                </div>
            </div>

            <!-- Filtrer par fonction -->
            <div class="col-md-3">
                <label class="form-label fs-7 fw-semibold text-gray-700">Fonction</label>
                <select name="fonction_id" class="form-select form-select-solid" data-control="select2">
                    <option value="">Toutes les fonctions...</option>
                    @foreach($fonctions as $f)
                        <option value="{{ $f->id }}" {{ request('fonction_id') == $f->id ? 'selected' : '' }}>{{ $f->nom }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Filtrer par statut -->
            <div class="col-md-3">
                <label class="form-label fs-7 fw-semibold text-gray-700">Statut</label>
                <select name="statut" class="form-select form-select-solid">
                    <option value="">Tous les statuts...</option>
                    <option value="actif" {{ request('statut') == 'actif' ? 'selected' : '' }}>Actif</option>
                    <option value="suspendu" {{ request('statut') == 'suspendu' ? 'selected' : '' }}>Suspendu</option>
                    <option value="inactif" {{ request('statut') == 'inactif' ? 'selected' : '' }}>Inactif</option>
                </select>
            </div>

            <!-- Boutons actions -->
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100">
                    Filtrer
                </button>
                @if(request()->anyFilled(['q', 'fonction_id', 'statut']))
                    <a href="{{ route('agent.index') }}" class="btn btn-light w-100">Reset</a>
                @endif
            </div>
        </form>
    </div>
</div>
<!--end::Filters-->

<!--begin::Table Card-->
<div class="card card-flush shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table align-middle table-row-dashed fs-6 gy-5">
                <thead>
                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                        <th class="w-50px pe-2">ID</th>
                        <th class="min-w-100px">Matricule</th>
                        <th class="min-w-175px">Nom & Prénom</th>
                        <th class="min-w-80px">Sexe</th>
                        <th class="min-w-150px">Fonction</th>
                        <th class="min-w-120px text-center">Compte Accès</th>
                        <th class="min-w-100px text-center">Statut</th>
                        <th class="text-end min-w-120px">Actions</th>
                    </tr>
                </thead>
                <tbody class="text-gray-600 fw-semibold">
                    @forelse($agents as $agent)
                        <tr>
                            <td>
                                <span class="text-gray-400 fs-8">{{ $loop->iteration }}</span>
                            </td>
                            <td>
                                <span class="badge badge-light-dark font-monospace fw-bold fs-7">{{ $agent->matricule }}</span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="symbol symbol-35px symbol-circle bg-light-primary text-primary fw-bold d-flex align-items-center justify-content-center me-3" style="width:35px; height:35px;">
                                        {{ substr($agent->personne->prenom, 0, 1) }}{{ substr($agent->personne->nom, 0, 1) }}
                                    </div>
                                    <div class="d-flex flex-column">
                                        <a href="{{ route('agent.show', $agent) }}" class="text-gray-900 text-hover-primary fw-bold">
                                            {{ $agent->personne->prenom }} {{ $agent->personne->nom }}
                                        </a>
                                        <span class="text-muted fs-8">{{ $agent->personne->email }}</span>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $agent->sexe == 'M' ? 'Masculin' : 'Féminin' }}</td>
                            <td>
                                <span class="badge badge-light-info py-2 px-3 fw-bold fs-8">{{ $agent->fonction?->nom ?? 'Non définie' }}</span>
                            </td>
                            <td class="text-center">
                                @if($agent->user)
                                    <span class="badge badge-light-success py-1 px-3 fs-8"><i class="fas fa-check-circle text-success me-1"></i>Provisionné</span>
                                @else
                                    <span class="badge badge-light-warning py-1 px-3 fs-8"><i class="fas fa-exclamation-triangle text-warning me-1"></i>Non lié</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($agent->statut->value == 'actif')
                                    <span class="badge bg-success text-white py-1 px-3 fs-8">Actif</span>
                                @elseif($agent->statut->value == 'suspendu')
                                    <span class="badge bg-warning text-dark py-1 px-3 fs-8">Suspendu</span>
                                @else
                                    <span class="badge bg-secondary text-white py-1 px-3 fs-8">Inactif</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('agent.show', $agent) }}" class="btn btn-icon btn-light btn-active-color-primary btn-sm" title="Consulter">
                                        <i class="fas fa-eye fs-6"></i>
                                    </a>
                                    <a href="{{ route('agent.edit', $agent) }}" class="btn btn-icon btn-light btn-active-color-primary btn-sm" title="Modifier">
                                        <i class="fas fa-pencil-alt fs-6"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-10 text-muted">
                                <i class="fas fa-user-slash fs-2x mb-3 d-block"></i>
                                Aucun agent municipal d'enquêtes trouvé.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-end mt-5">
            {{ $agents->links() }}
        </div>
    </div>
</div>
<!--end::Table Card-->
@endsection
