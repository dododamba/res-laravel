<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\Operateur;
use App\Models\PaiementTaxe;
use App\Models\Parameters\Quartier;
use App\Models\Taxe;
use App\Models\TaxeOperateur;
use App\Services\PaymentProcessingService;
use App\Services\TaxAssignmentService;
use Illuminate\Http\Request;

class PaiementTaxeController extends Controller
{
    public function __construct(
        protected PaymentProcessingService $paymentService,
        protected TaxAssignmentService $assignmentService
    ) {}

    public function index(Request $request)
    {
        $query = PaiementTaxe::query()
            ->with(['taxeOperateur.taxe', 'taxeOperateur.operateur.quartier', 'agent.personne', 'user'])
            ->latest('date_paiement');

        // Filtre Recherche (Reçu, Référence, Raison Sociale, NIF/RCCM)
        if ($request->filled('q')) {
            $search = $request->input('q');
            $query->where(function ($q) use ($search) {
                $q->where('numero_recu', 'like', "%{$search}%")
                  ->orWhere('reference', 'like', "%{$search}%")
                  ->orWhereHas('taxeOperateur.operateur', function ($opQ) use ($search) {
                      $opQ->where('nom_entreprise', 'like', "%{$search}%")
                          ->orWhere('nom_commercial', 'like', "%{$search}%")
                          ->orWhere('rccm', 'like', "%{$search}%")
                          ->orWhere('nif', 'like', "%{$search}%");
                  });
            });
        }

        // Filtre Année
        if ($request->filled('annee')) {
            $query->whereYear('date_paiement', $request->input('annee'));
        }

        // Filtre Taxe
        if ($request->filled('taxe_id')) {
            $query->whereHas('taxeOperateur', function ($q) use ($request) {
                $q->where('taxe_id', $request->input('taxe_id'));
            });
        }

        // Filtre Quartier
        if ($request->filled('quartier_id')) {
            $query->whereHas('taxeOperateur.operateur', function ($q) use ($request) {
                $q->where('quartier_id', $request->input('quartier_id'));
            });
        }

        // Filtre Agent encaisseur
        if ($request->filled('agent_id')) {
            $query->where('agent_id', $request->input('agent_id'));
        }

        // Filtre Mode de paiement
        if ($request->filled('mode_paiement')) {
            $query->where('mode_paiement', $request->input('mode_paiement'));
        }

        $paiements = $query->paginate(15)->withQueryString();

        $taxes = Taxe::orderBy('nom')->get();
        $quartiers = Quartier::active()->orderBy('nom')->get();
        $agents = Agent::with('personne')->get();

        return view('fiscalite.paiements.index', compact('paiements', 'taxes', 'quartiers', 'agents'));
    }

    public function create(Request $request)
    {
        $selectedTaxeOp = null;
        $selectedOperateur = null;

        if ($request->filled('taxe_operateur_id')) {
            $selectedTaxeOp = TaxeOperateur::with(['operateur', 'taxe', 'paiements'])->find($request->input('taxe_operateur_id'));
            if ($selectedTaxeOp) {
                $selectedOperateur = $selectedTaxeOp->operateur;
            }
        } elseif ($request->filled('operateur_id')) {
            $selectedOperateur = Operateur::with(['taxesAffectees.taxe', 'taxesAffectees.paiements'])->find($request->input('operateur_id'));
            if ($selectedOperateur && $selectedOperateur->taxesAffectees->isEmpty()) {
                // Auto-affectation des taxes si aucune taxe n'est affectée
                $this->assignmentService->autoAssignTaxesForOperateur($selectedOperateur);
                $selectedOperateur->load('taxesAffectees.taxe');
            }
        }

        $operateurs = Operateur::orderBy('nom_commercial')->limit(100)->get();
        $agents = Agent::with('personne')->get();

        return view('fiscalite.paiements.create', compact('selectedTaxeOp', 'selectedOperateur', 'operateurs', 'agents'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'taxe_operateur_id' => 'required|exists:taxe_operateurs,id',
            'montant' => 'required|numeric|min:1',
            'mode_paiement' => 'required|string',
            'date_paiement' => 'required|date',
            'reference' => 'nullable|string|max:255',
            'numero_recu' => 'nullable|string|max:100|unique:paiement_taxes,numero_recu',
            'agent_id' => 'nullable|exists:agents,id',
            'observation' => 'nullable|string',
            'justificatif' => 'nullable|file|mimes:pdf,jpeg,png,webp|max:5120',
        ]);

        $data = $request->all();

        $paiement = $this->paymentService->recordPayment($data);

        // Upload de pièce jointe s'il y en a une
        if ($request->hasFile('justificatif')) {
            $paiement->addMediaFromRequest('justificatif')
                     ->toMediaCollection('justificatifs_paiement');
        }

        return redirect()
            ->route('paiements.show', $paiement)
            ->with('success', "Encaissement enregistré avec succès. Reçu N° {$paiement->numero_recu} généré.");
    }

    public function show(PaiementTaxe $paiement)
    {
        $paiement->load(['taxeOperateur.taxe', 'taxeOperateur.operateur.quartier', 'agent.personne', 'user']);

        return view('fiscalite.paiements.show', compact('paiement'));
    }

    public function printRecu(PaiementTaxe $paiement)
    {
        $paiement->load(['taxeOperateur.taxe', 'taxeOperateur.operateur.quartier', 'agent.personne', 'user']);

        return view('fiscalite.paiements.recu', compact('paiement'));
    }
}
