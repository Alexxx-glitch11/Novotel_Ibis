# Alur Reservasi Pelanggan

## Tujuan

Setelah memilih outlet, pelanggan dapat mengisi kontak dan jadwal, melihat meja yang sesuai, lalu melanjutkan reservasi. Halaman harus nyaman digunakan di desktop dan ponsel serta mengikuti Tailwind yang sudah dipakai.

## Pilihan Pendekatan

- **Form panjang satu halaman:** semua data, denah, dan konfirmasi terlihat sekaligus; mudah membingungkan di ponsel.
- **Alur bertahap (dipilih):** data dan jadwal, pemilihan area/meja, lalu tinjauan dan konfirmasi; setiap langkah lebih fokus dan ketersediaan dihitung dari jadwal.
- **Form kontak saja lalu reservasi manual:** cepat dibuat tetapi tidak melanjutkan ke pemilihan meja dan alur hold yang diwajibkan PRD.

## Rancangan

1. Langkah data: nama, telepon, email, tanggal, waktu mulai, jumlah tamu, dan catatan opsional.
2. Langkah meja: area, denah atau daftar, serta meja aktif yang kapasitasnya mencukupi dan tidak bentrok dengan reservasi atau hold aktif.
3. Saat pelanggan melanjutkan dari meja, server membuat hold 10 menit secara atomik. Pelanggan dapat memilih jumlah tiap menu outlet selama hold masih aktif.
4. Langkah konfirmasi menampilkan data pelanggan, outlet/area/meja/jadwal, rincian menu, service charge, pajak, dan total dengan tombol untuk mengubah pesanan.
5. Konfirmasi menyimpan reservasi `pending_payment`, snapshot item menu dan harga, menghubungkan meja, menghitung total, dan mengubah hold menjadi `converted` dalam satu transaksi. Pembayaran dan penerbitan QR code tetap di luar cakupan karena alur modul pembayarannya belum tersedia.
6. Durasi reservasi belum ditentukan oleh PRD. Implementasi menggunakan konfigurasi dengan nilai awal 120 menit dan nilai tersebut mudah diganti setelah aturan operasional dikonfirmasi. Waktu mengikuti zona `Asia/Jakarta`.
7. Karena skema hanya menyimpan tanggal kunjungan dan waktu akhir tanpa tanggal akhir, waktu mulai yang membuat reservasi melewati tengah malam ditolak dengan pesan yang jelas.
8. Pembuatan reservasi dicatat di audit log tanpa menyalin data kontak pelanggan ke log.

## Batasan

- Tidak mengubah struktur halaman manager atau mengaktifkan login.
- Tidak menganggap meja aktif otomatis tersedia; status harus mempertimbangkan jadwal, hold aktif, dan reservasi yang belum dilepas.
- Rekaman video tidak dapat dibuka melalui browser karena URL file lokal ditolak; tata letak mengikuti pola proyek saat ini dan kebutuhan PRD.
- Teks antarmuka tersedia dalam Bahasa Indonesia dan Inggris melalui berkas bahasa yang sudah ada.

## Kriteria Hasil

- Validasi kontak, jumlah tamu, tanggal/waktu, outlet, area, dan meja dilakukan di server.
- Konflik jadwal atau hold bersamaan ditolak; hold kedaluwarsa setelah 10 menit.
- Reservasi sukses memiliki kode unik, kontak, waktu, jumlah tamu, status `pending_payment`, dan relasi meja.
- Pesanan menu disimpan dengan snapshot nama/harga dan total biaya memakai rate outlet yang berlaku; jumlah dari browser tidak dipercaya sebagai nilai harga.
- Antarmuka responsif, menyediakan peta/daftar meja, ringkasan pilihan, pesan validasi, serta indikator langkah.
