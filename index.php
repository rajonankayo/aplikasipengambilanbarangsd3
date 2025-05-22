<?php
include 'service/database.php'; // Memuat file koneksi ke database

// Ambil data pengaturan dari database
$setting = $db->query("SELECT * FROM pengaturan LIMIT 1"); // Jalankan query untuk mengambil 1 baris data dari tabel pengaturan
$dataSetting = $setting->fetch_assoc(); // Ambil hasil query dalam bentuk array asosiatif

// Atur nilai default jika tidak ada data
$unitName = $dataSetting['unit_name'] ?? 'Aplikasi Pengambilan Barang'; // Gunakan nilai dari database atau default jika kosong
$logoPath = (!empty($dataSetting['logo_path']) && file_exists($dataSetting['logo_path'])) // Cek apakah path logo tersedia dan file-nya ada
    ? $dataSetting['logo_path'] // Jika ada, gunakan logo dari database
    : 'assets/img/logo.png'; // Jika tidak, gunakan logo default

// Tentukan halaman saat ini
$currentPage = basename($_SERVER['PHP_SELF']); // Ambil nama file dari URL (misalnya 'index.php') untuk digunakan pada menu aktif

?>




<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Halaman Register</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* ==== Global ==== */
        * {
            box-sizing: border-box;
            /* Mengatur box model agar padding & border termasuk dalam lebar elemen */
        }

        body {
            margin: 0;
            /* Menghapus margin default browser */
            height: 100vh;
            /* Membuat tinggi body 100% dari tinggi layar */
            display: flex;
            /* Menggunakan Flexbox untuk layout vertikal */
            flex-direction: column;
            /* Susun elemen anak secara kolom */
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            /* Jenis font */
            background: linear-gradient(135deg, #74ebd5, #9face6);
            /* Latar belakang gradasi */
        }

        main {
            flex: 1;
            /* Memperluas elemen main agar mengisi ruang tersedia */
            display: flex;
            /* Flexbox untuk isi konten utama */
            justify-content: center;
            /* Pusatkan horizontal */
            align-items: center;
            /* Pusatkan vertikal */
        }

        /* ==== Header ==== */
        header {
            background: linear-gradient(90deg, #4e54c8, #8f94fb);
            /* Warna latar belakang gradasi horizontal */
            padding: 20px 30px;
            /* Spasi di dalam elemen */
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            /* Bayangan lembut di bawah header */
        }

        .nav-container {
            max-width: 1200px;
            /* Batas lebar maksimum kontainer */
            margin: auto;
            /* Pusatkan secara horizontal */
            display: flex;
            /* Flexbox untuk susunan menu dan logo */
            align-items: center;
            /* Rata tengah vertikal */
            justify-content: space-between;
            /* Jarak antara elemen kanan dan kiri */
            flex-wrap: wrap;
            /* Membungkus elemen jika sempit */
        }

        .logo {
            color: white;
            /* Warna teks logo */
            margin: 0;
            /* Hapus margin */
            font-size: 22px;
            /* Ukuran font logo */
            font-weight: 600;
            /* Ketebalan teks */
        }

        .nav-links a.active {
            color: yellow;
            /* Warna teks jika halaman aktif */
            font-weight: bold;
            /* Teks tebal untuk halaman aktif */
        }

        .nav-links a {
            color: white;
            /* Warna default link */
            text-decoration: none;
            /* Hapus garis bawah */
            margin-left: 20px;
            /* Jarak antar link */
            font-size: 16px;
            /* Ukuran teks link */
            transition: color 0.3s, transform 0.3s;
            /* Animasi perubahan warna dan transform */
        }

        .nav-links a:hover {
            color: #ffd700;
            /* Warna saat hover */
            transform: translateY(-2px);
            /* Naik sedikit saat hover */
        }

        /* Responsif header untuk layar kecil */
        @media (max-width: 600px) {
            .nav-container {
                flex-direction: column;
                /* Ubah ke kolom */
                align-items: flex-start;
                /* Rata kiri */
            }

            .nav-links {
                margin-top: 10px;
                /* Jarak atas menu */
            }

            .nav-links a {
                margin-left: 0;
                /* Reset margin kiri */
                margin-right: 15px;
                /* Tambahkan jarak kanan */
            }
        }

        /* ==== Login Box ==== */
        .login-container {
            background: white;
            /* Warna latar belakang */
            padding: 40px 30px;
            /* Spasi dalam box */
            border-radius: 12px;
            /* Membulatkan sudut */
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
            /* Bayangan besar */
            width: 100%;
            /* Lebar penuh */
            max-width: 400px;
            /* Maksimal lebar */
            animation: fadeIn 0.6s ease-in-out;
            /* Animasi masuk */
        }

        /* Animasi muncul dari bawah */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
                /* Awal: transparan dan turun */
            }

            to {
                opacity: 1;
                transform: translateY(0);
                /* Akhir: terlihat dan posisi normal */
            }
        }

        .login-container h1 {
            text-align: center;
            /* Tengah */
            margin-bottom: 25px;
            /* Jarak bawah */
            font-size: 26px;
            /* Ukuran teks */
            color: #333;
            /* Warna teks */
        }

        label {
            display: block;
            /* Label tampil sebagai blok */
            margin-bottom: 8px;
            /* Jarak ke bawah */
            font-weight: 600;
            /* Teks tebal */
            color: #444;
            /* Warna teks */
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            /* Isi lebar kontainer */
            padding: 12px 14px;
            /* Spasi dalam input */
            margin-bottom: 20px;
            /* Jarak bawah */
            border: 1px solid #ccc;
            /* Garis pinggir */
            border-radius: 8px;
            /* Membulatkan sudut */
            font-size: 15px;
            /* Ukuran teks */
            transition: border-color 0.3s;
            /* Transisi saat fokus */
        }

        input:focus {
            border-color: #5A67D8;
            /* Warna border saat aktif */
            outline: none;
            /* Hapus garis fokus browser */
        }

        .btn-container {
            text-align: center;
            /* Tombol di tengah */
        }

        button {
            width: 160px;
            /* Lebar tombol */
            padding: 12px;
            /* Spasi dalam */
            background-color: #5A67D8;
            /* Warna latar tombol */
            color: white;
            /* Warna teks */
            font-weight: bold;
            /* Teks tebal */
            border: none;
            /* Tanpa garis pinggir */
            border-radius: 8px;
            /* Sudut membulat */
            cursor: pointer;
            /* Cursor jadi pointer */
            transition: background-color 0.3s ease;
            /* Efek hover */
        }

        button:hover {
            background-color: #434190;
            /* Warna saat hover */
        }

        /* Login box responsif di layar kecil */
        @media (max-width: 480px) {
            .login-container {
                padding: 30px 20px;
                /* Kurangi padding */
            }
        }

        /* ==== Footer ==== */
        footer {
            background: linear-gradient(90deg, #4e54c8, #8f94fb);
            /* Latar belakang gradasi */
            color: white;
            /* Warna teks */
            text-align: center;
            /* Teks di tengah */
            padding: 20px 15px;
            /* Spasi dalam */
            font-size: 14px;
            /* Ukuran teks kecil */
            box-shadow: 0 -4px 10px rgba(0, 0, 0, 0.1);
            /* Bayangan atas */
        }

        .footer-container p {
            margin: 0;
            /* Hapus margin */
            font-style: italic;
            /* Teks miring */
            letter-spacing: 0.5px;
            /* Spasi antar huruf */
        }

        .footer-container strong {
            color: #ffd700;
            /* Warna emas untuk nama */
        }

        /* ==== Konten Tambahan ==== */
        main ul {
            list-style-type: disc;
            /* Bullet list */
        }

        main h2 {
            margin-bottom: 5px;
            /* Jarak bawah */
        }

        /* ==== Login Container Multi-Kolom ==== */
        .login-container {
            display: flex;
            /* Flexbox dua kolom */
            flex-direction: row;
            /* Arah horizontal */
            justify-content: space-between;
            /* Spasi antar kolom */
            gap: 50px;
            /* Jarak antar kolom */
            background: white;
            padding: 10px 60px;
            border-radius: 12px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
            width: 95%;
            max-width: 1200px;
            animation: fadeIn 0.6s ease-in-out;
        }

        .login-column {
            flex: 1;
            /* Kolom dengan lebar fleksibel */
        }

        .login-column:last-child {
            flex: 1.2;
            /* Kolom kanan sedikit lebih lebar */
        }

        .login-container h1 {
            margin-bottom: 5px;
            color: #4e54c8;
            font-size: 28px;
        }

        /* Responsif kolom login */
        @media (max-width: 900px) {
            .login-container {
                flex-direction: column;
                /* Susun ke bawah */
                padding: 30px 25px;
            }

            .login-column {
                flex: unset;
                /* Reset properti flex */
            }
        }


        /* ==== Logo ==== */
        .logo-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
            /* Tengah horizontal */
            margin-top: 30px;
        }

        .logo-wrapper img {
            width: 140px;
            height: 140px;
            object-fit: contain;
            /* Jaga rasio gambar */
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            background-color: white;
            padding: 10px;
            transition: transform 0.3s ease;
        }

        .logo-wrapper img:hover {
            transform: scale(1.05);
            /* Zoom saat hover */
        }

        .logo-wrapper h1 {
            margin-top: 15px;
            font-size: 24px;
            color: #d33;
            text-align: center;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            /* Bayangan teks halus */
        }

        footer {
            position: relative;
            background-color: #333;
            color: white;
            text-align: center;
            padding: 20px;
            font-size: 18px;
        }

        .footer-wrapper {
            position: relative;
            display: inline-block;
        }

        .programmer-name {
            cursor: pointer;
            font-weight: bold;
            
        }

        /* Info Box Hidden by Default */
        .info-box {
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background-color: #fff;
            color: #000;
            border: 1px solid #ccc;
            padding: 0px;
            width: 250px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            display: none;
            z-index: 999;
            text-align: center;
            border-radius: 8px;
        }

        /* Menampilkan kotak saat hover */
        .footer-wrapper:hover .info-box {
            display: block;
        }

        /* Gaya gambar */
        .info-box img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 50%;
            float: center;
            margin-top: 20px;
            
        }

        /* Membersihkan float */
        .info-text::after {
            content: "";
            display: block;
            clear: both;
        }
    </style>
