<?php

namespace App\Http\Controllers;

use App\Models\Exoneration;
use App\Models\TaxeOperateur;
use App\Services\TaxCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ExonerationController extends Controller
{
    public function __construct(
        protected TaxCalculationService $calculationService
    ) {}

    public function index(Request $request)
    {
        $query = Exoneration::query()
            ->with(['taxeOperateur.taxe', 'taxeOperateur.operateur', 'agent.personne', 'user'])
            ->latest('date_exoneration');

        if ($request->filled('q')) {
            $search = $request->input('q');
            $query->where(function ($q) use ($search) {
                $q->where('autorite', 'like', "%{$search}%")
                  ->orWhere('motif', 'like', "%{$search}%")
                  ->orWhereHas('taxeOperateur.operateur', function ($opQ) use ($search) {
                      $opQ->where('nom_entreprise', 'like', "%{$search}%")
                          ->orWhere('nom_commercial', 'like', "%{$search}%");
                  });
            });
        }

        $exonerations = $query->paginate(15)->withQueryString();

        $taxesOperateurs = TaxeOperateur::with(['operateur', 'taxe'])
            ->where('statut', '!=', 'Soldé')
            ->get();

        return view('fiscalite.exonerations.index', compact('exonerations', 'taxesOperateurs'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'taxe_operateur_id' => 'required|exists:taxe_operateurs,id',
            'motif' => 'required|string',
            'autorite' => 'required|string|max:255',
            'date_exoneration' => 'required|date',
            'montant_exonere' => 'required|numeric|min:1',
            'document' => 'nullable|file|mimes:pdf,jpeg,png|max:5120',
        ]);

        $taxeOp = TaxeOperateur::findOrFail($request->input('taxe_operateur_id'));

        $uuid = (string) Str::uuid();
        $docPath = null;

        if ($request->hasFile('document')) {
            $docPath = $request->file('document')->store('exonerations', 'public');
        }

        $exoneration = Exoneration::create([
            'id' => $uuid,
            'uuid' => $uuid,
            'taxe_operateur_id' => $taxeOp->id,
            'motif' => $request->input('motif'),
            'autorite' => $request->input('autorite'),
            'document' => $docPath,
            'date_exoneration' => $request->input('date_exoneration'),
            'montant_exonere' => $request->input('montant_exonere'),
            'user_id' => auth()->id(),
        ]);

        // Recalcul automatique du statut et du solde restant
        $this->calculationService->updateTaxeOperateurStatus($taxeOp);

        return redirect()
            ->route('exonerations.index')
            ->with('success', "Avis d'exonération de " . number_format($exoneration->montant_exonere) . " FCFA accordé avec succès.");
    }
}
