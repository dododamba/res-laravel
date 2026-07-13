@extends('layouts.app')

@section('title', isset($isSelfEdit) && $isSelfEdit ? 'Paramètres de mon Compte' : "Modifier l'Utilisateur : " . $user->firstname)

@section('content')
<!--begin::Toolbar-->
<div class="d-flex align-items-center justify-content-between mb-5">
    <div>
        <h1 class="fw-bold text-gray-900 mb-1">
            {{ isset($isSelfEdit) && $isSelfEdit ? 'Paramètres de mon Compte' : "Modifier l'Habilitation : " . $user->firstname . ' ' . $user->lastname }}
        </h1>
        <span class="text-muted fs-7">
            {{ isset($isSelfEdit) && $isSelfEdit ? 'Mise à jour de vos identifiants, contact et photo de profil' : 'Modification du compte système, mot de passe et rôles RBAC' }}
        </span>
    </div>
    <div>
        @if(isset($isSelfEdit) && $isSelfEdit)
            <a href="{{ route('profile.show') }}" class="btn btn-sm btn-light">
                <i class="fas fa-arrow-left me-1"></i>Retour au profil
            </a>
        @else
            <a href="{{ route('user.show', $user) }}" class="btn btn-sm btn-light">
                <i class="fas fa-arrow-left me-1"></i>Retour aux détails
            </a>
        @endif
    </div>
</div>
<!--end::Toolbar-->

