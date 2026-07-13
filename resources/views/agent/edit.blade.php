@extends('layouts.app')

@section('title', "Modifier l'Agent : " . $agent->personne->prenom)

@section('content')
<div class="card card-flush shadow-sm">
    <div class="card-header py-5">
        <div class="card-title d-flex flex-column">
            <h3 class="fw-bold text-gray-900 mb-1">Modifier l'Agent : {{ $agent->personne->prenom }} {{ $agent->personne->nom }}</h3>
            <span class="text-muted fs-7">Modification de la fiche d'identité et des paramètres techniques</span>
        </div>
        <div class="card-toolbar">
            <a href="{{ route('agent.show', $agent) }}" class="btn btn-sm btn-light">
                <i class="fas fa-arrow-left me-1"></i>Retour aux détails
            </a>
        </div>
    </div>

    <div class="card-body">
        <form method="POST" action="{{ route('agent.update', $agent) }}" class="form">
            @csrf
            @method('PUT')

            <!-- Section 1: Informations Civiles -->
            <div class="mb-8">
                <h5 class="text-primary fw-bold mb-4"><i class="fas fa-user-circle text-primary me-2"></i>1. Identité Civile</h5>
                <div class="separator separator-dashed border-primary opacity-25 mb-5"></div>

                <div class="row g-9 mb-5">
                    <!-- Prénom -->
                    <div class="col-md-6 fv-row">
                        <label class="required fs-6 fw-semibold mb-2">Prénom</label>
                        <input type="text" name="prenom" class="form-control form-control-solid @error('prenom') is-invalid @enderror" value="{{ old('prenom', $agent->personne->prenom) }}" required />
                        @error('prenom')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Nom -->
                    <div class="col-md-6 fv-row">
                        <label class="required fs-6 fw-semibold mb-2">Nom de famille</label>
                        <input type="text" name="nom" class="form-control form-control-solid @error('nom') is-invalid @enderror" value="{{ old('nom', $agent->personne->nom) }}" required />
                        @error('nom')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row g-9">
                    <!-- Email Civile -->
                    <div class="col-md-6 fv-row">
                        <label class="required fs-6 fw-semibold mb-2">Adresse Email</label>
                        <input type="email" name="email" class="form-control form-control-solid @error('email') is-invalid @enderror" value="{{ old('email', $agent->personne->email) }}" required />
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Téléphone Principal -->
                    <div class="col-md-6 fv-row">
                        <label class="required fs-6 fw-semibold mb-2">Numéro de Téléphone principal</label>
                        <input type="text" name="telephone" class="form-control form-control-solid @error('telephone') is-invalid @enderror" value="{{ old('telephone', $agent->personne->telephone) }}" required />
                        @error('telephone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Section 2: Informations Administratives -->
            <div class="mb-8">
                <h5 class="text-primary fw-bold mb-4"><i class="fas fa-briefcase text-primary me-2"></i>2. Informations Administratives & Techniques</h5>
                <div class="separator separator-dashed border-primary opacity-25 mb-5"></div>

                <div class="row g-9 mb-5">
                    <!-- Matricule -->
                    <div class="col-md-4 fv-row">
                        <label class="required fs-6 fw-semibold mb-2">Matricule</label>
                        <input type="text" name="matricule" class="form-control form-control-solid @error('matricule') is-invalid @enderror" value="{{ old('matricule', $agent->matricule) }}" required />
                        @error('matricule')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Fonction -->
                    <div class="col-md-4 fv-row">
                        <label class="required fs-6 fw-semibold mb-2">Fonction d'Affectation</label>
                        <select name="fonction_id" class="form-select form-select-solid @error('fonction_id') is-invalid @enderror" data-control="select2" required>
                            @foreach($fonctions as $f)
                                <option value="{{ $f->id }}" {{ old('fonction_id', $agent->fonction_id) == $f->id ? 'selected' : '' }}>{{ $f->nom }}</option>
                            @endforeach
                        </select>
                        @error('fonction_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Sexe -->
                    <div class="col-md-4 fv-row">
                        <label class="required fs-6 fw-semibold mb-2">Sexe de l'Agent</label>
                        <select name="sexe" class="form-select form-select-solid @error('sexe') is-invalid @enderror" required>
                            <option value="M" {{ old('sexe', $agent->sexe) == 'M' ? 'selected' : '' }}>Masculin</option>
                            <option value="F" {{ old('sexe', $agent->sexe) == 'F' ? 'selected' : '' }}>Féminin</option>
                        </select>
                        @error('sexe')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row g-9 mb-5">
                    <!-- CNI -->
                    <div class="col-md-4 fv-row">
                        <label class="fs-6 fw-semibold mb-2">Numéro CNI / Passeport</label>
                        <input type="text" name="cni" class="form-control form-control-solid @error('cni') is-invalid @enderror" value="{{ old('cni', $agent->cni) }}" />
                        @error('cni')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Date de Naissance -->
                    <div class="col-md-4 fv-row">
                        <label class="fs-6 fw-semibold mb-2">Date de Naissance</label>
                        <input type="date" name="date_naissance" class="form-control form-control-solid @error('date_naissance') is-invalid @enderror" value="{{ old('date_naissance', $agent->date_naissance ? \Carbon\Carbon::parse($agent->date_naissance)->format('Y-m-d') : '') }}" />
                        @error('date_naissance')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Lieu de Naissance -->
                    <div class="col-md-4 fv-row">
                        <label class="fs-6 fw-semibold mb-2">Lieu de Naissance</label>
                        <input type="text" name="lieu_naissance" class="form-control form-control-solid @error('lieu_naissance') is-invalid @enderror" value="{{ old('lieu_naissance', $agent->lieu_naissance) }}" />
                        @error('lieu_naissance')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row g-9 mb-8">
                    <!-- Couleur / Icône / Ordre - Non présents sur l'Agent mais on a le Statut ! -->
                    <div class="col-md-4 fv-row">
                        <label class="required fs-6 fw-semibold mb-2">Statut de l'Agent</label>
                        <select name="statut" class="form-select form-select-solid @error('statut') is-invalid @enderror" required>
                            <option value="actif" {{ old('statut', $agent->statut->value) == 'actif' ? 'selected' : '' }}>Actif</option>
                            <option value="suspendu" {{ old('statut', $agent->statut->value) == 'suspendu' ? 'selected' : '' }}>Suspendu / Arrêt temporaire</option>
                            <option value="inactif" {{ old('statut', $agent->statut->value) == 'inactif' ? 'selected' : '' }}>Inactif</option>
                        </select>
                        @error('statut')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Nationalité -->
                    <div class="col-md-4 fv-row">
                        <label class="fs-6 fw-semibold mb-2">Nationalité</label>
                        <input type="text" name="nationalite" class="form-control form-control-solid @error('nationalite') is-invalid @enderror" value="{{ old('nationalite', $agent->nationalite) }}" />
                        @error('nationalite')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Téléphone Secondaire -->
                    <div class="col-md-4 fv-row">
                        <label class="fs-6 fw-semibold mb-2">Téléphone Secondaire</label>
                        <input type="text" name="telephone_secondaire" class="form-control form-control-solid @error('telephone_secondaire') is-invalid @enderror" value="{{ old('telephone_secondaire', $agent->telephone_secondaire) }}" />
                        @error('telephone_secondaire')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row g-9 mb-5">
                    <!-- Profession précédente -->
                    <div class="col-md-4 fv-row">
                        <label class="fs-6 fw-semibold mb-2">Profession</label>
                        <input type="text" name="profession" class="form-control form-control-solid @error('profession') is-invalid @enderror" value="{{ old('profession', $agent->profession) }}" />
                        @error('profession')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Adresse de résidence -->
                    <div class="col-md-8 fv-row">
                        <label class="fs-6 fw-semibold mb-2">Adresse de résidence</label>
                        <input type="text" name="adresse" class="form-control form-control-solid @error('adresse') is-invalid @enderror" value="{{ old('adresse', $agent->adresse) }}" />
                        @error('adresse')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Section 3: Notes & Observations -->
            <div class="mb-8">
                <h5 class="text-primary fw-bold mb-4"><i class="fas fa-sticky-note text-primary me-2"></i>3. Observations terrain & Détails RH</h5>
                <div class="separator separator-dashed border-primary opacity-25 mb-5"></div>

                <div class="fv-row">
                    <label class="fs-6 fw-semibold mb-2">Observations / Remarques</label>
                    <textarea name="observations" class="form-control form-control-solid @error('observations') is-invalid @enderror" rows="3">{{ old('observations', $agent->observations) }}</textarea>
                    @error('observations')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="d-flex justify-content-end gap-5">
                <a href="{{ route('agent.show', $agent) }}" class="btn btn-light">Annuler</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i>Enregistrer les Modifications
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
