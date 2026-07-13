<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AuditLogController extends Controller
{
    /**
     * Liste des journaux d'audit et de sécurité (USER_MANAGE / AUDIT_VIEW).
     */
    public function index(Request $request)
    {
        Gate::authorize('AUDIT_VIEW');

        $query = AuditLog::query();

        // Gestion de la recherche/filtrage par action ou utilisateur
        if ($request->filled('q')) {
            $search = $request->input('q');
            $query->where(function ($q) use ($search) {
                $q->where('user_identifier', 'like', "%{$search}%")
                  ->orWhere('action', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        return view('audit.index', compact('logs'));
    }
}
