<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LifecycleRule;
use App\Models\MarketingSchedule;
use App\Models\Package;
use App\Services\WhatsAppService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CRMController extends Controller
{
    protected WhatsAppService $whatsAppService;

    public function __construct(WhatsAppService $whatsAppService)
    {
        $this->whatsAppService = $whatsAppService;
    }

    /**
     * Display the CRM & Lifecycle Marketing Dashboard.
     */
    public function index(): Response
    {
        $rules = LifecycleRule::with(['sourcePackage', 'targetPackage'])
            ->orderBy('id', 'desc')
            ->get();

        $packages = Package::where('is_active', true)
            ->orderBy('name', 'asc')
            ->get();

        $schedules = MarketingSchedule::with(['booking.package', 'lifecycleRule.targetPackage'])
            ->orderBy('scheduled_at', 'desc')
            ->get();

        return Inertia::render('Admin/CRM/Index', [
            'rules' => $rules,
            'packages' => $packages,
            'schedules' => $schedules,
        ]);
    }

    /**
     * Store a new lifecycle rule.
     */
    public function storeRule(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'source_package_id' => ['required', 'exists:packages,id'],
            'target_package_id' => ['required', 'exists:packages,id'],
            'delay_days' => ['required', 'integer', 'min:0'],
            'message_template' => ['required', 'string'],
            'is_active' => ['boolean'],
        ]);

        LifecycleRule::create($validated);

        return redirect()->route('admin.crm.index')->with('success', 'Aturan lifecycle marketing baru berhasil ditambahkan.');
    }

    /**
     * Update an existing lifecycle rule.
     */
    public function updateRule(Request $request, LifecycleRule $rule): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'source_package_id' => ['required', 'exists:packages,id'],
            'target_package_id' => ['required', 'exists:packages,id'],
            'delay_days' => ['required', 'integer', 'min:0'],
            'message_template' => ['required', 'string'],
            'is_active' => ['boolean'],
        ]);

        $rule->update($validated);

        return redirect()->route('admin.crm.index')->with('success', 'Aturan lifecycle marketing berhasil diperbarui.');
    }

    /**
     * Delete a lifecycle rule.
     */
    public function destroyRule(LifecycleRule $rule): RedirectResponse
    {
        $rule->delete();

        return redirect()->route('admin.crm.index')->with('success', 'Aturan lifecycle marketing berhasil dihapus.');
    }

    /**
     * Send a scheduled marketing message immediately.
     */
    public function sendNow(MarketingSchedule $schedule): RedirectResponse
    {
        $success = $this->whatsAppService->sendMessage($schedule->client_phone, $schedule->message_content);

        if ($success) {
            $schedule->update([
                'status' => 'sent',
                'sent_at' => now(),
                'error_message' => null,
            ]);

            return redirect()->route('admin.crm.index')->with('success', 'Pesan promosi WhatsApp berhasil dikirim langsung ke klien.');
        }

        $error = $this->whatsAppService->getLastError() ?: 'Token Fonnte tidak valid atau jaringan bermasalah';
        $schedule->update([
            'status' => 'failed',
            'error_message' => $error,
        ]);

        return redirect()->route('admin.crm.index')->with('error', 'Gagal mengirim pesan WhatsApp: ' . $error);
    }

    /**
     * Simulate a background cron job execution for due marketing schedules.
     */
    public function runSimulatedCron(): RedirectResponse
    {
        $pendingSchedules = MarketingSchedule::where('status', 'pending')
            ->where('scheduled_at', '<=', now())
            ->get();

        if ($pendingSchedules->isEmpty()) {
            return redirect()->route('admin.crm.index')->with('success', 'Simulasi cron selesai. Tidak ada pesan pending yang jatuh tempo saat ini.');
        }

        $sentCount = 0;
        $failedCount = 0;

        foreach ($pendingSchedules as $schedule) {
            $success = $this->whatsAppService->sendMessage($schedule->client_phone, $schedule->message_content);

            if ($success) {
                $schedule->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                    'error_message' => null,
                ]);
                $sentCount++;
            } else {
                $error = $this->whatsAppService->getLastError() ?: 'Fonnte API bermasalah/token tidak valid';
                $schedule->update([
                    'status' => 'failed',
                    'error_message' => $error,
                ]);
                $failedCount++;
            }
        }

        return redirect()->route('admin.crm.index')
            ->with('success', "Simulasi cron selesai diproses. Berhasil dikirim: {$sentCount} pesan, Gagal: {$failedCount} pesan.");
    }
}