</head>

<body>

    <!-- ======= Header (Bagian Atas Halaman) ======= -->
    <header>
        <div class="nav-container"> <!-- Kontainer navigasi -->
            <h3 class="logo">Aplikasi Pengambilan Barang</h3> <!-- Judul/logo aplikasi -->
            <nav class="nav-links"> <!-- Navigasi utama -->
                <!-- Tautan ke halaman utama, dengan penanda 'active' jika halaman saat ini adalah index.php -->
                <a href="index.php" class="<?= ($currentPage == 'index.php') ? 'active' : ''; ?>">Home</a>
                <a href="login.php">Login</a> <!-- Link ke halaman login -->
                <a href="register.php">Register</a> <!-- Link ke halaman registrasi -->
            </nav>
        </div>
    </header>

    <!-- ======= Main Content (Bagian Utama Halaman) ======= -->
    <main>
        <div class="login-container"> <!-- Kontainer utama login dan petunjuk -->

            <!-- ======= Kolom Kiri: Informasi Selamat Datang dan Logo ======= -->
            <div class="login-column">
                <h1>Selamat Datang di<br>Aplikasi Pengambilan Barang</h1> <!-- Judul sambutan -->
                <p style="color: #444; font-size: 16px; line-height: 1.6;">
                    Aplikasi ini membantu Anda dalam mengelola pengambilan barang dengan lebih mudah dan terstruktur. Silakan ikuti petunjuk di samping untuk menggunakan semua fitur yang tersedia.
                </p>

                <!-- Logo dan nama unit -->
                <div class="logo-wrapper">
                    <img src="<?= $logoPath ?>" alt="Logo Aplikasi"> <!-- Gambar logo dari pengaturan atau default -->
                    <h1><?= htmlspecialchars($unitName) ?></h1> <!-- Nama unit yang diambil dari pengaturan -->
                </div>
            </div>

            <!-- ======= Kolom Kanan: Petunjuk Penggunaan ======= -->
            <div class="login-column">
                <section>
                    <h2 style="font-size: 20px; color: #4e54c8;">📘 Petunjuk Untuk User</h2>
                    <ul style="padding-left: 20px; color: #444; line-height: 1.3;">
                        <!-- Daftar petunjuk penggunaan untuk user biasa -->
                        <li><strong>Login:</strong> Masuk dengan akun yang terdaftar.</li>
                        <li><strong>Register:</strong> Mendaftar jika belum punya akun.</li>
                        <li><strong>Dashboard:</strong> Lihat ringkasan informasi.</li>
                        <li><strong>Daftar Barang:</strong> Cek dan cari barang yang tersedia.</li>
                        <li><strong>Ambil Barang:</strong> Untuk melakukan pengambilan barang yang tersedia.</li>
                        <li><strong>Riwayat Pengambilan:</strong> Lihat log pengambilan barang.</li>
                        <li><strong>Logout:</strong> Klik tombol Logout untuk keluar.</li>
                    </ul>

                    <h2 style="font-size: 20px; color: #4e54c8;">📘 Petunjuk Untuk Admin</h2>
                    <ul style="padding-left: 20px; color: #444; line-height: 1.3;">
                        <!-- Daftar petunjuk penggunaan untuk admin -->
                        <li><strong>Login:</strong> Masuk dengan akun admin yang terdaftar.</li>
                        <li><strong>Register:</strong> Untuk mendaftarkan akun user.</li>
                        <li><strong>Dashboard:</strong> Lihat ringkasan informasi.</li>
                        <li><strong>Kelola User:</strong> Untuk mengelola akun user.</li>
                        <li><strong>Daftar Barang:</strong> Cek dan cari barang yang tersedia.</li>
                        <li><strong>Tambah Barang:</strong> Isi formulir lalu klik Tambah.</li>
                        <li><strong>Update Barang:</strong> Perbarui data barang.</li>
                        <li><strong>Riwayat Pengambilan:</strong> Lihat log pengambilan barang.</li>
                        <li><strong>Cetak:</strong> Mencetak daftar barang dan riwayat pengambilan.</li>
                        <li><strong>Pengaturan:</strong> Untuk mengatur nama unit dan logo.</li>
                        <li><strong>Logout:</strong> Klik tombol Logout untuk keluar.</li>
                    </ul>
                </section>
            </div>
        </div>
    </main>

    <!-- ======= Footer (Bagian Bawah Halaman) ======= -->
    <footer>
        <div class="footer-wrapper">
            <!-- Menampilkan tahun saat ini dan nama pembuat -->
            <p>&copy; <?= date("Y"); ?> Programer : <span class="programmer-name">Asriadi Kreatif</span></p>
            <div class="info-box">
                <img src="assets/img/Asriadi.jpg" alt="Foto Asriadi" />
                <div class="info-text">
                    <p><b>Motto:</b><br>"Dimana ada kemauan, disitu ciptakan jalan".</p>
                </div>
            </div>
        </div>
    </footer>

</body>


</html>