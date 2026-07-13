<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveRecensementRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Seuls les enquêteurs et les admins ont l'autorisation de soumettre un recensement
        return auth()->check() && auth()->user()->hasRole(['ROLE_ENQUETEUR', 'ROLE_ADMIN', 'ROLE_SUPER_ADMIN']);
    }

    public function rules(): array
    {
        return [
            // Identifiants
            'uuid' => 'nullable|string|size:36',
            'nom_recensement' => 'nullable|string|max:255',

            // Chef de Ménage
            'chefNom' => 'required|string|max:100',
            'chefPrenom' => 'required|string|max:100',
            'chefSexe' => 'required|string|in:M,F',
            'chefAge' => 'required|integer|min:18|max:120',
            'chefTelephone' => 'required|string|regex:/^[0-9]{9,15}$/',
            'chefEmail' => 'nullable|email|max:255',

            // Localisation Géographique
            'quartier_id' => 'required|exists:quartiers,id',
            'carre_id' => 'required|exists:carres,id',
            'secteur_id' => 'nullable|exists:secteurs,id',
            'avenue_id' => 'nullable|exists:avenues,id',
            'numeroPorte' => 'required|string|max:20',
            'adresse' => 'required|string|max:255',

            // Démographie & Composition
            'nombrePersonnes' => 'required|integer|min:1',
            'nombreHommes' => 'required|integer|min:0',
            'nombreFemmes' => 'required|integer|min:0',
            'nombreEnfants' => 'required|integer|min:0',
            'nombreJeunes' => 'required|integer|min:0',
            'nombreHandicapes' => 'required|integer|min:0',

            // Niveaux d'instruction
            'instructionAucun' => 'required|integer|min:0',
            'instructionPrimaire' => 'required|integer|min:0',
            'instructionSecondaire' => 'required|integer|min:0',
            'instructionSuperieur' => 'required|integer|min:0',

            // Besoins Prioritaires (Relation ManyToMany)
            'priorites' => 'required|array|min:1|max:3',
            'priorites.*' => 'exists:besoins_prioritaires,id',

            // GPS
            'gpsLatitude' => 'nullable|numeric|between:-90,90',
            'gpsLongitude' => 'nullable|numeric|between:-180,180',
        ];
    }

    /**
     * Validation métier stricte (remplacement de validateBusinessRules() du RecensementService)
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $data = $this->all();

            // Règle 1 : Cohérence Démographique (Total = Hommes + Femmes)
            $total = (int)($data['nombrePersonnes'] ?? 0);
            $hommes = (int)($data['nombreHommes'] ?? 0);
            $femmes = (int)($data['nombreFemmes'] ?? 0);

            if ($total !== ($hommes + $femmes)) {
                $validator->errors()->add('nombrePersonnes', 'Le nombre total de personnes doit être exactement égal à la somme des hommes et des femmes.');
            }

            // Règle 2 : Cohérence des enfants et jeunes
            $enfants = (int)($data['nombreEnfants'] ?? 0);
            $jeunes = (int)($data['nombreJeunes'] ?? 0);
            $handicapes = (int)($data['nombreHandicapes'] ?? 0);

            if ($enfants > $total) {
                $validator->errors()->add('nombreEnfants', "Le nombre d'enfants ne peut pas dépasser le nombre total de personnes du ménage.");
            }
            if ($jeunes > $total) {
                $validator->errors()->add('nombreJeunes', "Le nombre de jeunes ne peut pas dépasser le nombre total de personnes du ménage.");
            }
            if ($handicapes > $total) {
                $validator->errors()->add('nombreHandicapes', "Le nombre de personnes handicapées ne peut pas dépasser le nombre total de personnes.");
            }
        });
    }

    public function messages(): array
    {
        return [
            'chefNom.required' => 'Le nom du chef de ménage est obligatoire.',
            'chefTelephone.regex' => 'Le numéro de téléphone doit contenir entre 9 et 15 chiffres.',
            'priorites.max' => 'Vous pouvez sélectionner au maximum 3 besoins prioritaires.',
        ];
    }
}
