<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PackageController;
use App\Http\Controllers\Admin\EquipmentController;
use App\Http\Controllers\Admin\CrewController;
use App\Http\Controllers\Admin\BookingController as AdminBookingController;
use App\Http\Controllers\Client\BookingController as ClientBookingController;
use App\Http\Controllers\Client\ContractController;
use App\Http\Controllers\Client\PaymentController;
use App\Http\Controllers\Admin\BookingGalleryController as AdminGalleryController;
use App\Http\Controllers\Client\BookingGalleryController as ClientGalleryController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Admin\ContractController as AdminContractController;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\ExpenseController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Public Welcome Route
Route::get('/', function () {
    $packages = \App\Models\Package::where('is_active', true)->orderBy('price', 'asc')->get();
    
    $gallery = \App\Models\BookingPhoto::whereHas('booking', function ($query) {
        $query->where('status', 'completed');
    })
    ->orderBy('created_at', 'desc')
    ->limit(12)
    ->get()
    ->map(function ($photo) {
        return [
            'id' => $photo->id,
            'url' => '/storage/' . ($photo->edited_file_path ?? $photo->watermarked_file_path ?? $photo->file_path),
            'caption' => $photo->booking->package->name ?? 'Sesi Foto',
        ];
    });

    return Inertia::render('Welcome', [
        'packages' => $packages,
        'gallery' => $gallery,
        'laravelVersion' => ltrim(app()->version(), 'v'),
        'phpVersion' => PHP_VERSION,
    ]);
});

// Public Client Booking Routes
Route::get('/booking', [ClientBookingController::class, 'index'])->name('booking.index');
Route::post('/booking', [ClientBookingController::class, 'store'])->name('booking.store');
Route::get('/booking/success', [ClientBookingController::class, 'success'])->name('booking.success');

// Public Client E-Contract Routes
Route::get('/contract/{booking}/{hash}', [ContractController::class, 'show'])->name('contract.show');
Route::post('/contract/{booking}/{hash}/sign', [ContractController::class, 'sign'])->name('contract.sign');

// Public Client Payment Routes
Route::get('/booking/{booking}/payment/{hash}', [PaymentController::class, 'show'])->name('booking.payment');
Route::post('/booking/{booking}/payment/{hash}/initiate', [PaymentController::class, 'initiatePayment'])->name('booking.payment.initiate');
Route::post('/booking/{booking}/payment/{hash}/simulate-webhook', [PaymentController::class, 'simulateWebhook'])->name('booking.payment.simulate');

// Public Client Gallery & Proofing Routes
Route::get('/booking/{booking}/gallery/{hash}', [ClientGalleryController::class, 'show'])->name('booking.gallery.show');
Route::post('/booking/{booking}/gallery/{hash}/select', [ClientGalleryController::class, 'selectPhotos'])->name('booking.gallery.select');

// Midtrans Webhook Callback (CSRF Exempted in bootstrap/app.php)
Route::post('/midtrans/notification', [PaymentController::class, 'notification'])->name('midtrans.notification');

// Authentication Routes (Guest)
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

