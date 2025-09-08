# 🧾 Konfirmasi PRO — PT. Kayu Mabel Indonesia

## 🚀 Fitur Utama

* Geofencing login (pembatasan lokasi saat login)
* Scan barcode PRO atau input manual
* Menampilkan data PRO yang di input
* Konfirmasi kuantitas PRO yang di input
* Sinkronisasi data melalui Python service

## 🛠️ Teknologi yang Digunakan

| Komponen  | Teknologi                           |
| --------- | ----------------------------------- |
| Backend   | Laravel (PHP 8+)                    |
| Frontend  | Blade Template Engine               |
| Styling   | Tailwind CSS                        |
| Data Sync | Python (`yppi019_mysql_service.py`) |
| Database  | MySQL (`yppi019.sql`)               |

## 🧑‍💻 Instalasi & Setup

1. **Clone Repositori**

   ```bash
   git clone https://github.com/abdulrouff10/konfirmasi_pro.git
   cd konfirmasi_pro
   ```

2. **Install Dependency**

   ```bash
   composer install
   npm install
   ```

3. **Konfigurasi Environment**

   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Setting Database (MySQL)**

   ```bash
   # Menjalankan migration
   php artisan migrate

   # Menjalankan UserSeeder
   php artisan db:seed
   ```

5. **Build Asset (jika memakai Vite/Tailwind)**

   ```bash
   npm run dev
   ```

6. **Jalankan Server Lokal**

   ```bash
   php artisan serve
   ```

7. **Jalankan Service Python (Data Sync)**
   Jalankan pada terminal terpisah:

   ```bash
   python yppi019_mysql_service.py
   ```
