<?php

namespace App\Http\Controllers;

use App\Enums\ModeCalculTaxe;
use App\Enums\PeriodiciteTaxe;
use App\Models\Taxe;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TaxeController extends Controller
{
    public function index(Request $request)
    {
        $query = Taxe::query()->latest('ordre');

        if ($request->filled('q')) {
            $search = $request->input('q');
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('categorie', 'like', "%{$search}%");
            });
        }

        if ($request->filled('categorie')) {
            $query->where('categorie', $request->input('categorie'));
        }

        if ($request->filled('actif')) {
            $query->where('actif', $request->boolean('actif'));
        }

        $taxes = $query->paginate(15)->withQueryString();
        $categories = Taxe::select('categorie')->distinct()->pluck('categorie');

        return view('fiscalite.taxes.index', compact('taxes', 'categories'));
    }

    public function create()
    {
        return view('fiscalite.taxes.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:taxes,code',
            'categorie' => 'required|string|max:100',
            'montant' => 'required|numeric|min:0',
            'mode_calcul' => 'required|string',
            'periodicite' => 'required|string',
            'description' => 'nullable|string',
            'pourcentage' => 'nullable|numeric|min:0|max:100',
            'surface' => 'nullable|numeric|min:0',
            'volume' => 'nullable|numeric|min:0',
            'ordre' => 'nullable|integer',
        ]);

        $uuid = (string) Str::uuid();

        Taxe::create([
            'id' => $uuid,
            'uuid' => $uuid,
            'code' => strtoupper($request->input('code')),
            'nom' => $request->input('nom'),
            'description' => $request->input('description'),
            'categorie' => $request->input('categorie'),
            'montant' => $request->input('montant'),
            'mode_calcul' => $request->input('mode_calcul'),
            'periodicite' => $request->input('periodicite'),
            'pourcentage' => $request->input('pourcentage'),
            'surface' => $request->input('surface'),
            'volume' => $request->input('volume'),
            'actif' => $request->boolean('actif', true),
            'date_debut' => $request->input('date_debut') ?? now(),
            'date_fin' => $request->input('date_fin'),
            'ordre' => $request->input('ordre', 0),
        ]);

        return redirect()
            ->route('taxes.index')
            ->with('success', "La taxe '{$request->input('nom')}' a été ajoutée avec succès à la base réglementaire.");
    }

    public function edit(Taxe $taxe)
    {
        return view('fiscalite.taxes.edit', compact('taxe'));
    }

    public function update(Request $request, Taxe $taxe)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:taxes,code,' . $taxe->id,
            'categorie' => 'required|string|max:100',
            'montant' => 'required|numeric|min:0',
            'mode_calcul' => 'required|string',
            'periodicite' => 'required|string',
            'description' => 'nullable|string',
            'pourcentage' => 'nullable|numeric|min:0|max:100',
            'surface' => 'nullable|numeric|min:0',
            'volume' => 'nullable|numeric|min:0',
            'ordre' => 'nullable|integer',
        ]);

        $taxe->update([
            'code' => strtoupper($request->input('code')),
            'nom' => $request->input('nom'),
            'description' => $request->input('description'),
            'categorie' => $request->input('categorie'),
            'montant' => $request->input('montant'),
            'mode_calcul' => $request->input('mode_calcul'),
            'periodicite' => $request->input('periodicite'),
            'pourcentage' => $request->input('pourcentage'),
            'surface' => $request->input('surface'),
            'volume' => $request->input('volume'),
            'actif' => $request->boolean('actif', true),
            'date_debut' => $request->input('date_debut'),
            'date_fin' => $request->input('date_fin'),
            'ordre' => $request->input('ordre', 0),
        ]);

        return redirect()
            ->route('taxes.index')
            ->with('success', "La taxe '{$taxe->nom}' a été mise à jour.");
    }

    public function toggle(Taxe $taxe)
    {
        $taxe->actif = !$taxe->actif;
        $taxe->save();

        $statusLabel = $taxe->actif ? 'activée' : 'désactivée';
        return redirect()->back()->with('success', "La taxe '{$taxe->nom}' a été {$statusLabel}.");
    }

    public function destroy(Taxe $taxe)
    {
        $taxe->delete();
        return redirect()->route('taxes.index')->with('success', "La taxe a été supprimée de la base réglementaire.");
    }
}
