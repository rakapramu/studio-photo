<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Package;
use App\Models\Crew;
use App\Models\Equipment;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed default admin user
        User::firstOrCreate(
            ['email' => 'admin@raka.photo'],
            [
                'name' => 'Admin Raka',
                'password' => bcrypt('password123'),
            ]
        );

        // Seed default packages
        $packages = [
            [
                'name' => 'Personal Portrait Session',
                'description' => 'Sesi foto personal portrait di studio dengan 1 pilihan latar belakang. Cocok untuk kebutuhan CV, profil profesional, atau portofolio pribadi. Free 5 foto hasil edit pasca-produksi.',
                'price' => 350000.00,
                'duration_minutes' => 60,
                'is_active' => true,
            ],
            [
                'name' => 'Couple & Prewedding Studio',
                'description' => 'Sesi foto romantis studio selama 2 jam dengan 2 pilihan gaun/pakaian. Termasuk seluruh berkas foto mentah (raw), 10 foto edit pilihan, dan 1 cetakan foto ukuran 16R lengkap dengan bingkai minimalis premium.',
                'price' => 1500000.00,
                'duration_minutes' => 120,
                'is_active' => true,
            ],
            [
                'name' => 'Family & Group Portrait',
                'description' => 'Sesi foto bersama keluarga besar atau teman-teman terdekat (kapasitas maksimum 8 orang) di area studio. Bebas mengganti pakaian hingga 2 kali. Termasuk 15 foto edit dan 1 buah cetakan album foto kolase.',
                'price' => 750000.00,
                'duration_minutes' => 90,
                'is_active' => true,
            ],
            [
                'name' => 'Full Wedding Documentation',
                'description' => 'Liputan dokumentasi hari pernikahan lengkap mulai dari persiapan, akad nikah, hingga resepsi selesai (maksimal 8 jam kerja). Ditangani oleh 2 fotografer profesional dan 1 videografer. Output berupa cinematic video (3-5 menit), album cetak eksklusif, dan seluruh aset digital diserahkan via cloud.',
                'price' => 5000000.00,
                'duration_minutes' => 480,
                'is_active' => true,
            ]
        ];

        foreach ($packages as $pkg) {
            Package::firstOrCreate(['name' => $pkg['name']], $pkg);
        }

        // Seed default crews
        $crews = [
            [
                'name' => 'Raka Pradana',
                'role' => 'fotografer',
                'phone' => '081234567890',
                'email' => 'raka@studio.com',
                'is_active' => true,
            ],
            [
                'name' => 'Dian Sastrowardoyo',
                'role' => 'fotografer',
                'phone' => '081234567891',
                'email' => 'dian@studio.com',
                'is_active' => true,
            ],
            [
                'name' => 'Budi Santoso',
                'role' => 'videografer',
                'phone' => '081234567892',
                'email' => 'budi@studio.com',
                'is_active' => true,
            ],
            [
                'name' => 'Siti Aminah',
                'role' => 'videografer',
                'phone' => '081234567893',
                'email' => 'siti@studio.com',
                'is_active' => true,
            ],
            [
                'name' => 'Rani Rahmawati',
                'role' => 'mua',
                'phone' => '081234567894',
                'email' => 'rani@studio.com',
                'is_active' => true,
            ],
            [
                'name' => 'Alisha Putri',
                'role' => 'mua',
                'phone' => '081234567895',
                'email' => 'alisha@studio.com',
                'is_active' => true,
            ],
            [
                'name' => 'Gilang Ramadhan',
                'role' => 'editor',
                'phone' => '081234567896',
                'email' => 'gilang@studio.com',
                'is_active' => true,
            ],
            [
                'name' => 'Adi Nugroho',
                'role' => 'asisten',
                'phone' => '081234567897',
                'email' => 'adi@studio.com',
                'is_active' => true,
            ]
        ];

        foreach ($crews as $crew) {
            Crew::firstOrCreate(['name' => $crew['name']], $crew);
        }

        // Seed default equipments
        $equipments = [
            [
                'name' => 'Sony A7 IV',
                'type' => 'kamera',
                'serial_number' => 'SN-SONY-A74-9988',
                'status' => 'active',
                'notes' => 'Kamera utama studio, sensor full frame.',
            ],
            [
                'name' => 'Canon EOS R5',
                'type' => 'kamera',
                'serial_number' => 'SN-CANON-R5-1122',
                'status' => 'active',
                'notes' => 'Kamera resolusi tinggi untuk prewedding.',
            ],
            [
                'name' => 'Fujifilm X-T5',
                'type' => 'kamera',
                'serial_number' => 'SN-FUJI-XT5-4433',
                'status' => 'active',
                'notes' => 'Kamera backup atau untuk portrait session.',
            ],
            [
                'name' => 'Sony FE 24-70mm f/2.8 GM II',
                'type' => 'lensa',
                'serial_number' => 'SN-LEN-2470GM-55',
                'status' => 'active',
                'notes' => 'Lensa zoom standar serbaguna.',
            ],
            [
                'name' => 'Canon RF 50mm f/1.2L USM',
                'type' => 'lensa',
                'serial_number' => 'SN-LEN-RF5012-77',
                'status' => 'active',
                'notes' => 'Lensa prime legendaris untuk bokeh maksimal.',
            ],
            [
                'name' => 'Sigma 85mm f/1.4 DG DN Art',
                'type' => 'lensa',
                'serial_number' => 'SN-LEN-SIG85-09',
                'status' => 'active',
                'notes' => 'Lensa portrait professional.',
            ],
            [
                'name' => 'Godox AD600 Pro',
                'type' => 'lighting',
                'serial_number' => 'SN-LGT-AD600-01',
                'status' => 'active',
                'notes' => 'Strobe outdoor/indoor berdaya besar.',
            ],
            [
                'name' => 'Aputure Amaran 200d',
                'type' => 'lighting',
                'serial_number' => 'SN-LGT-AM200D-02',
                'status' => 'active',
                'notes' => 'Continuous light untuk video.',
            ],
            [
                'name' => 'Kursi Bar Kayu Jati',
                'type' => 'properti',
                'serial_number' => null,
                'status' => 'active',
                'notes' => 'Properti kursi tinggi untuk sesi portrait.',
            ],
            [
                'name' => 'Sofa Minimalis Abu-abu',
                'type' => 'properti',
                'serial_number' => null,
                'status' => 'active',
                'notes' => 'Sofa studio 2 dudukan.',
            ]
        ];

        foreach ($equipments as $eq) {
            Equipment::firstOrCreate(['name' => $eq['name']], $eq);
        }
    }
}
