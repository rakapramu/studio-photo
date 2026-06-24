<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseController extends Controller
{
    /**
     * Display a listing of expenses.
     */
    public function index(): Response
    {
        $expenses = Expense::orderBy('expense_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        $totalAmount = (float) Expense::sum('amount');
        
        $thisMonthAmount = (float) Expense::whereMonth('expense_date', Carbon::now()->month)
            ->whereYear('expense_date', Carbon::now()->year)
            ->sum('amount');
        
        $thisMonthLabel = Carbon::now()->translatedFormat('F Y');

        return Inertia::render('Admin/Expenses/Index', [
            'expenses' => $expenses,
            'totalAmount' => $totalAmount,
            'thisMonthAmount' => $thisMonthAmount,
            'thisMonthLabel' => $thisMonthLabel,
        ]);
    }

    /**
     * Store a newly created expense.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'expense_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        Expense::create($validated);

        return redirect()->route('admin.expenses.index')->with('success', 'Pengeluaran operasional berhasil dicatat.');
    }

    /**
     * Update the specified expense.
     */
    public function update(Request $request, Expense $expense): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'expense_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $expense->update($validated);

        return redirect()->route('admin.expenses.index')->with('success', 'Catatan pengeluaran berhasil diperbarui.');
    }

    /**
     * Remove the specified expense.
     */
    public function destroy(Expense $expense): RedirectResponse
    {
        $expense->delete();

        return redirect()->route('admin.expenses.index')->with('success', 'Catatan pengeluaran berhasil dihapus.');
    }
}
