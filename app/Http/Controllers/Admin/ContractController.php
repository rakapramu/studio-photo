<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use Inertia\Inertia;
use Inertia\Response;

class ContractController extends Controller
{
    /**
     * Display a listing of all contracts in the admin dashboard.
     */
    public function index(): Response
    {
        $contracts = Contract::with(['booking.package'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Calculate simple stats
        $totalContracts = Contract::count();
        $signedContracts = Contract::whereNotNull('signed_at')->count();
        $draftContracts = Contract::whereNull('signed_at')->count();

        return Inertia::render('Admin/Contracts/Index', [
            'contracts' => $contracts,
            'stats' => [
                'total_count' => $totalContracts,
                'signed_count' => $signedContracts,
                'draft_count' => $draftContracts,
            ],
        ]);
    }
}
