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
            ],
            [
                'name' => 'Maternity Portrait Session',
                'description' => 'Abadikan momen kehamilan indah Anda dengan sesi foto maternity eksklusif. Termasuk kostum pilihan, 10 foto edit pasca-produksi, dan cetakan bingkai ukuran 12R.',
                'price' => 850000.00,
                'duration_minutes' => 90,
                'is_active' => true,
            ],
            [
                'name' => 'Newborn & Baby Photography',
                'description' => 'Sesi foto bayi baru lahir yang aman dan dipandu oleh spesialis bayi berpengalaman. Menyediakan berbagai properti lucu, kostum lembut, dan 15 foto edit terbaik.',
                'price' => 1200000.00,
                'duration_minutes' => 120,
                'is_active' => true,
            ]
        ];

        foreach ($packages as $pkg) {
            Package::firstOrCreate(['name' => $pkg['name']], $pkg);
        }

        // Seed default lifecycle rules
        $wedding = Package::where('name', 'Full Wedding Documentation')->first();
        $maternity = Package::where('name', 'Maternity Portrait Session')->first();
        $newborn = Package::where('name', 'Newborn & Baby Photography')->first();
        $family = Package::where('name', 'Family & Group Portrait')->first();

        if ($wedding && $maternity && $newborn && $family) {
            $rules = [
                [
                    'name' => 'Wedding to Maternity Promotion (10 Months)',
                    'source_package_id' => $wedding->id,
                    'target_package_id' => $maternity->id,
                    'delay_days' => 300,
                    'message_template' => "Halo {client_name},\n\nKami dari Photo Studio Team ingin mengucapkan selamat atas peringatan pernikahan Anda yang ke-10 bulan! 🎉\n\nMenyambut kebahagiaan baru dalam hidup Anda, kami menawarkan promo eksklusif untuk sesi foto kehamilan (*{target_package}*) dengan harga spesial *{target_price}*.\n\nMari abadikan momen berharga ini bersama kami. Hubungi CS kami untuk info selengkapnya ya!\n\nSalam hangat,\nPhoto Studio Team",
                    'is_active' => true,
                ],
                [
                    'name' => 'Maternity to Newborn Promotion (2 Months)',
                    'source_package_id' => $maternity->id,
                    'target_package_id' => $newborn->id,
                    'delay_days' => 60,
                    'message_template' => "Halo {client_name},\n\nSemoga ibu dan calon bayi selalu dalam keadaan sehat dan bahagia. 💕\n\nMenjelang persalinan, jangan lewatkan kesempatan untuk mengabadikan kelucuan sang buah hati yang baru lahir dengan sesi foto *{target_package}* kami.\n\nDapatkan penawaran khusus hanya seharga *{target_price}* untuk booking hari ini!\n\nSalam hangat,\nPhoto Studio Team",
                    'is_active' => true,
                ],
                [
                    'name' => 'Newborn to Family Portrait Annual (1 Year)',
                    'source_package_id' => $newborn->id,
                    'target_package_id' => $family->id,
                    'delay_days' => 365,
                    'message_template' => "Halo {client_name},\n\nWah, tidak terasa sudah satu tahun berlalu sejak sesi foto Newborn sang buah hati tercinta! 🎂👶\n\nSekarang adalah waktu yang tepat untuk melakukan sesi foto keluarga tahunan (*{target_package}*) bersama seluruh keluarga besar Anda seharga *{target_price}*.\n\nMari jadikan momen berkumpul ini abadi. Hubungi kami untuk reservasi slot ya!\n\nSalam hangat,\nPhoto Studio Team",
                    'is_active' => true,
                ],
            ];

            foreach ($rules as $rule) {
                \App\Models\LifecycleRule::firstOrCreate(['name' => $rule['name']], $rule);
            }
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
