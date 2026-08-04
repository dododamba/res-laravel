@extends('layouts.app')

@section('title', 'Créer une Taxe Municipale')

@section('content')
<div class="card card-flush shadow-sm">
    <div class="card-header">
        <h3 class="card-title fw-bold text-gray-900">Formulaire de création d'une taxe municipale</h3>
    </div>
    <form method="POST" action="{{ route('taxes.store') }}" class="form">
        @csrf
        <div class="card-body">
            <div class="row g-5 mb-5">
                <div class="col-md-6">
                    <label class="required form-label fw-semibold">Code Réglementaire</label>
                    <input type="text" name="code" value="{{ old('code') }}" class="form-control form-control-solid" placeholder="Ex: PAT-COMM, ODP-TERR" required />
                </div>
                <div class="col-md-6">
                    <label class="required form-label fw-semibold">Intitulé de la taxe</label>
                    <input type="text" name="nom" value="{{ old('nom') }}" class="form-control form-control-solid" placeholder="Ex: Patente Commerciale et Industrielle" required />
                </div>
            </div>

            <div class="row g-5 mb-5">
                <div class="col-md-4">
                    <label class="required form-label fw-semibold">Catégorie</label>
                    <input type="text" name="categorie" value="{{ old('categorie') }}" class="form-control form-control-solid" placeholder="Ex: Domaine Public, Patente" required />
                </div>
                <div class="col-md-4">
                    <label class="required form-label fw-semibold">Montant de base (FCFA)</label>
                    <input type="number" step="0.01" name="montant" value="{{ old('montant', 0) }}" class="form-control form-control-solid" required />
                </div>
                <div class="col-md-4">
                    <label class="required form-label fw-semibold">Mode de Calcul</label>
                    <select name="mode_calcul" class="form-select form-select-solid" required>
                        @foreach(\App\Enums\ModeCalculTaxe::cases() as $mode)
                            <option value="{{ $mode->value }}">{{ $mode->label() }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="row g-5 mb-5">
                <div class="col-md-4">
                    <label class="required form-label fw-semibold">Périodicité</label>
                    <select name="periodicite" class="form-select form-select-solid" required>
                        @foreach(\App\Enums\PeriodiciteTaxe::cases() as $per)
                            <option value="{{ $per->value }}">{{ $per->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Surface par défaut (m²)</label>
                    <input type="number" step="0.01" name="surface" value="{{ old('surface') }}" class="form-control form-control-solid" placeholder="Optionnel" />
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Ordre d'affichage</label>
                    <input type="number" name="ordre" value="{{ old('ordre', 1) }}" class="form-control form-control-solid" />
                </div>
            </div>

            <div class="mb-5">
                <label class="form-label fw-semibold">Description et assiette réglementaire</label>
                <textarea name="description" class="form-control form-control-solid" rows="3" placeholder="Description des modalités de perception et conditions légales...">{{ old('description') }}</textarea>
            </div>
        </div>

        <div class="card-footer d-flex justify-content-end gap-3">
            <a href="{{ route('taxes.index') }}" class="btn btn-light">Annuler</a>
            <button type="submit" class="btn btn-primary">Enregistrer la Taxe</button>
        </div>
    </form>
</div>
@endsection
