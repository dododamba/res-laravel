@extends('layouts.app')

@section('title', "Modifier l'Habitation N°" . $maison->numero_porte)

@section('content')
<!--begin::Main form container-->
<div class="card card-flush shadow-sm">
    <!--begin::Card Header-->
    <div class="card-header py-5">
        <div class="card-title d-flex flex-column">
            <h3 class="fw-bold text-gray-900 mb-1">Modifier l'Habitation : #{{ $maison->id }}</h3>
            <span class="text-muted fs-7">Édition des informations d'habitat et d'urbanisme</span>
        </div>
        <div class="card-toolbar">
            <a href="{{ route('maison.show', $maison) }}" class="btn btn-sm btn-light">
                <i class="fas fa-arrow-left me-2"></i>Retour aux détails
            </a>
        </div>
    </div>
    <!--end::Card Header-->

    <!--begin::Card Body-->
    <div class="card-body">
        <form method="POST" action="{{ route('maison.update', $maison) }}" enctype="multipart/form-data" class="form">
            @csrf
            @method('PUT')

            <!-- Section 1 : Identification Cadastrale et géographie -->
            <div class="mb-8">
                <h5 class="text-primary fw-bold mb-5">
                    <i class="fas fa-search-location text-primary me-2"></i>1. Identification de l'Habitation
                </h5>
                <div class="separator separator-dashed border-primary opacity-25 mb-5"></div>
                
                <div class="row g-9 mb-5">
                    <!-- Numéro de Porte -->
                    <div class="col-md-4 fv-row">
                        <label class="required fs-6 fw-semibold mb-2">Numéro de Porte</label>
                        <input type="number" name="numero_porte" class="form-control form-control-solid @error('numero_porte') is-invalid @enderror" value="{{ old('numero_porte', $maison->numero_porte) }}" min="1" required />
                        @error('numero_porte')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <!-- Adresse -->
                    <div class="col-md-8 fv-row">
                        <label class="required fs-6 fw-semibold mb-2">Adresse</label>
                        <input type="text" name="adresse" class="form-control form-control-solid @error('adresse') is-invalid @enderror" value="{{ old('adresse', $maison->adresse) }}" required />
                        @error('adresse')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row g-9">
                    <!-- Référence Cadastrale -->
                    <div class="col-md-6 fv-row">
                        <label class="fs-6 fw-semibold mb-2">Référence Cadastrale</label>
                        <input type="text" name="reference_cadastrale" class="form-control form-control-solid @error('reference_cadastrale') is-invalid @enderror" value="{{ old('reference_cadastrale', $maison->reference_cadastrale) }}" />
                        @error('reference_cadastrale')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Carré -->
                    <div class="col-md-6 fv-row">
                        <label class="required fs-6 fw-semibold mb-2">Carré géographique (Bloc)</label>
                        <select name="carre_id" class="form-select form-select-solid @error('carre_id') is-invalid @enderror" data-control="select2" required>
                            <option value="">Sélectionner un carré...</option>
                            @foreach($carres as $carre)
                                <option value="{{ $carre->id }}" {{ old('carre_id', $maison->carre_id) == $carre->id ? 'selected' : '' }}>{{ $carre->nom }}</option>
                            @endforeach
                        </select>
                        @error('carre_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Section 2 : Population Résidente Estimée -->
            <div class="mb-8">
                <h5 class="text-primary fw-bold mb-5">
                    <i class="fas fa-users text-primary me-2"></i>2. Population Résidente Estimée
                </h5>
                <div class="separator separator-dashed border-primary opacity-25 mb-5"></div>

                <div class="row g-9">
                    <!-- Hommes -->
                    <div class="col-md-4 fv-row">
                        <label class="required fs-6 fw-semibold mb-2">Nombre d'Hommes</label>
                        <input type="number" name="nombre_hommes" class="form-control form-control-solid @error('nombre_hommes') is-invalid @enderror" value="{{ old('nombre_hommes', $maison->nombre_hommes ?? 0) }}" min="0" required />
                        @error('nombre_hommes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Femmes -->
                    <div class="col-md-4 fv-row">
                        <label class="required fs-6 fw-semibold mb-2">Nombre de Femmes</label>
                        <input type="number" name="nombre_femmes" class="form-control form-control-solid @error('nombre_femmes') is-invalid @enderror" value="{{ old('nombre_femmes', $maison->nombre_femmes ?? 0) }}" min="0" required />
                        @error('nombre_femmes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Enfants -->
                    <div class="col-md-4 fv-row">
                        <label class="required fs-6 fw-semibold mb-2">Nombre d'Enfants</label>
                        <input type="number" name="nombre_enfants" class="form-control form-control-solid @error('nombre_enfants') is-invalid @enderror" value="{{ old('nombre_enfants', $maison->nombre_enfants ?? 0) }}" min="0" required />
                        @error('nombre_enfants')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Section 3 : Géolocalisation SIG -->
            <div class="mb-8">
                <h5 class="text-primary fw-bold mb-5">
                    <i class="fas fa-satellite text-primary me-2"></i>3. Géolocalisation SIG
                </h5>
                <div class="separator separator-dashed border-primary opacity-25 mb-5"></div>

                <div class="row g-9">
                    <!-- Latitude -->
                    <div class="col-md-6 fv-row">
                        <label class="fs-6 fw-semibold mb-2">GPS Latitude</label>
                        <input type="text" name="gps_latitude" class="form-control form-control-solid @error('gps_latitude') is-invalid @enderror" value="{{ old('gps_latitude', $maison->gps_latitude) }}" placeholder="ex: -4.321" />
                        @error('gps_latitude')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Longitude -->
                    <div class="col-md-6 fv-row">
                        <label class="fs-6 fw-semibold mb-2">GPS Longitude</label>
                        <input type="text" name="gps_longitude" class="form-control form-control-solid @error('gps_longitude') is-invalid @enderror" value="{{ old('gps_longitude', $maison->gps_longitude) }}" placeholder="ex: 15.301" />
                        @error('gps_longitude')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Section 4 : Pièces Justificatives (Téléversement) -->
            <div class="mb-8">
                <h5 class="text-primary fw-bold mb-5">
                    <i class="fas fa-paperclip text-primary me-2"></i>4. Pièces Justificatives
                </h5>
                <div class="separator separator-dashed border-primary opacity-25 mb-5"></div>

                <div class="row g-9">
                    <!-- Photo d'habitation -->
                    <div class="col-md-6 fv-row">
                        <label class="fs-6 fw-semibold mb-2">Photo de la Façade (Max 5Mo - JPEG/PNG)</label>
                        
                        @if($maison->hasMedia('photos_habitation'))
                            <div class="mb-3 symbol symbol-100px symbol-md-150px d-block">
                                <img src="{{ $maison->getFirstMediaUrl('photos_habitation') }}" alt="Photo de la Façade" class="rounded border" style="object-fit: cover;" />
                                <span class="d-block text-muted fs-7 mt-1">Photo actuelle</span>
                            </div>
                        @endif

                        <input type="file" name="photo" class="form-control form-control-solid @error('photo') is-invalid @enderror" accept="image/*" />
                        <span class="fs-7 text-muted">Sélectionnez un nouveau fichier pour remplacer la photo existante</span>
                        @error('photo')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Document de cadastre -->
                    <div class="col-md-6 fv-row">
                        <label class="fs-6 fw-semibold mb-2">Document du Cadastre / Titre Foncier (Max 10Mo - PDF/JPEG/PNG)</label>
                        
                        @if($maison->hasMedia('documents_cadastre'))
                            <div class="mb-3 d-block">
                                <a href="{{ $maison->getFirstMediaUrl('documents_cadastre') }}" target="_blank" class="btn btn-sm btn-light-primary py-2 px-3">
                                    <i class="fas fa-file-pdf fs-4 me-2"></i>Visualiser le document actuel
                                </a>
                            </div>
                        @endif

                        <input type="file" name="document_cadastre" class="form-control form-control-solid @error('document_cadastre') is-invalid @enderror" accept=".pdf,image/*" />
                        <span class="fs-7 text-muted">Sélectionnez un nouveau fichier pour remplacer le document de cadastre existant</span>
                        @error('document_cadastre')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Boutons de validation -->
            <div class="d-flex justify-content-end gap-5 pt-5">
                <a href="{{ route('maison.show', $maison) }}" class="btn btn-light">Annuler</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Enregistrer les Modifications
                </button>
            </div>
        </form>
    </div>
    <!--end::Card Body-->
</div>
<!--end::Main form container-->
@endsection
