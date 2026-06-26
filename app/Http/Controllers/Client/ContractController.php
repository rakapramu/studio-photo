<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Contract;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ContractController extends Controller
{
    /**
     * Helper to verify URL signature/hash.
     */
    protected function verifyHash(Booking $booking, string $hash): bool
    {
        return $hash === md5($booking->id . $booking->client_email . config('app.key'));
    }

    /**
     * Show the contract page for the client.
     */
    public function show(Booking $booking, string $hash): Response
    {
        if (!$this->verifyHash($booking, $hash)) {
            abort(403, 'Akses tidak sah (Invalid Signature).');
        }

        // Cari atau buat draf kontrak baru jika belum ada
        $contract = Contract::where('booking_id', $booking->id)->first();

        if (!$contract) {
            $formattedDate = $booking->booking_date->format('d-m-Y');
            $startTime = substr($booking->start_time, 0, 5);
            $endTime = substr($booking->end_time, 0, 5);
            $priceText = number_format($booking->total_price, 0, ',', '.');
            $packagePriceText = number_format($booking->package->price, 0, ',', '.');
            $surchargeText = number_format($booking->travel_surcharge, 0, ',', '.');

            $studioName = \App\Models\Setting::getValue('studio_name', 'Photo Studio');

            $contractText = "SURAT PERJANJIAN KERJA (SPK)\n\n"
                . "Perjanjian ini dibuat pada tanggal " . date('d-m-Y') . " antara:\n"
                . "1. Nama: {$studioName} (Selaku penyedia jasa dokumentasi)\n"
                . "2. Nama Klien: {$booking->client_name} (Selaku pengguna jasa)\n\n"
                . "Dengan ini kedua belah pihak sepakat untuk melakukan kerja sama dokumentasi dengan ketentuan:\n"
                . "- Paket Sesi: {$booking->package->name}\n"
                . "- Tanggal Pemotretan: {$formattedDate}\n"
                . "- Waktu Sesi: {$startTime} - {$endTime} WIB\n"
                . "- Tipe Sesi: " . ($booking->is_outdoor ? "Outdoor (Luar Studio)" : "Indoor (di Studio)") . "\n"
                . "- Lokasi: {$booking->location}\n";

            if ($booking->is_outdoor) {
                $contractText .= "- Jarak Tempuh: {$booking->travel_distance} KM\n"
                    . "- Nilai Paket Sesi: Rp {$packagePriceText},-\n"
                    . "- Biaya Tambahan Perjalanan: Rp {$surchargeText},-\n"
                    . "  (Bensin: Rp " . number_format($booking->fuel_cost, 0, ',', '.') . ", Tol: Rp " . number_format($booking->toll_cost, 0, ',', '.') . ", Akomodasi: Rp " . number_format($booking->accommodation_cost, 0, ',', '.') . ")\n";
            }

            $contractText .= "- Total Nilai Kontrak: Rp {$priceText},-\n\n"
                . "PASAL 1: PEMBAYARAN\n"
                . "Klien wajib membayar uang muka (DP) sebesar 30% dari total kontrak setelah SPK ditandatangani untuk mengamankan slot jadwal. Pelunasan wajib diselesaikan paling lambat pada hari H pelaksanaan sebelum sesi foto dimulai.\n\n"
                . "PASAL 2: PEMBATALAN & JADWAL ULANG\n"
                . "Pembatalan oleh klien secara sepihak akan mengakibatkan uang muka (DP) hangus. Jadwal ulang (reschedule) diperbolehkan maksimal 1 kali dengan pemberitahuan minimal H-3 sebelum jadwal awal.\n\n"
                . "PASAL 3: HAK CIPTA & KEPEMILIKAN GAMBAR\n"
                . "Hak cipta gambar tetap berada pada {$studioName}. Klien diberikan hak guna gambar untuk kepentingan pribadi dan promosi non-komersil. {$studioName} berhak menggunakan foto untuk kepentingan portofolio dan promosi media sosial, kecuali jika klien meminta secara tertulis untuk menjaga privasi.";

            $contract = Contract::create([
                'booking_id' => $booking->id,
                'contract_text' => $contractText,
            ]);
        }

        return Inertia::render('Client/Contract', [
            'booking' => $booking->load('package'),
            'contract' => $contract,
            'hash' => $hash,
        ]);
    }

    /**
     * Submit signature and sign the contract.
     */
    public function sign(Request $request, Booking $booking, string $hash): RedirectResponse
    {
        if (!$this->verifyHash($booking, $hash)) {
            abort(403, 'Akses tidak sah.');
        }

        $request->validate([
            'signature_image' => ['required', 'string'], // Data URI base64
        ]);

        $contract = Contract::where('booking_id', $booking->id)->firstOrFail();

        if ($contract->signed_at) {
            return redirect()->back()->with('error', 'Kontrak ini sudah ditandatangani sebelumnya.');
        }

        // Proses pengunggahan gambar tanda tangan base64
        $image = $request->signature_image;
        $image = str_replace('data:image/png;base64,', '', $image);
        $image = str_replace(' ', '+', $image);
        $imageName = 'sig_' . $booking->id . '_' . Str::random(10) . '.png';

        // Buat folder signatures jika belum ada
        if (!Storage::disk('public')->exists('signatures')) {
            Storage::disk('public')->makeDirectory('signatures');
        }

        Storage::disk('public')->put('signatures/' . $imageName, base64_decode($image));

        // Update database
        $contract->update([
            'signature_path' => '/storage/signatures/' . $imageName,
            'signed_at' => now(),
            'ip_address' => $request->ip(),
        ]);
        
        return redirect()->route('booking.payment', ['booking' => $booking->id, 'hash' => $hash])
            ->with('success', 'Kontrak SPK berhasil ditandatangani secara digital. Silakan lanjutkan ke pembayaran Uang Muka (DP).');
    }
}
