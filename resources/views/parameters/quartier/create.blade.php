@extends('layouts.app')

@section('title', 'Nouveau Quartier')

@section('content')
<div class="card card-flush shadow-sm">
    <div class="card-header py-5">
        <div class="card-title d-flex flex-column">
            <h3 class="fw-bold text-gray-900 mb-1">Créer un Nouveau Quartier</h3>
            <span class="text-muted fs-7">Définition d'une subdivision territoriale administrative</span>
        </div>
        <div class="card-toolbar">
            <a href="{{ route('quartier.index') }}" class="btn btn-sm btn-light">
                <i class="fas fa-arrow-left me-2"></i>Retour à la liste
            </a>
        </div>
    </div>

    <div class="card-body">
        <form method="POST" action="{{ route('quartier.store') }}" class="form">
            @csrf

            <div class="row g-9 mb-5">
                <!-- Nom du Quartier -->
                <div class="col-md-8 fv-row">
                    <label class="required fs-6 fw-semibold mb-2">Nom du Quartier</label>
                    <input type="text" name="nom" class="form-control form-control-solid @error('nom') is-invalid @enderror" value="{{ old('nom') }}" placeholder="ex: Sabangali, Amriguebe..." required />
                    @error('nom')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Code Administratif -->
                <div class="col-md-4 fv-row">
                    <label class="fs-6 fw-semibold mb-2">Code Quartier</label>
                    <input type="text" name="code" class="form-control form-control-solid @error('code') is-invalid @enderror" value="{{ old('code') }}" placeholder="ex: Q-SAB" />
                    @error('code')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <!-- Description -->
            <div class="fv-row mb-5">
                <label class="fs-6 fw-semibold mb-2">Description / Notes géographiques</label>
                <textarea name="description" class="form-control form-control-solid @error('description') is-invalid @enderror" rows="3" placeholder="Saisissez une description optionnelle pour ce quartier...">{{ old('description') }}</textarea>
                @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="row g-9 mb-8">
                <!-- Couleur -->
                <div class="col-md-4 fv-row">
                    <label class="fs-6 fw-semibold mb-2">Couleur Hexadécimale</label>
                    <div class="d-flex align-items-center gap-2">
                        <input type="color" class="form-control form-control-color w-60px form-control-solid" id="colorPicker" value="{{ old('couleur', '#7239EA') }}" title="Choisir une couleur" oninput="document.getElementById('colorInput').value = this.value">
                        <input type="text" name="couleur" id="colorInput" class="form-control form-control-solid @error('couleur') is-invalid @enderror" value="{{ old('couleur', '#7239EA') }}" placeholder="#7239EA" pattern="^#([A-Fa-f0-9]{6})$" oninput="document.getElementById('colorPicker').value = this.value">
                    </div>
                    @error('couleur')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Icône -->
                <div class="col-md-4 fv-row">
                    <label class="fs-6 fw-semibold mb-2">Icône Bootstrap</label>
                    <input type="text" name="icone" class="form-control form-control-solid @error('icone') is-invalid @enderror" value="{{ old('icone', 'bi-geo-alt') }}" placeholder="ex: bi-geo-alt, bi-house..." />
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

            <!-- Section 2 : Assigner le Délégué Communal (Chef de Quartier) -->
            <div class="mb-8 p-5 bg-light-primary rounded border border-primary border-dashed">
                <h5 class="text-primary fw-bold mb-3">
                    <i class="fas fa-user-shield text-primary me-2"></i>2. Assignation du Délégué Communal (Chef de Quartier)
                </h5>
                <p class="text-muted fs-7 mb-4">Attribuez immédiatement un Agent municipal en tant que Délégué de Quartier actif (Règle d'affectation Symfony).</p>

                <div class="fv-row">
                    <label class="fs-6 fw-semibold mb-2">Délégué de Quartier à affecter</label>
                    <select name="delegue_id" class="form-select form-select-solid @error('delegue_id') is-invalid @enderror" data-control="select2">
                        <option value="">Sélectionner un agent municipal...</option>
                        @foreach($agents as $agent)
                            <option value="{{ $agent->id }}" {{ old('delegue_id') == $agent->id ? 'selected' : '' }}>
                                {{ $agent->personne->prenom }} {{ $agent->personne->nom }} (Matricule : {{ $agent->matricule }})
                            </option>
                        @endforeach
                    </select>
                    @error('delegue_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="d-flex justify-content-end gap-5">
                <a href="{{ route('quartier.index') }}" class="btn btn-light">Annuler</a>
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-check me-1"></i>Enregistrer le Quartier
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
