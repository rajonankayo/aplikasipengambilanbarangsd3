<?php
    // Memulai sesi PHP, agar dapat menggunakan data session seperti login status
    session_start();

    // Cek apakah pengguna sudah login dengan memeriksa session 'is_login'
    if(isset($_SESSION['is_login']) == false) {
        // Jika pengguna belum login, arahkan ke halaman login.php
        header("location: login.php");
        // Hentikan eksekusi kode selanjutnya
        exit;
    }

    // Tentukan halaman saat ini
    // Fungsi basename() digunakan untuk mendapatkan nama file dari URL, 
    // contohnya jika URL adalah 'http://domain.com/index.php', maka hasilnya adalah 'index.php'
    $currentPage = basename($_SERVER['PHP_SELF']); // Ambil nama file dari URL untuk digunakan pada menu aktif
?>



<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>APLIKASI PENGAMBILAN BARANG</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
    /* ==== Global ==== */
    * {
        box-sizing: border-box; /* Mengatur model kotak elemen agar padding dan border tidak mempengaruhi ukuran total elemen */
    }

    body {
        margin: 0; /* Menghilangkan margin default dari browser */
        height: 100vh; /* Menetapkan tinggi body sebesar tinggi viewport */
        display: flex; /* Menggunakan flexbox untuk tata letak */
        flex-direction: column; /* Menyusun elemen secara vertikal */
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; /* Mengatur font utama */
        background: linear-gradient(135deg, #74ebd5, #9face6); /* Background dengan gradien warna */
    }

    main {
        flex: 1; /* Memastikan elemen main memenuhi sisa ruang yang tersedia */
        display: flex; /* Menggunakan flexbox untuk menyusun konten secara fleksibel */
        justify-content: center; /* Mengatur konten agar terpusat secara horizontal */
        align-items: center; /* Mengatur konten agar terpusat secara vertikal */
        padding: 30px 20px; /* Memberikan padding di sekitar konten */
    }

    .welcome-box {
        background-color: #fff; /* Warna latar belakang putih */
        padding: 40px 35px; /* Padding di dalam kotak */
        border-radius: 12px; /* Membuat sudut kotak melengkung */
        box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1); /* Memberikan bayangan pada kotak */
        max-width: 600px; /* Lebar maksimum kotak */
        width: 100%; /* Lebar kotak mengisi 100% dari elemen induknya */
        animation: slideFadeIn 0.6s ease-in-out; /* Animasi untuk kotak muncul */
        text-align: center; /* Mengatur teks agar terpusat */
    }

    @keyframes slideFadeIn {
        0% {
            opacity: 0; /* Opasitas 0 (transparan) di awal animasi */
            transform: translateY(20px); /* Pindahkan elemen 20px ke bawah */
        }
        100% {
            opacity: 1; /* Opasitas 1 (tidak transparan) di akhir animasi */
            transform: translateY(0); /* Kembalikan posisi elemen ke semula */
        }
    }

    .welcome-box h1 {
        font-size: 28px; /* Ukuran font judul */
        color: #4e54c8; /* Warna teks judul */
        margin-bottom: 15px; /* Jarak bawah antara judul dan paragraf */
    }

    .welcome-box p {
        font-size: 16px; /* Ukuran font untuk teks paragraf */
        color: #333; /* Warna teks paragraf */
        line-height: 1.6; /* Jarak antar baris untuk meningkatkan keterbacaan */
    }

    /* ==== Header ==== */
    header {
        background: linear-gradient(90deg, #4e54c8, #8f94fb); /* Latar belakang dengan gradien */
        padding: 20px 30px; /* Padding untuk ruang di sekitar header */
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1); /* Bayangan ringan pada header */
    }

    .nav-container {
        max-width: 1200px; /* Lebar maksimum kontainer navigasi */
        margin: auto; /* Mengatur margin agar elemen terpusat */
        display: flex; /* Menggunakan flexbox untuk tata letak navigasi */
        align-items: center; /* Menyusun item secara vertikal di tengah */
        justify-content: space-between; /* Memberikan jarak antara elemen */
        flex-wrap: wrap; /* Membungkus elemen jika diperlukan (responsif) */
    }

    .logo {
        color: white; /* Warna teks logo */
        margin: 0; /* Menghilangkan margin default */
        font-size: 22px; /* Ukuran font logo */
        font-weight: 600; /* Ketebalan font logo */
    }

    .nav-links a.active {
        color: yellow; /* Warna teks untuk link yang aktif */
        font-weight: bold; /* Membuat teks link aktif menjadi tebal */
    }

    .nav-links a {
        color: white; /* Warna teks link */
        text-decoration: none; /* Menghilangkan garis bawah pada link */
        margin-left: 20px; /* Jarak antara link */
        font-size: 16px; /* Ukuran font link */
        transition: color 0.3s, transform 0.3s; /* Transisi untuk efek hover */
    }

    .nav-links a:hover {
        color: #ffd700; /* Warna teks saat hover (kuning) */
        transform: translateY(-2px); /* Efek gerakan naik sedikit saat hover */
    }

    @media (max-width: 600px) {
        .nav-container {
            flex-direction: column; /* Membuat tata letak navigasi menjadi kolom di layar kecil */
            align-items: flex-start; /* Mengatur item agar rata kiri pada layar kecil */
        }

        .nav-links {
            margin-top: 10px; /* Menambahkan margin atas pada link navigasi */
        }

        .nav-links a {
            margin-left: 0; /* Menghilangkan margin kiri */
            margin-right: 15px; /* Menambahkan margin kanan untuk setiap link */
        }
    }

    /* ==== Login Box ==== */
    .login-container {
        background: white; /* Latar belakang kotak login */
        padding: 40px 30px; /* Padding untuk ruang di dalam kotak */
        border-radius: 12px; /* Membuat sudut kotak login melengkung */
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15); /* Memberikan bayangan pada kotak */
        width: 100%; /* Lebar kotak login mengisi seluruh ruang */
        max-width: 400px; /* Lebar maksimum kotak login */
        animation: fadeIn 0.6s ease-in-out; /* Efek animasi untuk kotak login */
    }

    @keyframes fadeIn {
        from {
            opacity: 0; /* Opasitas 0 di awal animasi */
            transform: translateY(20px); /* Menggeser kotak ke bawah di awal animasi */
        }
        to {
            opacity: 1; /* Opasitas 1 di akhir animasi */
            transform: translateY(0); /* Kembali ke posisi semula */
        }
    }

    .login-container h1 {
        text-align: center; /* Menyusun judul di tengah */
        margin-bottom: 25px; /* Memberikan jarak bawah */
        font-size: 26px; /* Ukuran font untuk judul */
        color: #333; /* Warna teks judul */
    }

    label {
        display: block; /* Menampilkan label sebagai blok agar terlihat rapi */
        margin-bottom: 8px; /* Memberikan jarak bawah antara label dan input */
        font-weight: 600; /* Menebalkan font label */
        color: #444; /* Warna teks label */
    }

    input[type="text"],
    input[type="password"] {
        width: 100%; /* Input mengambil 100% lebar kontainer */
        padding: 12px 14px; /* Padding dalam input */
        margin-bottom: 20px; /* Memberikan jarak bawah */
        border: 1px solid #ccc; /* Border input */
        border-radius: 8px; /* Sudut input yang melengkung */
        font-size: 15px; /* Ukuran font dalam input */
        transition: border-color 0.3s; /* Efek transisi pada border */
    }

    input:focus {
        border-color: #5A67D8; /* Mengubah warna border saat input difokuskan */
        outline: none; /* Menghilangkan outline saat input difokuskan */
    }

    .btn-container {
        text-align: center; /* Menyusun tombol di tengah */
    }

    button {
        width: 160px; /* Lebar tombol */
        padding: 12px; /* Padding dalam tombol */
        background-color: #5A67D8; /* Warna latar belakang tombol */
        color: white; /* Warna teks tombol */
        font-weight: bold; /* Menebalkan teks tombol */
        border: none; /* Menghilangkan border tombol */
        border-radius: 8px; /* Sudut tombol yang melengkung */
        cursor: pointer; /* Mengubah kursor menjadi pointer saat hover */
        transition: background-color 0.3s ease; /* Efek transisi pada background warna */
    }

    button:hover {
        background-color: #434190; /* Mengubah warna latar belakang tombol saat hover */
    }

    @media (max-width: 480px) {
        .login-container {
            padding: 30px 20px; /* Mengurangi padding pada layar kecil */
        }
    }

    /* ==== Footer ==== */
    footer {
        background: linear-gradient(90deg, #4e54c8, #8f94fb); /* Latar belakang footer dengan gradien */
        color: white; /* Warna teks footer */
        text-align: center; /* Menyusun teks di tengah */
        padding: 20px 15px; /* Padding dalam footer */
        font-size: 14px; /* Ukuran font footer */
        box-shadow: 0 -4px 10px rgba(0, 0, 0, 0.1); /* Bayangan di bagian atas footer */
    }

    .footer-container p {
        margin: 0; /* Menghilangkan margin pada teks */
        font-style: italic; /* Membuat teks footer miring */
        letter-spacing: 0.5px; /* Menambahkan jarak antar huruf */
    }

    .footer-container strong {
        color: #ffd700; /* Warna emas untuk teks yang lebih menonjol */
    }

    /* ==== Logout Button ==== */
    .logout-button {
        position: fixed; /* Memastikan tombol logout tetap di posisi tetap */
        bottom: 20px; /* Jarak dari bawah */
        right: 20px; /* Jarak dari kanan */
        background-color: #e53e3e; /* Warna latar belakang merah */
        color: white; /* Warna teks tombol */
        font-weight: bold; /* Menebalkan teks tombol */
        padding: 12px 20px; /* Padding dalam tombol */
        border: none; /* Menghilangkan border tombol */
        border-radius: 8px; /* Sudut tombol yang melengkung */
        cursor: pointer; /* Mengubah kursor menjadi pointer saat hover */
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2); /* Bayangan pada tombol */
        transition: background-color 0.3s ease; /* Efek transisi pada background warna */
    }

    .logout-button:hover {
        background-color: #c53030; /* Mengubah warna latar belakang tombol saat hover */
    }
