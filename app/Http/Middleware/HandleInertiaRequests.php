<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'settings' => [
                'studio_name' => \App\Models\Setting::getValue('studio_name', 'Studio Photo Raka'),
                'studio_address' => \App\Models\Setting::getValue('studio_address', 'Bogor, Jawa Barat, Indonesia'),
                'studio_latitude' => (float) \App\Models\Setting::getValue('studio_latitude', -6.597629),
                'studio_longitude' => (float) \App\Models\Setting::getValue('studio_longitude', 106.799568),
                'fuel_cost_per_km' => (float) \App\Models\Setting::getValue('fuel_cost_per_km', 2000),
                'accommodation_cost_per_night' => (float) \App\Models\Setting::getValue('accommodation_cost_per_night', 500000),
            ],
        ];
    }
}
