<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanySettingsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test unauthenticated users cannot view company settings.
     */
    public function test_guest_cannot_access_settings(): void
    {
        $response = $this->get(route('admin.settings.index'));

        $response->assertRedirect(route('login'));
    }

    /**
     * Test admin can view company settings.
     */
    public function test_admin_can_view_settings(): void
    {
        $admin = User::factory()->create();

        // Seed settings
        Setting::setValue('studio_name', 'Studio Raka Test');

        $response = $this->actingAs($admin)->get(route('admin.settings.index'));

        $response->assertStatus(200);
        $response->assertSee('Studio Raka Test');
    }

    /**
     * Test admin can update company settings with valid data.
     */
    public function test_admin_can_update_settings(): void
    {
        $admin = User::factory()->create();

        $newData = [
            'studio_name' => 'Studio Photo Baru',
            'studio_address' => 'Jl. Baru No. 10',
            'studio_latitude' => '-7.250444',
            'studio_longitude' => '112.768845',
            'fuel_cost_per_km' => 3000,
            'accommodation_cost_per_night' => 600000,
        ];

        $response = $this->actingAs($admin)->put(route('admin.settings.update'), $newData);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Check values in DB
        $this->assertEquals('Studio Photo Baru', Setting::getValue('studio_name'));
        $this->assertEquals('Jl. Baru No. 10', Setting::getValue('studio_address'));
        $this->assertEquals('-7.250444', Setting::getValue('studio_latitude'));
        $this->assertEquals('112.768845', Setting::getValue('studio_longitude'));
        $this->assertEquals('3000', Setting::getValue('fuel_cost_per_km'));
        $this->assertEquals('600000', Setting::getValue('accommodation_cost_per_night'));
    }

    /**
     * Test settings update validation rules.
     */
    public function test_settings_update_validation(): void
    {
        $admin = User::factory()->create();

        // Send invalid coordinates and negative pricing
        $invalidData = [
            'studio_name' => '',
            'studio_address' => '',
            'studio_latitude' => '95.000', // max 90
            'studio_longitude' => '-190.000', // min -180
            'fuel_cost_per_km' => -500, // min 0
            'accommodation_cost_per_night' => -1000, // min 0
        ];

        $response = $this->actingAs($admin)->put(route('admin.settings.update'), $invalidData);

        $response->assertSessionHasErrors([
            'studio_name',
            'studio_address',
            'studio_latitude',
            'studio_longitude',
            'fuel_cost_per_km',
            'accommodation_cost_per_night'
        ]);
    }
}