// Admin Protected Routes (Auth)
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('/admin/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');

    // Payments & Contracts (Admin)
    Route::get('/admin/payments', [AdminPaymentController::class, 'index'])->name('admin.payments.index');
    Route::get('/admin/contracts', [AdminContractController::class, 'index'])->name('admin.contracts.index');

    // Analytics & Expenses (Admin)
    Route::get('/admin/analytics', [AnalyticsController::class, 'index'])->name('admin.analytics.index');
    Route::get('/admin/analytics/report', [AnalyticsController::class, 'report'])->name('admin.analytics.report');
    Route::get('/admin/expenses', [ExpenseController::class, 'index'])->name('admin.expenses.index');
    Route::post('/admin/expenses', [ExpenseController::class, 'store'])->name('admin.expenses.store');
    Route::put('/admin/expenses/{expense}', [ExpenseController::class, 'update'])->name('admin.expenses.update');
    Route::delete('/admin/expenses/{expense}', [ExpenseController::class, 'destroy'])->name('admin.expenses.destroy');

    // Package Management (Admin)
    Route::get('/admin/packages', [PackageController::class, 'index'])->name('admin.packages.index');
    Route::post('/admin/packages', [PackageController::class, 'store'])->name('admin.packages.store');
    Route::put('/admin/packages/{package}', [PackageController::class, 'update'])->name('admin.packages.update');
    Route::delete('/admin/packages/{package}', [PackageController::class, 'destroy'])->name('admin.packages.destroy');
    Route::patch('/admin/packages/{package}/toggle', [PackageController::class, 'toggleActive'])->name('admin.packages.toggle');

    // Equipment Management (Admin)
    Route::get('/admin/equipments', [EquipmentController::class, 'index'])->name('admin.equipments.index');
    Route::post('/admin/equipments', [EquipmentController::class, 'store'])->name('admin.equipments.store');
    Route::put('/admin/equipments/{equipment}', [EquipmentController::class, 'update'])->name('admin.equipments.update');
    Route::delete('/admin/equipments/{equipment}', [EquipmentController::class, 'destroy'])->name('admin.equipments.destroy');

    // Crew Management (Admin)
    Route::get('/admin/crews/kanban', [CrewController::class, 'kanban'])->name('admin.crews.kanban');
    Route::get('/admin/crews', [CrewController::class, 'index'])->name('admin.crews.index');
    Route::post('/admin/crews', [CrewController::class, 'store'])->name('admin.crews.store');
    Route::put('/admin/crews/{crew}', [CrewController::class, 'update'])->name('admin.crews.update');
    Route::delete('/admin/crews/{crew}', [CrewController::class, 'destroy'])->name('admin.crews.destroy');

    // Booking Management (Admin)
    Route::get('/admin/bookings', [AdminBookingController::class, 'index'])->name('admin.bookings.index');
    Route::patch('/admin/bookings/{booking}/status', [AdminBookingController::class, 'updateStatus'])->name('admin.bookings.status');
    Route::delete('/admin/bookings/{booking}', [AdminBookingController::class, 'destroy'])->name('admin.bookings.destroy');
    Route::post('/admin/bookings/{booking}/assign', [AdminBookingController::class, 'assignResources'])->name('admin.bookings.assign');

    // Booking Gallery Management (Admin)
    Route::get('/admin/bookings/{booking}/gallery', [AdminGalleryController::class, 'index'])->name('admin.bookings.gallery.index');
    Route::post('/admin/bookings/{booking}/gallery/upload', [AdminGalleryController::class, 'upload'])->name('admin.bookings.gallery.upload');
    Route::delete('/admin/bookings/gallery/{photo}', [AdminGalleryController::class, 'destroy'])->name('admin.bookings.gallery.destroy');
    Route::post('/admin/bookings/gallery/{photo}/upload-edited', [AdminGalleryController::class, 'uploadEdited'])->name('admin.bookings.gallery.upload_edited');

    // Company Settings (Admin)
    Route::get('/admin/settings', [\App\Http\Controllers\Admin\SettingController::class, 'index'])->name('admin.settings.index');
    Route::put('/admin/settings', [\App\Http\Controllers\Admin\SettingController::class, 'update'])->name('admin.settings.update');

    // CRM & Lifecycle Marketing (Admin)
    Route::get('/admin/crm', [\App\Http\Controllers\Admin\CRMController::class, 'index'])->name('admin.crm.index');
    Route::post('/admin/crm/rules', [\App\Http\Controllers\Admin\CRMController::class, 'storeRule'])->name('admin.crm.rules.store');
    Route::put('/admin/crm/rules/{rule}', [\App\Http\Controllers\Admin\CRMController::class, 'updateRule'])->name('admin.crm.rules.update');
    Route::delete('/admin/crm/rules/{rule}', [\App\Http\Controllers\Admin\CRMController::class, 'destroyRule'])->name('admin.crm.rules.destroy');
    Route::post('/admin/crm/schedules/{schedule}/send', [\App\Http\Controllers\Admin\CRMController::class, 'sendNow'])->name('admin.crm.schedules.send');
    Route::post('/admin/crm/schedules/run-cron', [\App\Http\Controllers\Admin\CRMController::class, 'runSimulatedCron'])->name('admin.crm.schedules.run-cron');
});
