<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    /**
     * Display the settings form.
     */
    public function index(): Response
    {
        $settings = [
            'studio_name' => Setting::getValue('studio_name', 'Studio Photo Raka'),
            'studio_address' => Setting::getValue('studio_address', 'Bogor, Jawa Barat, Indonesia'),
            'studio_latitude' => Setting::getValue('studio_latitude', '-6.597629'),
            'studio_longitude' => Setting::getValue('studio_longitude', '106.799568'),
            'fuel_cost_per_km' => Setting::getValue('fuel_cost_per_km', '2000'),
            'accommodation_cost_per_night' => Setting::getValue('accommodation_cost_per_night', '500000'),
        ];

        return Inertia::render('Admin/Settings/Index', [
            'settings' => $settings,
        ]);
    }

    /**
     * Update the settings.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'studio_name' => ['required', 'string', 'max:255'],
            'studio_address' => ['required', 'string'],
            'studio_latitude' => ['required', 'numeric', 'between:-90,90'],
            'studio_longitude' => ['required', 'numeric', 'between:-180,180'],
            'fuel_cost_per_km' => ['required', 'integer', 'min:0'],
            'accommodation_cost_per_night' => ['required', 'integer', 'min:0'],
        ]);

        foreach ($validated as $key => $value) {
            Setting::setValue($key, (string) $value);
        }

        return redirect()->back()->with('success', 'Pengaturan usaha berhasil diperbarui.');
    }
}
