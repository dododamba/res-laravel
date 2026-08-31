<?php

namespace App\Http\Controllers;

use App\Services\StatisticsService;
use Illuminate\Http\Request;

class StatisticsController extends Controller
{
    protected StatisticsService $statsService;

    public function __construct(StatisticsService $statsService)
    {
        $this->statsService = $statsService;
    }

    /**
     * Display the main statistics page (Global KPIs + Quartiers Table).
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        
        $globalStats = $this->statsService->getGlobalStats($user);
        $quartierStats = $this->statsService->getQuartierStats($user);

        return view('statistics.index', [
            'globalStats' => $globalStats,
            'quartierStats' => $quartierStats,
        ]);
    }

    /**
     * Display carres for a given quartier (Web AJAX or view).
     */
    public function carres(Request $request, $quartierId)
    {
        $user = auth()->user();

        try {
            $carreStats = $this->statsService->getCarreStats((int) $quartierId, $user);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => $carreStats,
                ]);
            }

            return view('statistics.carres', [
                'carreStats' => $carreStats,
            ]);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 403);
            }
            abort(403, $e->getMessage());
        }
    }
}
