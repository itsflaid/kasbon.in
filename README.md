# Kasbon.in

> Buku kasbon warung, didigitalkan — real-time, sederhana, dan transparan buat siapa pun yang jaga warung.

![License](https://img.shields.io/badge/license-MIT-blue.svg)
![PHP](https://img.shields.io/badge/PHP-8.3%2B-777BB4?logo=php&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel&logoColor=white)
![Livewire](https://img.shields.io/badge/Livewire-3-4E56A6?logo=livewire&logoColor=white)
![Status](https://img.shields.io/badge/status-in%20development-yellow)

## Tentang

Warung yang dijaga bergantian oleh beberapa anggota keluarga sering punya masalah klasik: catatan utang pelanggan cuma ada di buku, atau bahkan cuma di kepala si penjaga. Begitu pelanggan mau bayar ke penjaga yang berbeda dari yang mencatat, si penjaga harus tanya-tanya dulu — nggak efisien, nggak transparan.

**Kasbon.in** menggantikan buku catatan itu dengan aplikasi web real-time. Siapa pun yang jaga warung langsung catat, dan semua anggota lain — yang lagi jaga atau nggak — bisa langsung lihat: siapa utang apa, berapa, ke siapa, kapan. Tanpa perlu tanya-tanya lagi.

## ✨ Fitur

- **Multi-warung, multi-anggota** — satu warung dikelola bareng oleh beberapa akun, dengan role owner & staff
- **Pencatatan utang cepat** — nama pelanggan (pilih dari list atau tambah baru), item, harga *opsional* (bisa diisi belakangan oleh anggota lain)
- **Pembayaran fleksibel** — nominal custom atau bayar lunas, lengkap dengan peringatan otomatis kalau nominal melebihi sisa utang
- **Transparansi penuh** — riwayat per pelanggan & riwayat global seluruh warung, siapa mencatat apa dan kapan
- **Real-time** — perubahan langsung muncul di semua device yang sedang membuka warung yang sama, tanpa refresh
- **Kontrol akses bertingkat** — owner, pencatat entri, dan *trusted editor* pilihan owner punya kewenangan edit berbeda
- **Jejak audit** — setiap aksi penting (catat, edit, batalkan) tercatat, entri yang dibatalkan tidak pernah benar-benar dihapus

## 🛠️ Tech Stack

| Layer | Teknologi |
|---|---|
| Backend | Laravel |
| Frontend | Livewire (server-driven, tanpa SPA framework terpisah) |
| Database | MySQL |
| Real-time | Laravel Reverb + Laravel Echo |
| Auth | Laravel Socialite (Google OAuth) |
| Styling | Tailwind CSS |
| Testing | Pest |

## 🚀 Menjalankan Secara Lokal

### Tanpa Docker

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm run dev        # terminal terpisah
php artisan reverb:start   # terminal terpisah
php artisan serve
```

### Dengan Docker (Sail)

```bash
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan reverb:start   # terminal terpisah
./vendor/bin/sail npm run dev
```

Detail lengkap konfigurasi `.env` (Google OAuth, Reverb) ada di dokumentasi setup.


## 📄 Lisensi

[MIT](./LICENSE)


---

<p align="center">
  <img src="https://img.shields.io/badge/Portfolio-flaid.my.id-black?style=flat-square" alt="Portfolio" />
  <a href="https://github.com/itsflaid"><img src="https://img.shields.io/badge/GitHub-itsflaid-black?style=flat-square&logo=github" alt="GitHub" /></a>
</p>

<p align="center">
  Built with ☕ by <a href="https://flaid.my.id"><strong>Flaid</strong></a> — Full-stack Developer & Indie Builder
</p> 
