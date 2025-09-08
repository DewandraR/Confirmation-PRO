# 🧾 Konfirmasi PRO — PT. Kayu Mabel Indonesia

## 🚀 Fitur Utama

* Geofencing login (pembatasan lokasi saat login)
* Scan barcode PRO atau input manual
* Menampilkan data PRO yang diinput
* Konfirmasi kuantitas PRO yang diinput
* Sinkronisasi/layanan data melalui Python service

## 🛠️ Teknologi yang Digunakan

| Komponen  | Teknologi                           |
| --------- | ----------------------------------- |
| Backend   | Laravel (PHP 8+)                    |
| Frontend  | Blade Template Engine               |
| Styling   | Tailwind CSS (CDN)                  |
| Data Sync | Python (`yppi019_mysql_service.py`) |
| Database  | MySQL (`yppi019.sql`)               |

## 🧑‍💻 Instalasi & Setup

1. **Clone Repositori**

   ```bash
   git clone https://github.com/abdulrouff10/konfirmasi_pro.git
   cd konfirmasi_pro
   ```

2. **Install Dependency (PHP)**

   ```bash
   composer install
   ```

   > Karena Tailwind menggunakan **CDN** di layout, **tidak perlu `npm install`**.

3. **Konfigurasi Environment**

   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

   Sesuaikan kredensial database di `.env` (contoh DB: `yppi019`).

4. **Setting Database (MySQL)**

   ```bash
   # Menjalankan migration
   php artisan migrate

   # Menjalankan UserSeeder (opsional, jika tersedia)
   php artisan db:seed
   ```

5. **Jalankan Server Lokal**

   ```bash
   php artisan serve
   ```

6. **Jalankan Service Python (Data Sync)**
   Jalankan pada terminal terpisah:

   ```bash
   python yppi019_mysql_service.py
   ```

## 🔐 Akun Login

Gunakan akun berikut untuk login:

```
Email    : user@gmail.com
Password : 12345
```

