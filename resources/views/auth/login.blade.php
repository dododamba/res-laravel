<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connexion - Système de Recensement Communal</title>
    
    <!-- Metronic Bootstrap 5 Stylesheets (for structure compatibility) -->
    <link href="{{ asset('assets/css/style.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css" />
    
    <!-- Google Fonts - Inter & Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f8fafc; /* Slate 50 - Arrière-plan clair, propre, sans dégradé */
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #0f172a; /* Slate 900 */
            margin: 0;
            padding: 0;
        }

        .login-card {
            background-color: #ffffff;
            border-radius: 0.75rem; /* Clean rounded-xl */
            border: 1px solid #e2e8f0; /* Slate 200 - Bordure fine minimaliste */
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05); /* Tailwind shadow-md soft */
            width: 100%;
            max-width: 450px;
            padding: 2.5rem;
            transition: all 0.2s ease-in-out;
        }

        .brand-logo {
            height: 56px;
            width: auto;
            margin-bottom: 1.5rem;
            transition: transform 0.2s ease;
        }

        .brand-logo:hover {
            transform: scale(1.03);
        }

        .form-label {
            font-size: 0.875rem; /* text-sm */
            font-weight: 600; /* font-semibold */
            color: #334155; /* Slate 700 */
            margin-bottom: 0.5rem;
            display: block;
        }

        .form-control {
            background-color: #ffffff;
            border: 1px solid #cbd5e1; /* Slate 300 */
            border-radius: 0.5rem; /* rounded-lg */
            padding: 0.625rem 0.875rem; /* standard size */
            font-size: 0.875rem;
            color: #0f172a;
            transition: all 0.15s ease-in-out;
            box-shadow: none !important;
        }

        .form-control::placeholder {
            color: #94a3b8; /* Slate 400 */
        }

        .form-control:focus {
            background-color: #ffffff;
            border-color: #6366f1; /* Tailwind Indigo 500 - Focus standard */
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1) !important; /* Indigo halo soft */
        }

        .form-check-input {
            border-radius: 0.25rem;
            border-color: #cbd5e1;
        }

        .form-check-input:checked {
            background-color: #6366f1; /* Indigo 500 */
            border-color: #6366f1;
        }

        .btn-primary {
            background-color: #4f46e5; /* Tailwind Indigo 600 */
            border: 1px solid #4f46e5;
            border-radius: 0.5rem; /* rounded-lg */
            padding: 0.75rem 1.5rem;
            font-size: 0.875rem;
            font-weight: 600;
            color: #ffffff;
            transition: all 0.15s ease-in-out;
            cursor: pointer;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important; /* Tailwind shadow-sm */
        }

        .btn-primary:hover {
            background-color: #4338ca; /* Tailwind Indigo 700 */
            border-color: #4338ca;
        }

        .btn-primary:active {
            background-color: #3730a3; /* Indigo 800 */
            border-color: #3730a3;
        }

        .link-primary {
            color: #4f46e5; /* Indigo 600 */
            font-weight: 600;
            font-size: 0.8125rem; /* text-xs+ */
            text-decoration: none;
            transition: color 0.15s ease;
        }

        .link-primary:hover {
            color: #4338ca; /* Indigo 700 */
            text-decoration: underline;
        }

        /* Soft Slate & Emerald/Rose Alerts inspired by Tailwind */
        .alert-success {
            background-color: #f0fdf4; /* Emerald 50 */
            border: 1px solid #bbf7d0; /* Emerald 200 */
            border-radius: 0.5rem;
            color: #15803d; /* Emerald 700 */
            padding: 0.875rem;
            font-size: 0.8125rem;
            font-weight: 500;
        }

        .alert-danger {
            background-color: #fef2f2; /* Rose 50 */
            border: 1px solid #fecdd3; /* Rose 200 */
            border-radius: 0.5rem;
            color: #be123c; /* Rose 700 */
            padding: 0.875rem;
            font-size: 0.8125rem;
            font-weight: 500;
        }

        .text-slate-500 {
            color: #64748b; /* Slate 500 */
        }
    </style>
</head>
<body>

    <div class="d-flex flex-column align-items-center justify-content-center w-100 p-6">
        
        <!--begin::Card wrapper-->
        <div class="login-card">
            
            <!--begin::Form-->
            <form class="form w-100" novalidate="novalidate" id="kt_sign_in_form" method="POST" action="{{ route('login') }}">
                @csrf

                <!--begin::Header-->
                <div class="text-center mb-8">
                    <!--Logo Officiel-->
                    <div>
                        <img alt="Logo" src="{{ asset('logo.png') }}" class="brand-logo" />
                    </div>
                    <!--Titres épurés-->
                    <h1 class="text-slate-900 fw-extrabolder mb-1 fs-3">Connexion</h1>
                    <p class="text-slate-500 fw-medium fs-6">Recensement Territorial & Cartographie</p>
                </div>
                <!--end::Header-->

                <!-- Alertes de validations ou flashs -->
                @if(session('success'))
                    <div class="alert alert-success d-flex align-items-center mb-6">
                        <span class="fw-bold">{{ session('success') }}</span>
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger mb-6">
                        <div class="d-flex flex-column">
                            @foreach ($errors->all() as $error)
                                <span class="fw-bold mb-1">{{ $error }}</span>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Champ : Email (Identifiant) -->
                <div class="mb-5">
                    <label class="form-label">Adresse Email</label>
                    <input class="form-control" type="email" placeholder="agent@commune.gov" name="email" value="{{ old('email') }}" required autocomplete="off" />
                </div>

                <!-- Champ : Mot de passe -->
                <div class="mb-5">
                    <div class="d-flex flex-stack justify-content-between mb-2">
                        <label class="form-label mb-0">Mot de passe</label>
                        <a href="#" class="link-primary">Mot de passe oublié ?</a>
                    </div>
                    <input class="form-control" type="password" placeholder="••••••••" name="password" required autocomplete="off" />
                </div>

                <!-- Option : Se souvenir de moi -->
                <div class="mb-6">
                    <label class="form-check form-check-custom form-check-solid d-flex align-items-center cursor-pointer">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember" />
                        <span class="form-check-label text-slate-500 fw-semibold fs-6 ms-2 user-select-none">Se souvenir de moi</span>
                    </label>
                </div>

                <!-- Bouton de Soumission principal -->
                <div class="d-grid">
                    <button type="submit" id="kt_sign_in_submit" class="btn btn-primary w-100">
                        Se connecter
                    </button>
                </div>

            </form>
            <!--end::Form-->

        </div>
        <!--end::Card wrapper-->

        <!-- Footer épuré discret -->
        <div class="text-center mt-8 text-slate-500 fs-7 fw-medium">
            Mairie Commune &copy; {{ date('Y') }} - Tous droits réservés.
        </div>

    </div>

    <!-- Scripts de base Metronic -->
    <script src="{{ asset('assets/plugins/global/plugins.bundle.js') }}"></script>
    <script src="{{ asset('assets/js/scripts.bundle.js') }}"></script>
</body>
</html>
