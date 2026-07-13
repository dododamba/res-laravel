@extends('layouts.app')

@section('title', 'Modifier le Quartier : ' . $entity->nom)

@section('content')
<div class="card card-flush shadow-sm">
    <div class="card-header py-5">
        <div class="card-title d-flex flex-column">
            <h3 class="fw-bold text-gray-900 mb-1">Modifier le Quartier : {{ $entity->nom }}</h3>
            <span class="text-muted fs-7">Édition de l'aire administrative et de sa supervision</span>
        </div>
        <div class="card-toolbar">
            <a href="{{ route('quartier.index') }}/{{ $entity->id }}" class="btn btn-sm btn-light">
                <i class="fas fa-arrow-left me-2"></i>Retour aux détails
            </a>
        </div>
    </div>

    <div class="card-body">
        <form method="POST" action="{{ route('quartier.index') }}/{{ $entity->id }}" class="form">
            @csrf
            @method('PUT')

            <div class="row g-9 mb-5">
                <!-- Nom du Quartier -->
                <div class="col-md-8 fv-row">
                    <label class="required fs-6 fw-semibold mb-2">Nom du Quartier</label>
                    <input type="text" name="nom" class="form-control form-control-solid @error('nom') is-invalid @enderror" value="{{ old('nom', $entity->nom) }}" required />
                    @error('nom')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Code Administratif -->
                <div class="col-md-4 fv-row">
                    <label class="fs-6 fw-semibold mb-2">Code Quartier</label>
                    <input type="text" name="code" class="form-control form-control-solid @error('code') is-invalid @enderror" value="{{ old('code', $entity->code) }}" />
                    @error('code')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <!-- Description -->
            <div class="fv-row mb-5">
                <label class="fs-6 fw-semibold mb-2">Description / Notes géographiques</label>
                <textarea name="description" class="form-control form-control-solid @error('description') is-invalid @enderror" rows="3">{{ old('description', $entity->description) }}</textarea>
                @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="row g-9 mb-8">
                <!-- Couleur -->
                <div class="col-md-4 fv-row">
                    <label class="fs-6 fw-semibold mb-2">Couleur Hexadécimale</label>
                    <div class="d-flex align-items-center gap-2">
                        <input type="color" class="form-control form-control-color w-60px form-control-solid" id="colorPicker" value="{{ old('couleur', $entity->couleur ?? '#7239EA') }}" oninput="document.getElementById('colorInput').value = this.value">
                        <input type="text" name="couleur" id="colorInput" class="form-control form-control-solid @error('couleur') is-invalid @enderror" value="{{ old('couleur', $entity->couleur ?? '#7239EA') }}" placeholder="#7239EA" pattern="^#([A-Fa-f0-9]{6})$" oninput="document.getElementById('colorPicker').value = this.value">
                    </div>
                    @error('couleur')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Icône -->
                <div class="col-md-4 fv-row">
                    <label class="fs-6 fw-semibold mb-2">Icône Bootstrap</label>
                    <input type="text" name="icone" class="form-control form-control-solid @error('icone') is-invalid @enderror" value="{{ old('icone', $entity->icone ?? 'bi-geo-alt') }}" />
                    @error('icone')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Ordre d'affichage -->
                <div class="col-md-4 fv-row">
                    <label class="fs-6 fw-semibold mb-2">Ordre d'affichage</label>
                    <input type="number" name="ordre_affichage" class="form-control form-control-solid @error('ordre_affichage') is-invalid @enderror" value="{{ old('ordre_affichage', $entity->ordre_affichage ?? 0) }}" min="0" />
                    @error('ordre_affichage')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <!-- Section 2 : Supervision (Délégué de Quartier) -->
            <div class="mb-8 p-5 bg-light-primary rounded border border-primary border-dashed">
                <h5 class="text-primary fw-bold mb-3">
                    <i class="fas fa-user-shield text-primary me-2"></i>2. Supervision (Délégué de Quartier)
                </h5>
                <p class="text-muted fs-7 mb-4">Attribuez un autre agent municipal. Changer d'agent clôturera automatiquement l'ancienne affectation de Délégué pour ce quartier et en ouvrira une nouvelle (Historisation des affectations Symfony).</p>

                <div class="fv-row">
                    <label class="fs-6 fw-semibold mb-2">Délégué de Quartier actuel ou futur</label>
                    <select name="delegue_id" class="form-select form-select-solid @error('delegue_id') is-invalid @enderror" data-control="select2">
                        <option value="">-- Aucun délégué assigné --</option>
                        @foreach($agents as $agent)
                            <option value="{{ $agent->id }}" {{ old('delegue_id', $entity->delegue?->id) == $agent->id ? 'selected' : '' }}>
                                {{ $agent->personne->prenom }} {{ $agent->personne->nom }} (Matricule : {{ $agent->matricule }})
                            </option>
                        @endforeach
                    </select>
                    @error('delegue_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                @if($entity->delegue)
                    <div class="mt-3 text-gray-700 fs-7">
                        <i class="fas fa-info-circle me-1 text-primary"></i>Délégué actif actuel : <strong>{{ $entity->delegue->personne->prenom }} {{ $entity->delegue->personne->nom }}</strong>
                    </div>
                @endif
            </div>

            <div class="d-flex justify-content-end gap-5">
                <a href="{{ route('quartier.index') }}/{{ $entity->id }}" class="btn btn-light">Annuler</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i>Enregistrer les Modifications
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
