<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingPhoto;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BookingGalleryTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Package $package;
    private Booking $booking;
    private string $secureHash;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock public disk storage
        Storage::fake('local');
        Storage::fake('public');

        // Create an admin
        $this->admin = User::factory()->create();

        // Create a package
        $this->package = Package::create([
            'name' => 'Premium Studio Portrait',
            'description' => 'Premium package description',
            'price' => 1000000.00,
            'duration_minutes' => 120,
            'is_active' => true,
        ]);

        // Create a booking
        $this->booking = Booking::create([
            'package_id' => $this->package->id,
            'client_name' => 'Alice Doe',
            'client_email' => 'alice@doe.com',
            'client_phone' => '08123456789',
            'booking_date' => '2026-07-15',
            'start_time' => '14:00:00',
            'end_time' => '16:00:00',
            'status' => 'confirmed',
            'total_price' => 1000000.00,
            'paid_amount' => 300000.00, // DP paid
            'location' => 'Studio Premium',
        ]);

        // Generate expected secure hash for client link
        $this->secureHash = md5($this->booking->id . $this->booking->client_email . config('app.key'));
    }

    /**
     * Test admin gallery page requires auth.
     */
    public function test_admin_gallery_page_requires_auth(): void
    {
        $response = $this->get("/admin/bookings/{$this->booking->id}/gallery");
        $response->assertRedirect('/login');
    }

    /**
     * Test admin can load gallery management page.
     */
    public function test_admin_can_load_gallery_management(): void
    {
        $response = $this->actingAs($this->admin)->get("/admin/bookings/{$this->booking->id}/gallery");
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Bookings/Gallery')
            ->has('booking')
            ->has('clientGalleryUrl')
        );
    }

    /**
     * Test admin can upload photos to booking gallery.
     */
    public function test_admin_can_upload_photos(): void
    {
        // Fake 2 photos
        $file1 = UploadedFile::fake()->image('raw_photo1.jpg');
        $file2 = UploadedFile::fake()->image('raw_photo2.png');

        $response = $this->actingAs($this->admin)->post("/admin/bookings/{$this->booking->id}/gallery/upload", [
            'photos' => [$file1, $file2],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Check if database contains the records
        $this->assertDatabaseCount('booking_photos', 2);
        
        // Retrieve and check the photo record
        $photo = BookingPhoto::first();
        $this->assertNotNull($photo->watermarked_file_path);
        
        // Assert files exist on fake disk
        Storage::disk('public')->assertExists($photo->file_path);
        Storage::disk('public')->assertExists($photo->watermarked_file_path);
    }

    /**
     * Test client gallery secure hash validation.
     */
    public function test_client_gallery_secure_hash_validation(): void
    {
        // 1. Invalid Hash (Forbidden)
        $response = $this->get("/booking/{$this->booking->id}/gallery/invalidhash123");
        $response->assertStatus(403);

        // 2. Valid Hash (Success)
        $response = $this->get("/booking/{$this->booking->id}/gallery/{$this->secureHash}");
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Client/Gallery')
            ->has('booking')
            ->has('hash')
        );
    }

    /**
     * Test client can submit proofing photo selections.
     */
    public function test_client_can_submit_proofing_selections(): void
    {
        // Create 3 photos in DB first
        $photo1 = BookingPhoto::create([
            'booking_id' => $this->booking->id,
            'file_path' => 'public/galleries/test/photo1.jpg',
            'is_selected' => false,
            'status' => 'raw',
        ]);
        $photo2 = BookingPhoto::create([
            'booking_id' => $this->booking->id,
            'file_path' => 'public/galleries/test/photo2.jpg',
            'is_selected' => false,
            'status' => 'raw',
        ]);
        $photo3 = BookingPhoto::create([
            'booking_id' => $this->booking->id,
            'file_path' => 'public/galleries/test/photo3.jpg',
            'is_selected' => false,
            'status' => 'raw',
        ]);

        // Client submits selection: select photo1 and photo2
        $response = $this->post("/booking/{$this->booking->id}/gallery/{$this->secureHash}/select", [
            'photo_ids' => [$photo1->id, $photo2->id],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Check selected status in DB
        $this->assertDatabaseHas('booking_photos', ['id' => $photo1->id, 'is_selected' => true, 'status' => 'selected']);
        $this->assertDatabaseHas('booking_photos', ['id' => $photo2->id, 'is_selected' => true, 'status' => 'selected']);
        $this->assertDatabaseHas('booking_photos', ['id' => $photo3->id, 'is_selected' => false, 'status' => 'raw']);
    }

    /**
     * Test admin can upload edited version of a selected photo.
     */
    public function test_admin_can_upload_edited_version(): void
    {
        $photo = BookingPhoto::create([
            'booking_id' => $this->booking->id,
            'file_path' => 'public/galleries/test/photo1.jpg',
            'is_selected' => true,
            'status' => 'selected',
        ]);

        $editedFile = UploadedFile::fake()->image('edited_photo1.jpg');

        $response = $this->actingAs($this->admin)->post("/admin/bookings/gallery/{$photo->id}/upload-edited", [
            'edited_photo' => $editedFile,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Check update in DB
        $this->assertDatabaseHas('booking_photos', [
            'id' => $photo->id,
            'is_selected' => true,
            'status' => 'edited',
        ]);

        // Refresh and check path
        $photo->refresh();
        $this->assertNotNull($photo->edited_file_path);
    }

    /**
     * Test raw_url accessor serves watermark depending on payment status.
     */
    public function test_gallery_accessor_shows_watermarked_or_raw_url_depending_on_payment_status(): void
    {
        // 1. Create a photo with a watermark file path
        $photo = BookingPhoto::create([
            'booking_id' => $this->booking->id,
            'file_path' => 'galleries/test/raw/photo.jpg',
            'watermarked_file_path' => 'galleries/test/watermarked/photo.jpg',
            'is_selected' => false,
            'status' => 'raw',
        ]);

        // 2. Booking is unpaid (DP is paid but total is 1,000,000 and paid_amount is 300,000)
        $this->assertEquals(1000000.00, (float) $this->booking->total_price);
        $this->assertEquals(300000.00, (float) $this->booking->paid_amount);

        // Accessor should return the watermarked URL
        $photo->refresh();
        $this->assertStringContainsString('galleries/test/watermarked/photo.jpg', $photo->raw_url);

        // 3. Mark booking as fully paid
        $this->booking->update([
            'paid_amount' => 1000000.00
        ]);

        // Accessor should now return the original raw URL
        $photo->refresh();
        $this->assertStringContainsString('galleries/test/raw/photo.jpg', $photo->raw_url);
    }

    /**
     * Test R2 disk config initialization.
     */
    public function test_r2_disk_can_be_resolved_from_filesystem_config(): void
    {
        $disk = Storage::disk('r2');
        $this->assertInstanceOf(\Illuminate\Contracts\Filesystem\Filesystem::class, $disk);
        
        // Assert it uses the s3 driver
        $this->assertEquals('s3', config('filesystems.disks.r2.driver'));
    }
}
