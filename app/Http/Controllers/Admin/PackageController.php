<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PackageController extends Controller
{
    /**
     * Display a listing of the packages.
     */
    public function index(): Response
    {
        $packages = Package::orderBy('created_at', 'desc')->get();

        return Inertia::render('Admin/Packages/Index', [
            'packages' => $packages,
        ]);
    }

    /**
     * Store a newly created package in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'duration_minutes' => ['required', 'integer', 'min:1'],
            'image' => ['nullable', 'image', 'max:5120'],
        ]);

        $data = $validated;
        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('packages', 'public');
        }

        Package::create($data);

        return redirect()->route('admin.packages.index')->with('success', 'Paket foto berhasil ditambahkan.');
    }

    /**
     * Update the specified package in storage.
     */
    public function update(Request $request, Package $package): RedirectResponse
    {
        // Support method spoofing for file uploads
        if ($request->has('_method') && $request->input('_method') === 'PUT') {
            $request->merge(['is_active' => filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN)]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'duration_minutes' => ['required', 'integer', 'min:1'],
            'is_active' => ['required', 'boolean'],
            'image' => ['nullable', 'image', 'max:5120'],
        ]);

        $data = $validated;
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($package->image_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($package->image_path)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($package->image_path);
            }
            $data['image_path'] = $request->file('image')->store('packages', 'public');
        }

        $package->update($data);

        return redirect()->route('admin.packages.index')->with('success', 'Paket foto berhasil diperbarui.');
    }

    /**
     * Remove the specified package from storage.
     */
    public function destroy(Package $package): RedirectResponse
    {
        // Hindari penghapusan jika ada booking yang terikat (foreign key restrict akan memicu PDOException)
        if ($package->bookings()->exists()) {
            return redirect()->route('admin.packages.index')->with('error', 'Paket tidak dapat dihapus karena sudah memiliki pemesanan aktif.');
        }

        $package->delete();

        return redirect()->route('admin.packages.index')->with('success', 'Paket foto berhasil dihapus.');
    }

    /**
     * Toggle the active status of the package.
     */
    public function toggleActive(Package $package): RedirectResponse
    {
        $package->update([
            'is_active' => !$package->is_active
        ]);

        return redirect()->route('admin.packages.index')->with('success', 'Status keaktifan paket berhasil diubah.');
    }
}
