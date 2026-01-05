# Panduan Penggunaan Printer POS

## Fitur Printer

Aplikasi POS ini mendukung pencetakan struk transaksi menggunakan printer Bluetooth.

## Persyaratan

1. **Printer Thermal Bluetooth** yang mendukung ESC/POS
2. **Browser yang mendukung Web Bluetooth API** (Chrome, Edge, atau browser berbasis Chromium lainnya)
3. Pastikan Bluetooth di perangkat Anda aktif

## Pengaturan Toko

Sebelum menggunakan printer, pastikan Anda sudah mengatur informasi toko:

1. Login ke admin panel
2. Masuk ke menu **Pengaturan → Pengaturan Toko**
3. Isi informasi berikut:
   - **Nama Toko**: Nama toko yang akan muncul di struk
   - **Alamat**: Alamat toko (opsional)
   - **Telepon**: Nomor telepon toko (opsional)
4. Klik **Simpan**

ID perangkat printer akan tersimpan otomatis saat Anda menghubungkan printer.

## Cara Menghubungkan Printer

### Di Halaman POS

1. Buka halaman **Point of Sale**
2. Pastikan printer Anda dalam **mode pairing**
3. Klik tombol **"Hubungkan Printer"** di pojok kanan atas
4. Browser akan meminta izin untuk mengakses Bluetooth, klik **"Allow"** atau **"Izinkan"**
5. Pilih printer Anda dari daftar yang muncul
6. Printer akan terhubung dan status akan berubah menjadi "Printer terhubung"

### Mencetak Struk

Setelah Transaksi Selesai:

1. Proses pembayaran seperti biasa
2. Setelah transaksi berhasil, modal akan muncul
3. Klik tombol **"Cetak Struk"**
4. Struk akan otomatis tercetak ke printer yang terhubung

**Catatan**: Jika printer tidak terhubung saat transaksi selesai, struk tidak akan tercetak secara otomatis. Anda perlu menghubungkan printer terlebih dahulu.

### Di Halaman Laporan Transaksi

1. Buka menu **Laporan → Laporan Transaksi**
2. Cari transaksi yang ingin dicetak
3. Klik tombol **"Cetak Struk"** pada baris transaksi tersebut
4. Struk akan tercetak ke printer yang terhubung

## Memutus Koneksi Printer

1. Buka halaman POS
2. Klik tombol **"Putuskan Printer"** di pojok kanan atas
3. Koneksi printer akan diputus

## Format Struk

Struk yang dicetak akan berisi:

- **Header**:
  - Nama toko
  - Alamat
  - Nomor telepon

- **Informasi Transaksi**:
  - ID Transaksi
  - Tanggal & waktu
  - Nama kasir

- **Daftar Item**:
  - Nama produk
  - Jumlah
  - Harga per item
  - Subtotal

- **Ringkasan Pembayaran**:
  - Total
  - Metode pembayaran (Tunai/Transfer/QRIS)
  - Jumlah dibayar
  - Kembalian (jika ada)

- **Footer**:
  - Barcode transaksi
  - Pesan terima kasih

## Troubleshooting

### Printer tidak terdeteksi

1. Pastikan printer dalam mode pairing
2. Pastikan Bluetooth di perangkat aktif
3. Coba nyalakan ulang printer
4. Bersihkan daftar perangkat Bluetooth yang sudah terhubung dan coba lagi

### Browser tidak mendukung Bluetooth

1. Gunakan **Google Chrome** atau **Microsoft Edge** (versi terbaru)
2. Pada Chrome, pastikan Web Bluetooth API diizinkan:
   - Buka `chrome://flags/#enable-experimental-web-platform-features`
   - Aktifkan fitur tersebut dan restart browser

### Struk tidak tercetak

1. Pastikan printer terhubung (status menunjukkan "Printer terhubung")
2. Pastikan kertas tersedia di printer
3. Coba putuskan dan hubungkan kembali printer
4. Pastikan koneksi Bluetooth stabil

### Izin Bluetooth ditolak

1. Di pengaturan browser, izinkan akses Bluetooth
2. Refresh halaman dan coba lagi
3. Hapus cache browser jika perlu

### Printer terhubung tapi tidak mencetak

1. Pastikan printer mendukung ESC/POS
2. Coba gunakan aplikasi lain untuk menguji printer
3. Pastikan printer tidak sedang mencetak tugas lain
4. Restart printer

## Tips Penggunaan

1. **Hubungkan printer sebelum jam sibuk** agar tidak mengganggu transaksi
2. **Simpan ID perangkat printer** di pengaturan toko untuk koneksi otomatis
3. **Selalu sediakan kertas cadangan** di printer
4. **Periksa status printer** secara berkala untuk memastikan koneksi stabil
4. **Gunakan browser Chrome** atau Edge untuk pengalaman terbaik

## Catatan Penting

- Fitur printer hanya berfungsi pada desktop/laptop dengan dukungan Bluetooth
- Tidak semua printer Bluetooth mendukung format ESC/POS, pastikan printer Anda kompatibel
- Browser harus mendukung Web Bluetooth API
- Untuk penggunaan produksi, pastikan untuk menguji printer Anda terlebih dahulu

## Dukungan Teknis

Jika mengalami masalah dengan printer:

1. Periksa manual printer untuk informasi kompatibilitas
2. Pastikan browser Anda adalah versi terbaru
3. Coba gunakan browser lain yang mendukung Web Bluetooth
4. Hubungi vendor printer jika masalah berlanjut
