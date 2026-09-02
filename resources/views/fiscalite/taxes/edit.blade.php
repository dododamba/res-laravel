@extends('layouts.app')

@section('title', 'Modifier une Taxe Municipale')
@section('page_title', 'Édition de la taxe : ' . $taxe->nom)
@section('page_subtitle', 'Code : ' . $taxe->code . ' | Catégorie : ' . $taxe->categorie)

@section('actions')
    <a href="{{ route('taxes.index') }}" class="btn btn-sm btn-light">
        <i class="fas fa-arrow-left me-1"></i>Retour à la nomenclature
    </a>
@endsection

@section('content')
<div class="card card-flush shadow-sm">
    <form method="POST" action="{{ route('taxes.update', $taxe) }}" class="form">
        @csrf
        @method('PUT')
        <div class="card-body">
            <div class="row g-5 mb-5">
                <div class="col-md-6">
                    <label class="required form-label fw-semibold">Code Réglementaire</label>
                    <input type="text" name="code" value="{{ old('code', $taxe->code) }}" class="form-control form-control-solid" required />
                </div>
                <div class="col-md-6">
                    <label class="required form-label fw-semibold">Intitulé de la taxe</label>
                    <input type="text" name="nom" value="{{ old('nom', $taxe->nom) }}" class="form-control form-control-solid" required />
                </div>
            </div>

            <div class="row g-5 mb-5">
                <div class="col-md-4">
                    <label class="required form-label fw-semibold">Catégorie</label>
                    <input type="text" name="categorie" value="{{ old('categorie', $taxe->categorie) }}" class="form-control form-control-solid" required />
                </div>
                <div class="col-md-4">
                    <label class="required form-label fw-semibold">Montant de base (FCFA)</label>
                    <input type="number" step="0.01" name="montant" value="{{ old('montant', $taxe->montant) }}" class="form-control form-control-solid" required />
                </div>
                <div class="col-md-4">
                    <label class="required form-label fw-semibold">Mode de Calcul</label>
                    <select name="mode_calcul" class="form-select form-select-solid" required>
                        @foreach(\App\Enums\ModeCalculTaxe::cases() as $mode)
                            <option value="{{ $mode->value }}" {{ $taxe->mode_calcul == $mode ? 'selected' : '' }}>{{ $mode->label() }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="row g-5 mb-5">
                <div class="col-md-4">
                    <label class="required form-label fw-semibold">Périodicité</label>
                    <select name="periodicite" class="form-select form-select-solid" required>
                        @foreach(\App\Enums\PeriodiciteTaxe::cases() as $per)
                            <option value="{{ $per->value }}" {{ $taxe->periodicite == $per ? 'selected' : '' }}>{{ $per->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Surface par défaut (m²)</label>
                    <input type="number" step="0.01" name="surface" value="{{ old('surface', $taxe->surface) }}" class="form-control form-control-solid" />
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Ordre d'affichage</label>
                    <input type="number" name="ordre" value="{{ old('ordre', $taxe->ordre) }}" class="form-control form-control-solid" />
                </div>
            </div>

            <div class="mb-5">
                <label class="form-label fw-semibold">Description et assiette réglementaire</label>
                <textarea name="description" class="form-control form-control-solid" rows="3">{{ old('description', $taxe->description) }}</textarea>
            </div>
        </div>

        <div class="card-footer d-flex justify-content-end gap-3">
            <a href="{{ route('taxes.index') }}" class="btn btn-light">Annuler</a>
            <button type="submit" class="btn btn-primary">Mettre à jour</button>
        </div>
    </form>
</div>
@endsection