</style>

</head>
<body>

    <!-- Header -->
    <header>
        <!-- Kontainer navigasi -->
        <div class="nav-container">
            <!-- Logo aplikasi -->
            <h3 class="logo">Aplikasi Pengambilan Barang</h3>
            <!-- Menu navigasi -->
            <nav class="nav-links">
                <!-- Tautan ke dashboard_user.php, menambahkan kelas 'active' jika halaman ini adalah yang aktif -->
                <a href="dashboard_user.php" class="<?= ($currentPage == 'dashboard_user.php') ? 'active' : ''; ?>">Dashboard</a>
                <!-- Tautan ke daftarbarang_user.php -->
                <a href="daftarbarang_user.php">Daftar Barang</a>
                <!-- Tautan ke ambilbarang_user.php -->
                <a href="ambilbarang_user.php">Ambil Barang</a>
                <!-- Tautan ke riwayatpengambilan_user.php -->
                <a href="riwayatpengambilan_user.php">Riwayat Pengambilan</a>
            </nav>
        </div>
    </header>

    <!-- Halaman Login atau Beranda -->
    <main>
        <!-- Kotak sambutan untuk pengguna -->
        <div class="welcome-box">
            <?php if (isset($_SESSION["namalengkap"])): ?>
                <!-- Jika pengguna sudah login, tampilkan nama lengkap dan pesan sambutan -->
                <h1>Selamat Datang, <?= htmlspecialchars($_SESSION["namalengkap"]); ?>!</h1>
                <p>Gunakan aplikasi ini untuk melakukan pengambilan barang secara tertib dan transparan. Catatan yang akurat membantu proses evaluasi dan pengadaan barang di masa depan.</p>
            <?php else: ?>
                <!-- Jika pengguna belum login, tampilkan pesan yang mengarahkan untuk login -->
                <h1>Selamat Datang!</h1>
                <p>Silakan login terlebih dahulu untuk menggunakan aplikasi pengambilan barang dengan baik dan bertanggung jawab.</p>
            <?php endif; ?>
        </div>
    </main>

    <!-- Footer -->
    <footer>
        <div class="footer-container">
            <!-- Menampilkan copyright dan nama pembuat aplikasi -->
            <p>&copy; <?= date("Y"); ?> By: <strong>Asriadi Kreatif</strong></p>
        </div>
    </footer>

    <!-- Jika pengguna sudah login, tampilkan tombol Logout -->
    <?php if (isset($_SESSION["namalengkap"])): ?>
        <form action="logout.php" method="POST">
            <!-- Tombol Logout -->
            <button type="submit" class="logout-button">Logout</button>
        </form>
    <?php endif; ?>

</body>

</html>
