<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Equipment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EquipmentController extends Controller
{
    /**
     * Display a listing of equipments.
     */
    public function index(): Response
    {
        $equipments = Equipment::orderBy('name', 'asc')->get();

        return Inertia::render('Admin/Equipments/Index', [
            'equipments' => $equipments,
        ]);
    }

    /**
     * Store a newly created equipment.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:kamera,lensa,lighting,aksesoris,properti'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', 'in:active,maintenance,broken'],
            'notes' => ['nullable', 'string'],
        ]);

        Equipment::create($validated);

        return redirect()->route('admin.equipments.index')->with('success', 'Alat/properti baru berhasil ditambahkan.');
    }

    /**
     * Update the specified equipment.
     */
    public function update(Request $request, Equipment $equipment): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:kamera,lensa,lighting,aksesoris,properti'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', 'in:active,maintenance,broken'],
            'notes' => ['nullable', 'string'],
        ]);

        $equipment->update($validated);

        return redirect()->route('admin.equipments.index')->with('success', 'Data alat/properti berhasil diperbarui.');
    }

    /**
     * Remove the specified equipment.
     */
    public function destroy(Equipment $equipment): RedirectResponse
    {
        // Hapus penugasan dari booking terlebih dahulu (cascade dibantu db migration)
        $equipment->delete();

        return redirect()->route('admin.equipments.index')->with('success', 'Alat/properti berhasil dihapus dari sistem.');
    }
}
