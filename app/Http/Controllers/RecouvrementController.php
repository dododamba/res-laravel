<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\Recouvrement;
use App\Models\TaxeOperateur;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RecouvrementController extends Controller
{
    public function index(Request $request)
    {
        $query = Recouvrement::query()
            ->with(['taxeOperateur.taxe', 'taxeOperateur.operateur.quartier', 'agent.personne'])
            ->latest('date_relance');

        if ($request->filled('q')) {
            $search = $request->input('q');
            $query->where(function ($q) use ($search) {
                $q->where('commentaires', 'like', "%{$search}%")
                  ->orWhereHas('taxeOperateur.operateur', function ($opQ) use ($search) {
                      $opQ->where('nom_entreprise', 'like', "%{$search}%")
                          ->orWhere('nom_commercial', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('statut')) {
            $query->where('statut', $request->input('statut'));
        }

        $recouvrements = $query->paginate(15)->withQueryString();

        // Taxes en retard ou partiellement payées éligibles à la relance
        $taxesOverdue = TaxeOperateur::with(['operateur', 'taxe'])
            ->whereIn('statut', ['En retard', 'Partiellement payé', 'A payer'])
            ->get();

        $agents = Agent::with('personne')->get();

        return view('fiscalite.recouvrements.index', compact('recouvrements', 'taxesOverdue', 'agents'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'taxe_operateur_id' => 'required|exists:taxe_operateurs,id',
            'commentaires' => 'required|string',
            'statut' => 'required|string',
            'date_relance' => 'required|date',
            'prochaine_relance' => 'nullable|date',
            'agent_id' => 'nullable|exists:agents,id',
        ]);

        $uuid = (string) Str::uuid();

        Recouvrement::create([
            'id' => $uuid,
            'uuid' => $uuid,
            'taxe_operateur_id' => $request->input('taxe_operateur_id'),
            'date_relance' => $request->input('date_relance'),
            'agent_id' => $request->input('agent_id'),
            'user_id' => auth()->id(),
            'commentaires' => $request->input('commentaires'),
            'statut' => $request->input('statut'),
            'prochaine_relance' => $request->input('prochaine_relance'),
        ]);

        return redirect()
            ->route('recouvrements.index')
            ->with('success', 'Relance de recouvrement enregistrée avec succès.');
    }
}
