@extends('layouts.app')

@section('title', "Journaux d'Audit Sécurité")

@section('content')
<!--begin::Toolbar-->
<div class="d-flex align-items-center justify-content-between mb-5">
    <div>
        <h1 class="fw-bold text-gray-900 mb-1">Journaux d'Audit & Sécurité</h1>
        <span class="text-muted fs-7">Traçabilité complète des transactions, connexions et modifications (Journal d'activités)</span>
    </div>
</div>
<!--end::Toolbar-->

<!--begin::Card-->
<div class="card card-flush shadow-sm">
    <!--begin::Card Header-->
    <div class="card-header align-items-center py-5 gap-2 gap-md-5">
        <div class="card-title">
            <form method="GET" action="{{ route('audit.index') }}" class="d-flex align-items-center position-relative my-1">
                <i class="fas fa-search fs-4 position-absolute ms-4 text-gray-500" style="top: 50%; transform: translateY(-50%);"></i>
                <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-solid w-300px ps-12" placeholder="Rechercher une action, e-mail..." />
                @if(request()->filled('q'))
                    <a href="{{ route('audit.index') }}" class="btn btn-sm btn-light position-absolute end-0 me-3" style="top: 50%; transform: translateY(-50%);">
                        <i class="fas fa-times"></i>
                    </a>
                @endif
            </form>
        </div>
    </div>
    <!--end::Card Header-->

    <!--begin::Card Body-->
    <div class="card-body pt-0">
        <div class="table-responsive">
            <table class="table align-middle table-row-dashed fs-6 gy-5">
                <thead>
                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                        <th class="min-w-150px">Date & Heure</th>
                        <th class="min-w-200px">Action / Événement</th>
                        <th class="min-w-175px">Opérateur</th>
                        <th class="min-w-100px">Adresse IP</th>
                        <th class="min-w-150px">Navigateur & OS</th>
                        <th class="min-w-100px text-center">Résultat</th>
                    </tr>
                </thead>
                <tbody class="text-gray-600 fw-semibold">
                    @forelse($logs as $log)
                        <tr>
                            <td>
                                <span class="text-gray-800 fw-bold fs-7">{{ $log->created_at->format('d/m/Y H:i:s') }}</span>
                            </td>
                            <td>
                                <div class="d-flex flex-column">
                                    <strong class="text-gray-900 fs-7">{{ $log->action }}</strong>
                                    @if($log->object_class)
                                        <span class="text-muted fs-8 font-monospace mt-1">
                                            Objet : {{ class_basename($log->object_class) }} (#{{ $log->object_id }})
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-light-dark py-2 px-3 fs-7 fw-bold">{{ $log->user_identifier ?? 'Visiteur anonyme' }}</span>
                            </td>
                            <td>
                                <span class="badge badge-light-secondary fs-7">{{ $log->ip_address }}</span>
                            </td>
                            <td>
                                <div class="d-flex flex-column">
                                    <span class="text-gray-800 fs-7"><i class="fas fa-desktop text-muted me-1 fs-8"></i>{{ $log->browser ?? 'Inconnu' }}</span>
                                    <span class="text-muted fs-8"><i class="fas fa-cog text-muted me-1 fs-9"></i>{{ $log->os ?? 'Inconnu' }}</span>
                                </div>
                            </td>
                            <td class="text-center">
                                @if(($log->result ?? 'success') == 'success' || ($log->result ?? 'success') == 'réussi')
                                    <span class="badge bg-light-success text-success py-1 px-3 fs-8"><i class="fas fa-check-circle text-success me-1"></i>Réussi</span>
                                @else
                                    <span class="badge bg-light-danger text-danger py-1 px-3 fs-8"><i class="fas fa-times-circle text-danger me-1"></i>Échoué</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-10 text-muted">
                                <i class="fas fa-history fs-2x mb-3 d-block"></i>
                                Aucun journal d'audit enregistré.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="d-flex justify-content-end mt-5">
            {{ $logs->links() }}
        </div>
    </div>
    <!--end::Card Body-->
</div>
<!--end::Card-->
@endsection
