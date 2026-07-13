@extends('layouts.app')

@section('title', 'Nouveau Carré')

@section('content')
<div class="card card-flush shadow-sm">
    <div class="card-header py-5">
        <div class="card-title d-flex flex-column">
            <h3 class="fw-bold text-gray-900 mb-1">Créer un Nouveau Carré (Bloc)</h3>
            <span class="text-muted fs-7">Découpage urbain de niveau 2 (Sous-division territoriale)</span>
        </div>
        <div class="card-toolbar">
            <a href="{{ route('carre.index') }}" class="btn btn-sm btn-light">
                <i class="fas fa-arrow-left me-2"></i>Retour à la liste
            </a>
        </div>
    </div>

    <div class="card-body">
        <form method="POST" action="{{ route('carre.store') }}" class="form">
            @csrf

            <div class="row g-9 mb-5">
                <!-- Nom du Carré -->
                <div class="col-md-5 fv-row">
                    <label class="required fs-6 fw-semibold mb-2">Nom du Carré (ex: Bloc A, Carré 12...)</label>
                    <input type="text" name="nom" class="form-control form-control-solid @error('nom') is-invalid @enderror" value="{{ old('nom') }}" placeholder="ex: Carré 14, Bloc C..." required />
                    @error('nom')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Code -->
                <div class="col-md-3 fv-row">
                    <label class="fs-6 fw-semibold mb-2">Code Carré</label>
                    <input type="text" name="code" class="form-control form-control-solid @error('code') is-invalid @enderror" value="{{ old('code') }}" placeholder="ex: C-14" />
                    @error('code')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Quartier Parent -->
                <div class="col-md-4 fv-row">
                    <label class="required fs-6 fw-semibold mb-2">Quartier de Rattachement</label>
                    <select name="quartier_id" class="form-select form-select-solid @error('quartier_id') is-invalid @enderror" data-control="select2" required>
                        <option value="">Sélectionner un quartier parent...</option>
                        @foreach($quartiers as $q)
                            <option value="{{ $q->id }}" {{ (old('quartier_id') ?? $selectedQuartierId) == $q->id ? 'selected' : '' }}>
                                {{ $q->nom }} ({{ $q->code ?? 'S/C' }})
                            </option>
                        @endforeach
                    </select>
                    @error('quartier_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <!-- Description -->
            <div class="fv-row mb-5">
                <label class="fs-6 fw-semibold mb-2">Description / Notes topographiques</label>
                <textarea name="description" class="form-control form-control-solid @error('description') is-invalid @enderror" rows="3" placeholder="Saisissez des limites topographiques, points de repères ou notes de terrain...">{{ old('description') }}</textarea>
                @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="row g-9 mb-8">
                <!-- Couleur -->
                <div class="col-md-4 fv-row">
                    <label class="fs-6 fw-semibold mb-2">Couleur Hexadécimale</label>
                    <div class="d-flex align-items-center gap-2">
                        <input type="color" class="form-control form-control-color w-60px form-control-solid" id="colorPicker" value="{{ old('couleur', '#10B981') }}" oninput="document.getElementById('colorInput').value = this.value">
                        <input type="text" name="couleur" id="colorInput" class="form-control form-control-solid @error('couleur') is-invalid @enderror" value="{{ old('couleur', '#10B981') }}" placeholder="#10B981" pattern="^#([A-Fa-f0-9]{6})$" oninput="document.getElementById('colorPicker').value = this.value">
                    </div>
                    @error('couleur')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Icône -->
                <div class="col-md-4 fv-row">
                    <label class="fs-6 fw-semibold mb-2">Icône Bootstrap</label>
                    <input type="text" name="icone" class="form-control form-control-solid @error('icone') is-invalid @enderror" value="{{ old('icone', 'bi-grid') }}" placeholder="ex: bi-grid, bi-bounding-box..." />
                    @error('icone')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Ordre d'affichage -->
                <div class="col-md-4 fv-row">
                    <label class="fs-6 fw-semibold mb-2">Ordre d'affichage</label>
                    <input type="number" name="ordre_affichage" class="form-control form-control-solid @error('ordre_affichage') is-invalid @enderror" value="{{ old('ordre_affichage', 0) }}" min="0" />
                    @error('ordre_affichage')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <!-- Section 2 : Supervision du Carré (Chef de Carré) -->
            <div class="mb-8 p-5 bg-light-warning rounded border border-warning border-dashed">
                <h5 class="text-warning fw-bold mb-3">
                    <i class="fas fa-users-cog text-warning me-2"></i>2. Assignation du Superviseur (Chef de Carré)
                </h5>
                <p class="text-muted fs-7 mb-4">Attribuez un agent en tant que Chef de Carré actif. Il sera responsable du contrôle de terrain des habitations et des opérateurs de ce bloc (Règle d'affectation Symfony).</p>

                <div class="fv-row">
                    <label class="fs-6 fw-semibold mb-2">Chef de Carré à affecter</label>
                    <select name="chef_carre_id" class="form-select form-select-solid @error('chef_carre_id') is-invalid @enderror" data-control="select2">
                        <option value="">Sélectionner un agent de terrain...</option>
                        @foreach($agents as $agent)
                            <option value="{{ $agent->id }}" {{ old('chef_carre_id') == $agent->id ? 'selected' : '' }}>
                                {{ $agent->personne->prenom }} {{ $agent->personne->nom }} (Matricule : {{ $agent->matricule }})
                            </option>
                        @endforeach
                    </select>
                    @error('chef_carre_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="d-flex justify-content-end gap-5">
                <a href="{{ route('carre.index') }}" class="btn btn-light">Annuler</a>
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-check me-1"></i>Enregistrer le Carré
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
