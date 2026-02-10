<?php

namespace App\Services;

class IconService
{
    /**
     * Get all available icons with their localized labels.
     */
    public static function getAllIcons(): array
    {
        return [
            // Kamar & Rumah
            'lucide-house' => 'Rumah',
            'lucide-home' => 'Kamar',
            'lucide-building' => 'Gedung',
            'lucide-door-closed' => 'Pintu',
            'lucide-bed' => 'Tempat Tidur',
            'lucide-armchair' => 'Kursi',
            'lucide-table' => 'Meja',
            'lucide-sofa' => 'Sofa',
            'lucide-container' => 'Lemari',
            'lucide-lamp' => 'Lampu',
            'lucide-lightbulb' => 'Bohlam',
            
            // Elektronik
            'lucide-wifi' => 'WiFi',
            'lucide-tv' => 'TV',
            'lucide-computer' => 'Komputer',
            'lucide-smartphone' => 'HP',
            'lucide-printer' => 'Printer',
            'lucide-fan' => 'Kipas',
            'lucide-snowflake' => 'AC',
            'lucide-plug' => 'Listrik',
            'lucide-zap' => 'Petir/Energi',
            
            // Kamar Mandi
            'lucide-shower-head' => 'Shower',
            'lucide-bath' => 'Bak Mandi',
            'lucide-waves' => 'Air',
            'lucide-droplets' => 'Cairan',
            'lucide-droplet' => 'Tetes Air',
            'lucide-washing-machine' => 'Cuci',
            
            // Dapur & Makan
            'lucide-utensils' => 'Makan',
            'lucide-coffee' => 'Kopi',
            'lucide-flame' => 'Api/Masak',
            
            // Keamanan & Aturan
            'lucide-shield-check' => 'Aman',
            'lucide-shield-alert' => 'Peringatan Keamanan',
            'lucide-lock' => 'Kunci/Terkunci',
            'lucide-key' => 'Kunci (Key)',
            'lucide-bell' => 'Bell/Lonceng',
            'lucide-ban' => 'Dilarang',
            'lucide-alert-circle' => 'Penting',
            'lucide-info' => 'Info',
            'lucide-help-circle' => 'Tanya',
            'lucide-check-circle' => 'Selesai/Ok',
            'lucide-cigarette-off' => 'Tanpa Rokok',
            
            // Transportasi
            'lucide-parking-circle' => 'Parkir',
            'lucide-bike' => 'Sepeda',
            'lucide-car' => 'Mobil',
            'lucide-bus' => 'Transport',
            
            // Sosial & Lainnya
            'lucide-users' => 'Orang/Penghuni',
            'lucide-library' => 'Perpustakaan',
            'lucide-graduation-cap' => 'Akademik',
            'lucide-book-open' => 'Buku',
            'lucide-calendar' => 'Kalender',
            'lucide-clock' => 'Jam',
            'lucide-flask-conical' => 'Lab',
            'lucide-wrench' => 'Alat',
            'lucide-settings' => 'Pengaturan',
            'lucide-shopping-bag' => 'Belanja',
            'lucide-gift' => 'Hadiah',
            'lucide-map-pin' => 'Lokasi',
            'lucide-globe' => 'Dunia',
            'lucide-camera' => 'Kamera',
            'lucide-video' => 'CCTV',
            'lucide-music' => 'Musik',
            'lucide-mic' => 'Mic',
            'lucide-phone' => 'Telepon',
            'lucide-mail' => 'Email',
            'lucide-message-square' => 'Chat',
            'lucide-trash-2' => 'Sampah/Kebersihan',
            'lucide-credit-card' => 'Kartu',
            'lucide-banknote' => 'Uang',
            'lucide-cloud' => 'Awan',
            'lucide-search' => 'Cari',
            'lucide-layout-grid' => 'Grid',
            'lucide-box' => 'Box',
            'lucide-sparkles' => 'Fasilitas/Bintang',
            'lucide-star' => 'Bintang',
            'lucide-heart' => 'Hati',
            'lucide-sun' => 'Siang',
            'lucide-moon' => 'Malam',
            'lucide-trees' => 'Taman',
            'lucide-shovel' => 'Kebun',
            'lucide-shirt' => 'Pakaian',
            'lucide-volume-2' => 'Suara',
            'lucide-dog' => 'Hewan',
        ];
    }
}
