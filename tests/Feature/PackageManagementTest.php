<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PackageManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
    }

    /**
     * Test admin can store package with an image.
     */
    public function test_admin_can_store_package_with_image(): void
    {
        Storage::fake('public');

        $imageFile = UploadedFile::fake()->image('standard_package.png');

        $packageData = [
            'name' => 'Premium Studio Package',
            'description' => 'Premium quality photo session.',
            'price' => 1200000.00,
            'duration_minutes' => 120,
            'image' => $imageFile,
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.packages.store'), $packageData);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $package = Package::where('name', 'Premium Studio Package')->first();
        $this->assertNotNull($package);
        $this->assertNotNull($package->image_path);

        Storage::disk('public')->assertExists($package->image_path);
    }

    /**
     * Test admin can update package with a new image.
     */
    public function test_admin_can_update_package_with_new_image(): void
    {
        Storage::fake('public');

        // Create initial package
        $oldImagePath = UploadedFile::fake()->image('old.png')->store('packages', 'public');
        $package = Package::create([
            'name' => 'Old Package Name',
            'description' => 'Old description',
            'price' => 500000.00,
            'duration_minutes' => 60,
            'is_active' => true,
            'image_path' => $oldImagePath,
        ]);

        Storage::disk('public')->assertExists($oldImagePath);

        // Update package
        $newImageFile = UploadedFile::fake()->image('new.png');
        $updateData = [
            '_method' => 'PUT',
            'name' => 'Updated Package Name',
            'description' => 'Updated description',
            'price' => 600000.00,
            'duration_minutes' => 90,
            'is_active' => 'true',
            'image' => $newImageFile,
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.packages.update', $package->id), $updateData);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $package->refresh();
        $this->assertEquals('Updated Package Name', $package->name);
        $this->assertNotEquals($oldImagePath, $package->image_path);

        // Assert old file was deleted and new file exists
        Storage::disk('public')->assertMissing($oldImagePath);
        Storage::disk('public')->assertExists($package->image_path);
    }
}
