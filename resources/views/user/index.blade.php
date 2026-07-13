@extends('layouts.app')

@section('title', 'Gestion des Utilisateurs')

@section('content')
<!--begin::Toolbar-->
<div class="d-flex align-items-center justify-content-between mb-5">
    <div>
        <h1 class="fw-bold text-gray-900 mb-1">Comptes Utilisateurs</h1>
        <span class="text-muted fs-7">Gestion des habilitations RBAC et des accès système</span>
    </div>
</div>
<!--end::Toolbar-->

<!--begin::Card-->
<div class="card card-flush shadow-sm">
    <!--begin::Card Header-->
    <div class="card-header align-items-center py-5 gap-2 gap-md-5">
        <div class="card-title">
            <!--begin::Search and Filters-->
            <form method="GET" action="{{ route('user.index') }}" class="d-flex align-items-center gap-3">
                <div class="position-relative my-1">
                    <i class="fas fa-search fs-4 position-absolute ms-4 text-gray-500" style="top: 50%; transform: translateY(-50%);"></i>
                    <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-solid w-250px ps-12" placeholder="Rechercher par nom, e-mail..." />
                </div>
                
                <select name="role" class="form-select form-select-solid w-200px" onchange="this.form.submit()">
                    <option value="">Tous les rôles...</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->slug }}" {{ request('role') == $role->slug ? 'selected' : '' }}>
                            {{ $role->name }}
                        </option>
                    @endforeach
                </select>
                
                @if(request()->filled('q') || request()->filled('role'))
                    <a href="{{ route('user.index') }}" class="btn btn-light btn-sm">Réinitialiser</a>
                @endif
            </form>
            <!--end::Search and Filters-->
        </div>
    </div>
    <!--end::Card Header-->

    <!--begin::Card Body-->
    <div class="card-body pt-0">
        <div class="table-responsive">
            <table class="table align-middle table-row-dashed fs-6 gy-5">
                <thead>
                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                        <th class="min-w-200px">Utilisateur</th>
                        <th class="min-w-125px">Téléphone</th>
                        <th class="min-w-150px">Rôles d'Accès</th>
                        <th class="min-w-100px text-center">Vérifié</th>
                        <th class="min-w-100px text-center">Statut</th>
                        <th class="text-end min-w-100px">Actions</th>
                    </tr>
                </thead>
                <tbody class="text-gray-600 fw-semibold">
                    @forelse($users as $user)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="symbol symbol-40px symbol-circle me-3 bg-light-primary text-primary d-flex align-items-center justify-content-center fw-bold fs-5" style="width: 40px; height: 40px;">
                                        {{ substr($user->firstname ?? 'U', 0, 1) }}{{ substr($user->lastname ?? 'S', 0, 1) }}
                                    </div>
                                    <div class="d-flex flex-column">
                                        <a href="{{ route('user.show', $user) }}" class="text-gray-900 text-hover-primary fs-6 fw-bold">
                                            {{ $user->firstname }} {{ $user->lastname }}
                                        </a>
                                        <span class="text-muted fs-7">{{ $user->email }}</span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="text-gray-700 fs-7">{{ $user->telephone ?? '-' }}</span>
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    @forelse($user->roles as $role)
                                        <span class="badge badge-light-primary fs-8 fw-bold">{{ str_replace('ROLE_', '', $role->slug) }}</span>
                                    @empty
                                        <span class="text-muted fs-8">- Aucun -</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="text-center">
                                @if($user->is_verified)
                                    <span class="badge badge-light-success py-2 px-3 fs-8"><i class="fas fa-check-circle text-success me-1"></i>Oui</span>
                                @else
                                    <span class="badge badge-light-warning py-2 px-3 fs-8"><i class="fas fa-exclamation-triangle text-warning me-1"></i>Non</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if(($user->status ?? 'active') == 'active')
                                    <span class="badge bg-success text-white py-1 px-3 fs-8">Actif</span>
                                @elseif(($user->status ?? 'active') == 'suspended')
                                    <span class="badge bg-danger text-white py-1 px-3 fs-8">Suspendu</span>
                                @else
                                    <span class="badge bg-warning text-dark py-1 px-3 fs-8">En attente</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('user.show', $user) }}" class="btn btn-icon btn-light btn-active-color-primary btn-sm" title="Détails">
                                        <i class="fas fa-eye fs-6"></i>
                                    </a>
                                    <a href="{{ route('user.edit', $user) }}" class="btn btn-icon btn-light btn-active-color-primary btn-sm" title="Modifier">
                                        <i class="fas fa-pencil-alt fs-6"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-10 text-muted">
                                <i class="fas fa-users fs-2x mb-3 d-block"></i>
                                Aucun compte utilisateur trouvé.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="d-flex justify-content-end mt-5">
            {{ $users->links() }}
        </div>
    </div>
    <!--end::Card Body-->
</div>
<!--end::Card-->
@endsection