<div class="card card-flush shadow-sm">
    <div class="card-header py-5">
        <h3 class="card-title fw-bold text-gray-900">
            {{ isset($isSelfEdit) && $isSelfEdit ? 'Modifier mes Paramètres' : 'Détails et rôles de l\'utilisateur' }}
        </h3>
    </div>
    
    <div class="card-body">
        <form method="POST" action="{{ isset($isSelfEdit) && $isSelfEdit ? route('profile.update') : route('user.update', $user) }}" enctype="multipart/form-data" class="form">
            @csrf
            @method('PUT')

            <!-- Section 1 : Informations civiles -->
            <div class="mb-8">
                <h5 class="text-primary fw-bold mb-4"><i class="fas fa-user me-2"></i>1. Informations d'identité</h5>
                <div class="row g-9">
                    <!-- Prénom -->
                    <div class="col-md-6 fv-row">
                        <label class="fs-6 fw-semibold mb-2">Prénom</label>
                        <input type="text" name="firstname" class="form-control form-control-solid @error('firstname') is-invalid @enderror" value="{{ old('firstname', $user->firstname) }}" required />
                        @error('firstname')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <!-- Nom -->
                    <div class="col-md-6 fv-row">
                        <label class="fs-6 fw-semibold mb-2">Nom de famille</label>
                        <input type="text" name="lastname" class="form-control form-control-solid @error('lastname') is-invalid @enderror" value="{{ old('lastname', $user->lastname) }}" required />
                        @error('lastname')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Section 2 : Contacts et statuts -->
            <div class="mb-8">
                <h5 class="text-primary fw-bold mb-4"><i class="fas fa-envelope me-2"></i>2. Contacts et Paramètres</h5>
                <div class="row g-9 mb-5">
                    <!-- Email -->
                    <div class="col-md-6 fv-row">
                        <label class="required fs-6 fw-semibold mb-2">Adresse Email</label>
                        <input type="email" name="email" class="form-control form-control-solid @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" required />
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <!-- Téléphone -->
                    <div class="col-md-6 fv-row">
                        <label class="fs-6 fw-semibold mb-2">Numéro de Téléphone</label>
                        <input type="text" name="telephone" class="form-control form-control-solid @error('telephone') is-invalid @enderror" value="{{ old('telephone', $user->telephone) }}" />
                        @error('telephone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                @if(!isset($isSelfEdit) || !$isSelfEdit)
                    <div class="row g-9">
                        <!-- Statut du compte -->
                        <div class="col-md-6 fv-row">
                            <label class="required fs-6 fw-semibold mb-2">Statut du Compte</label>
                            <select name="status" class="form-select form-select-solid @error('status') is-invalid @enderror" required>
                                <option value="active" {{ old('status', $user->status ?? 'active') == 'active' ? 'selected' : '' }}>Actif / Opérationnel</option>
                                <option value="pending" {{ old('status', $user->status ?? 'active') == 'pending' ? 'selected' : '' }}>En attente de validation</option>
                                <option value="suspended" {{ old('status', $user->status ?? 'active') == 'suspended' ? 'selected' : '' }}>Suspendu / Bloqué</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Activé / Désactivé Switch -->
                        <div class="col-md-6 d-flex align-items-center pt-8">
                            <input type="hidden" name="is_active" value="0">
                            <div class="form-check form-check-solid form-switch form-check-custom">
                                <input class="form-check-input w-45px h-30px @error('is_active') is-invalid @enderror" type="checkbox" name="is_active" value="1" id="is_active_switch" {{ old('is_active', $user->is_active) ? 'checked' : '' }} />
                                <label class="form-check-label fw-bold text-gray-800 fs-7 ms-3" for="is_active_switch">Compte activé (Autoriser la connexion)</label>
                            </div>
                            @error('is_active')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                @else
                    <!-- Photo de profil (Avatar) pour l'édition personnelle -->
                    <div class="row g-9">
                        <div class="col-md-6 fv-row">
                            <label class="fs-6 fw-semibold mb-2">Photo de Profil (Avatar) (Max 2Mo - JPEG/PNG)</label>
                            @if($user->avatar)
                                <div class="mb-3 symbol symbol-100px d-block">
                                    <img src="{{ asset('uploads/avatars/' . $user->avatar) }}" alt="Avatar" class="rounded border" style="object-fit: cover; width:100px; height:100px;" />
                                    <span class="d-block text-muted fs-8 mt-1">Avatar actuel</span>
                                </div>
                            @endif
                            <input type="file" name="avatar" class="form-control form-control-solid @error('avatar') is-invalid @enderror" accept="image/*" />
                            @error('avatar')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                @endif
            </div>

            <!-- Section 3 : Habilitations de sécurité -->
            <div class="mb-8 p-5 bg-light-primary rounded border border-primary border-dashed">
                <h5 class="text-primary fw-bold mb-3">
                    <i class="fas fa-shield-alt text-primary me-2"></i>3. Rôles et Habilitations d'Accès (RBAC)
                </h5>
                
                @if(!isset($isSelfEdit) || !$isSelfEdit)
                    <p class="text-muted fs-7 mb-4">Attribuez un ou plusieurs rôles d'accès cumulés pour définir le périmètre fonctionnel de l'utilisateur (Rôles pivots de sécurité Symfony).</p>

                    <div class="fv-row">
                        <label class="required fs-6 fw-semibold mb-2">Rôles d'Accès (Sélection multiple autorisée)</label>
                        <select name="roles[]" class="form-select form-select-solid @error('roles') is-invalid @enderror" data-control="select2" data-placeholder="Choisir des rôles..." multiple required>
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}" {{ in_array($role->id, old('roles', $user->roles->pluck('id')->toArray())) ? 'selected' : '' }}>
                                    {{ $role->name }} ({{ str_replace('ROLE_', '', $role->slug) }})
                                </option>
                            @endforeach
                        </select>
                        @error('roles')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                @else
                    <p class="text-muted fs-7 mb-3">Vos rôles d'accès système actuels (En lecture seule pour des raisons de sécurité).</p>
                    <div class="d-flex flex-wrap gap-2">
                        @forelse($user->roles as $role)
                            <span class="badge badge-light-primary py-2 px-4 fs-7 fw-bold border">{{ $role->name }}</span>
                        @empty
                            <span class="text-muted fs-7">Aucun rôle attribué.</span>
                        @endforelse
                    </div>
                @endif
            </div>

            <!-- Section 4 : Sécurité (Modification Mot de Passe optionnelle) -->
            <div class="mb-8">
                <h5 class="text-danger fw-bold mb-4"><i class="fas fa-lock text-danger me-2"></i>4. Sécurité (Modification optionnelle du Mot de Passe)</h5>
                <p class="text-muted fs-7 mb-4">Laissez les champs ci-dessous **vides** si vous ne souhaitez pas modifier votre mot de passe de connexion.</p>

                <div class="row g-9">
                    <!-- Nouveau mot de passe -->
                    <div class="col-md-6 fv-row">
                        <label class="fs-6 fw-semibold mb-2">Nouveau mot de passe</label>
                        <input type="password" name="password" class="form-control form-control-solid @error('password') is-invalid @enderror" placeholder="Saisir un nouveau mot de passe" autocomplete="new-password" />
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Confirmation -->
                    <div class="col-md-6 fv-row">
                        <label class="fs-6 fw-semibold mb-2">Confirmer le mot de passe</label>
                        <input type="password" name="password_confirmation" class="form-control form-control-solid" placeholder="Ressaisir le mot de passe" />
                    </div>
                </div>
            </div>

            <!-- Validation Actions -->
            <div class="d-flex justify-content-end gap-5 pt-4">
                @if(!isset($is_profile_edit) && !isset($is_profile) && (!isset($isSelfEdit) || !$isSelfEdit))
                    <a href="{{ route('user.show', $user) }}" class="btn btn-light">Annuler</a>
                @else
                    <a href="{{ route('profile.show') }}" class="btn btn-light">Annuler</a>
                @endif
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i>Enregistrer les Modifications
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
