<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Crew;
use App\Models\Equipment;
use App\Services\WhatsAppService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class BookingController extends Controller
{
    protected WhatsAppService $whatsAppService;

    /**
     * Inject WhatsAppService to handle notifications.
     */
    public function __construct(WhatsAppService $whatsAppService)
    {
        $this->whatsAppService = $whatsAppService;
    }

    /**
     * Display a listing of bookings for admin.
     */
    public function index(): Response
    {
        $bookings = Booking::with(['package', 'contract', 'crews', 'equipments'])
            ->orderBy('booking_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->get()
            ->map(function ($booking) {
                $hash = md5($booking->id . $booking->client_email . config('app.key'));
                $booking->contract_url = route('contract.show', ['booking' => $booking->id, 'hash' => $hash]);
                $booking->payment_url = route('booking.payment', ['booking' => $booking->id, 'hash' => $hash]);
                return $booking;
            });

        $allCrews = Crew::where('is_active', true)->orderBy('name', 'asc')->get();
        $allEquipments = Equipment::where('status', 'active')->orderBy('name', 'asc')->get();

        return Inertia::render('Admin/Bookings/Index', [
            'bookings' => $bookings,
            'allCrews' => $allCrews,
            'allEquipments' => $allEquipments,
        ]);
    }

    /**
     * Update the status of a booking and trigger WhatsApp notification.
     */
    public function updateStatus(Request $request, Booking $booking): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:pending,confirmed,completed,cancelled'],
        ]);

        $oldStatus = $booking->status;
        $newStatus = $validated['status'];

        $booking->update([
            'status' => $newStatus,
        ]);

        // Jika status selesai, anggap lunas untuk simulasi dasar
        if ($newStatus === 'completed') {
            $booking->update([
                'paid_amount' => $booking->total_price,
            ]);

            if ($oldStatus !== 'completed') {
                $this->scheduleMarketing($booking);
            }
        }

        $waSent = true;

        // Kirim WhatsApp secara asinkron jika status berubah ke terkonfirmasi atau batal
        if ($newStatus !== $oldStatus) {
            $formattedDate = $booking->booking_date->format('d-m-Y');
            $formattedStartTime = substr($booking->start_time, 0, 5);
            $formattedEndTime = substr($booking->end_time, 0, 5);
            $packageName = $booking->package->name;

            if ($newStatus === 'confirmed') {
                $hash = md5($booking->id . $booking->client_email . config('app.key'));
                $contractUrl = route('contract.show', ['booking' => $booking->id, 'hash' => $hash]);

                $message = "Halo *{$booking->client_name}*,\n\nKami menginformasikan bahwa jadwal sesi foto Anda (*{$packageName}*) untuk tanggal *{$formattedDate}* pukul *{$formattedStartTime} - {$formattedEndTime} WIB* telah *DIKONFIRMASI*.\n\nLangkah selanjutnya silakan tanda tangani Surat Perjanjian Kerja (SPK) digital Anda melalui tautan berikut:\n{$contractUrl}\n\nSetelah tanda tangan selesai, Anda dapat melanjutkan ke pembayaran Uang Muka (DP).\n\nTerima kasih,\nPhoto Studio Team";
                $waSent = $this->whatsAppService->sendMessage($booking->client_phone, $message);
            } elseif ($newStatus === 'cancelled') {
                $message = "Halo *{$booking->client_name}*,\n\nKami menginformasikan bahwa jadwal sesi foto Anda untuk tanggal *{$formattedDate}* telah *DIBATALKAN*.\n\nJika ada kekeliruan atau ingin mengatur ulang jadwal, silakan hubungi tim CS kami.\n\nTerima kasih,\nPhoto Studio Team";
                $waSent = $this->whatsAppService->sendMessage($booking->client_phone, $message);
            }
        }

        if (!$waSent) {
            $errorMsg = $this->whatsAppService->getLastError() ?: 'Fonnte API bermasalah/token tidak valid';
            return redirect()->route('admin.bookings.index')
                ->with('success', 'Status pemesanan berhasil diperbarui.')
                ->with('error', 'Status pemesanan diperbarui, tetapi pesan WhatsApp gagal terkirim: ' . $errorMsg);
        }

        return redirect()->route('admin.bookings.index')->with('success', 'Status pemesanan berhasil diperbarui.');
    }

    /**
     * Delete a booking.
     */
    public function destroy(Booking $booking): RedirectResponse
    {
        $booking->delete();

        return redirect()->route('admin.bookings.index')->with('success', 'Pemesanan berhasil dihapus dari sistem.');
    }

    /**
     * Assign crews and equipments to a booking with overlap checking.
     */
    public function assignResources(Request $request, Booking $booking): RedirectResponse
    {
        $validated = $request->validate([
            'crew_ids' => ['nullable', 'array'],
            'crew_ids.*' => ['exists:crews,id'],
            'equipment_ids' => ['nullable', 'array'],
            'equipment_ids.*' => ['exists:equipments,id'],
        ]);

        $crewIds = $validated['crew_ids'] ?? [];
        $equipmentIds = $validated['equipment_ids'] ?? [];

        // Validate Crews for overlapping schedules
        foreach ($crewIds as $crewId) {
            $crew = Crew::find($crewId);
            $overlapExists = $crew->bookings()
                ->where('bookings.id', '!=', $booking->id)
                ->where('booking_date', $booking->booking_date)
                ->where('status', '!=', 'cancelled')
                ->where(function ($query) use ($booking) {
                    $query->where('start_time', '<', $booking->end_time)
                          ->where('end_time', '>', $booking->start_time);
                })
                ->exists();

            if ($overlapExists) {
                return back()->with('error', "Gagal: Staf {$crew->name} ({$crew->role}) sudah dijadwalkan pada sesi foto lain di jam yang bentrok.");
            }
        }

        // Validate Equipment for overlapping allocations
        foreach ($equipmentIds as $equipmentId) {
            $equipment = Equipment::find($equipmentId);
            $overlapExists = $equipment->bookings()
                ->where('bookings.id', '!=', $booking->id)
                ->where('booking_date', $booking->booking_date)
                ->where('status', '!=', 'cancelled')
                ->where(function ($query) use ($booking) {
                    $query->where('start_time', '<', $booking->end_time)
                          ->where('end_time', '>', $booking->start_time);
                })
                ->exists();

            if ($overlapExists) {
                return back()->with('error', "Gagal: Alat {$equipment->name} ({$equipment->type}) sudah dialokasikan ke sesi foto lain di jam yang bentrok.");
            }
        }

        try {
            DB::transaction(function () use ($booking, $crewIds, $equipmentIds) {
                $booking->crews()->sync($crewIds);
                $booking->equipments()->sync($equipmentIds);
            });

            return back()->with('success', 'Kru dan alat penunjang sesi berhasil dialokasikan.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menyimpan alokasi: ' . $e->getMessage());
        }
    }

    /**
     * Automatically schedule marketing messages based on lifecycle rules.
     */
    protected function scheduleMarketing(Booking $booking): void
    {
        $booking->loadMissing('package');

        $rules = \App\Models\LifecycleRule::where('source_package_id', $booking->package_id)
            ->where('is_active', true)
            ->get();

        foreach ($rules as $rule) {
            $targetPackage = $rule->targetPackage;
            if (!$targetPackage) {
                continue;
            }

            // Calculate scheduled date (booking_date + delay_days)
            $baseDate = $booking->booking_date ? \Carbon\Carbon::parse($booking->booking_date) : now();
            $scheduledAt = $baseDate->addDays($rule->delay_days);

            // Replace template placeholders
            $formattedPrice = 'Rp ' . number_format($targetPackage->price, 0, ',', '.');
            $messageContent = str_replace(
                ['{client_name}', '{source_package}', '{target_package}', '{target_price}'],
                [$booking->client_name, $booking->package->name, $targetPackage->name, $formattedPrice],
                $rule->message_template
            );

            \App\Models\MarketingSchedule::create([
                'booking_id' => $booking->id,
                'lifecycle_rule_id' => $rule->id,
                'client_name' => $booking->client_name,
                'client_phone' => $booking->client_phone,
                'message_content' => $messageContent,
                'scheduled_at' => $scheduledAt,
                'status' => 'pending',
            ]);
        }
    }
}
