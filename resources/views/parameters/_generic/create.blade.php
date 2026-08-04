@extends('layouts.app')

@section('title', 'Créer : ' . ($parameterLabel ?? 'Paramètre'))

@section('content')
<div class="card card-flush shadow-sm">
    <div class="card-header py-5">
        <div class="card-title d-flex flex-column">
            <h3 class="fw-bold text-gray-900 mb-1">Créer un nouveau {{ $parameterLabelSingular ?? 'paramètre' }}</h3>
            <span class="text-muted fs-7">Ajout d'un nouvel enregistrement de référence</span>
        </div>
        <div class="card-toolbar">
            <a href="{{ route($routePrefix . '.index') }}" class="btn btn-sm btn-light">
                <i class="fas fa-arrow-left me-2"></i>Retour à la liste
            </a>
        </div>
    </div>

    <div class="card-body">
        <form method="POST" action="{{ route($routePrefix . '.store') }}" class="form">
            @csrf

            <div class="row g-9 mb-5">
                <!-- Nom -->
                <div class="col-md-8 fv-row">
                    <label class="required fs-6 fw-semibold mb-2">Nom</label>
                    <input type="text" name="nom" class="form-control form-control-solid @error('nom') is-invalid @enderror" value="{{ old('nom') }}" placeholder="Saisissez le nom..." required />
                    @error('nom')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Code -->
                <div class="col-md-4 fv-row">
                    <label class="fs-6 fw-semibold mb-2">Code</label>
                    <input type="text" name="code" class="form-control form-control-solid @error('code') is-invalid @enderror" value="{{ old('code') }}" placeholder="ex: CODE-01" />
                    @error('code')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <!-- Description -->
            <div class="fv-row mb-5">
                <label class="fs-6 fw-semibold mb-2">Description</label>
                <textarea name="description" class="form-control form-control-solid @error('description') is-invalid @enderror" rows="3" placeholder="Description optionnelle...">{{ old('description') }}</textarea>
                @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="row g-9 mb-8">
                <!-- Couleur -->
                <div class="col-md-4 fv-row">
                    <label class="fs-6 fw-semibold mb-2">Couleur</label>
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
                    <label class="fs-6 fw-semibold mb-2">Icône</label>
                    <input type="text" name="icone" class="form-control form-control-solid @error('icone') is-invalid @enderror" value="{{ old('icone') }}" placeholder="ex: fas fa-tag" />
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

            <div class="d-flex justify-content-end gap-5">
                <a href="{{ route($routePrefix . '.index') }}" class="btn btn-light">Annuler</a>
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-check me-1"></i>Enregistrer
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
