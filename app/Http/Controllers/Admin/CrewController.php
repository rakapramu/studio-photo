<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Crew;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CrewController extends Controller
{
    /**
     * Display a listing of crews.
     */
    public function index(): Response
    {
        $crews = Crew::orderBy('name', 'asc')->get();

        return Inertia::render('Admin/Crews/Index', [
            'crews' => $crews,
        ]);
    }

    /**
     * Store a newly created crew.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'role' => ['required', 'string', 'in:fotografer,videografer,editor,mua,asisten'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'is_active' => ['required', 'boolean'],
            'fee_per_session' => ['required', 'numeric', 'min:0'],
        ]);

        Crew::create($validated);

        return redirect()->route('admin.crews.index')->with('success', 'Staf/kru baru berhasil ditambahkan.');
    }

    /**
     * Update the specified crew.
     */
    public function update(Request $request, Crew $crew): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'role' => ['required', 'string', 'in:fotografer,videografer,editor,mua,asisten'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'is_active' => ['required', 'boolean'],
            'fee_per_session' => ['required', 'numeric', 'min:0'],
        ]);

        $crew->update($validated);

        return redirect()->route('admin.crews.index')->with('success', 'Data staf/kru berhasil diperbarui.');
    }

    /**
     * Remove the specified crew.
     */
    public function destroy(Crew $crew): RedirectResponse
    {
        $crew->delete();

        return redirect()->route('admin.crews.index')->with('success', 'Data staf/kru berhasil dihapus dari sistem.');
    }
}
