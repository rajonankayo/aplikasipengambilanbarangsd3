<?php
session_start(); // Memulai sesi PHP untuk melacak login pengguna

// Cek apakah user sudah login atau belum
if (!isset($_SESSION['is_login'])) {
    header("location: login.php"); // Jika belum login, redirect ke halaman login
    exit; // Hentikan eksekusi kode setelah redirect
}

include 'service/database.php'; // Menyertakan file koneksi ke database

// Ambil total user berdasarkan kolom 'username' dari tabel 'users'
$resultUsers = $db->query("SELECT COUNT(username) AS total_users FROM users");
$totalUsers = $resultUsers->fetch_assoc()['total_users']; // Simpan hasil jumlah user ke variabel

// Ambil total jenis barang berdasarkan jumlah baris unik 'kodebarang' dari tabel 'daftarbarang'
$resultJenisBarang = $db->query("SELECT COUNT(kodebarang) AS total_jenis FROM daftarbarang");
$totalJenisBarang = $resultJenisBarang->fetch_assoc()['total_jenis']; // Simpan hasil jumlah jenis barang

// Ambil total semua stok barang dari kolom 'stokbarang' di tabel 'daftarbarang'
$resultStok = $db->query("SELECT SUM(stokbarang) AS total_stok FROM daftarbarang");
$totalStok = $resultStok->fetch_assoc()['total_stok']; // Simpan total stok barang

// Menentukan halaman yang sedang diakses saat ini
$currentPage = basename($_SERVER['PHP_SELF']); // Mengambil nama file dari URL, misalnya: 'index.php'
?>



<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>APLIKASI PENGAMBILAN BARANG</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* ==== Global ==== */
        /* Mengatur semua elemen agar padding dan border tidak menambah ukuran elemen */
        * {
            box-sizing: border-box;
        }

        /* Gaya dasar untuk <body> */
        body {
            margin: 0;
            /* Hilangkan margin bawaan */
            height: 100vh;
            /* Tinggi penuh layar */
            display: flex;
            /* Gunakan flexbox */
            flex-direction: column;
            /* Arahkan elemen anak secara vertikal */
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            /* Font default */
            background: linear-gradient(135deg, #74ebd5, #9face6);
            /* Gradien warna latar belakang */
        }

        /* Area utama konten */
        main {
            flex: 1;
            /* Isi sisa ruang */
            display: flex;
            justify-content: center;
            /* Pusatkan horizontal */
            align-items: center;
            /* Pusatkan vertikal */
        }

        /* ==== Header ==== */
        /* Gaya header navigasi */
        header {
            background: linear-gradient(90deg, #4e54c8, #8f94fb);
            /* Gradien horizontal */
            padding: 20px 30px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            /* Bayangan bawah */
        }

        /* Container navigasi agar fleksibel dan responsif */
        .nav-container {
            max-width: 1200px;
            margin: auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            /* Elemen bisa turun ke baris berikutnya */
        }

        /* Logo teks */
        .logo {
            color: white;
            margin: 0;
            font-size: 22px;
            font-weight: 600;
        }

        /* Link navigasi aktif */
        .nav-links a.active {
            color: yellow;
            /* Teks kuning untuk halaman aktif */
            font-weight: bold;
        }

        /* Semua link navigasi */
        .nav-links a {
            color: white;
            text-decoration: none;
            margin-left: 20px;
            font-size: 16px;
            transition: color 0.3s, transform 0.3s;
            /* Efek transisi saat hover */
        }

        /* Efek hover pada link */
        .nav-links a:hover {
            color: #ffd700;
            transform: translateY(-2px);
            /* Sedikit naik saat hover */
        }

        /* ==== Responsif Navigasi (untuk layar kecil) ==== */
        @media (max-width: 600px) {
            .nav-container {
                flex-direction: column;
                /* Tumpuk vertikal */
                align-items: flex-start;
            }

            .nav-links {
                margin-top: 10px;
            }

            .nav-links a {
                margin-left: 0;
                margin-right: 15px;
            }
        }

        /* ==== Login Box ==== */
        /* Kontainer form login */
        .login-container {
            background: white;
            padding: 40px 30px;
            border-radius: 12px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
            /* Bayangan lembut */
            width: 100%;
            max-width: 400px;
            animation: fadeIn 0.6s ease-in-out;
            /* Animasi masuk */
        }

        /* Animasi masuk fade */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Judul login */
        .login-container h1 {
            text-align: center;
            margin-bottom: 25px;
            font-size: 26px;
            color: #333;
        }

        /* Label input */
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #444;
        }

        /* Input teks dan password */
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px 14px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 15px;
            transition: border-color 0.3s;
        }

        /* Efek fokus pada input */
        input:focus {
            border-color: #5A67D8;
            outline: none;
        }

        /* Kontainer tombol */
        .btn-container {
            text-align: center;
        }

        /* Gaya tombol login */
        button {
            width: 160px;
            padding: 12px;
            background-color: #5A67D8;
            color: white;
            font-weight: bold;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        /* Hover tombol login */
        button:hover {
            background-color: rgb(76, 12, 239);
        }

        /* Responsif untuk layar sangat kecil */
        @media (max-width: 480px) {
            .login-container {
                padding: 30px 20px;
            }
        }

        /* ==== Footer ==== */
        /* Gaya footer di bawah halaman */
        footer {
            background: linear-gradient(90deg, #4e54c8, #8f94fb);
            color: white;
            text-align: center;
            padding: 20px 15px;
            font-size: 14px;
            box-shadow: 0 -4px 10px rgba(0, 0, 0, 0.1);
            /* Bayangan atas */
        }

        /* Isi footer */
        .footer-container p {
            margin: 0;
            font-style: italic;
            letter-spacing: 0.5px;
        }

        /* Highlight teks di footer */
        .footer-container strong {
            color: #ffd700;
        }

        /* Kotak selamat datang / info */
        .welcome-box {
            background-color: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
            max-width: 650px;
            width: 100%;
            text-align: center;
        }

        /* ==== Pengaturan (Logout & Settings) ==== */
        /* Wrapper untuk tombol logout dan pengaturan */
        .logout-settings-wrapper {
            position: fixed;
            bottom: 20px;
            right: 20px;
            display: flex;
            gap: 12px;
        }

        /* Tombol pengaturan */
        .settings-button {
            background-color: #3182ce;
            color: white;
            font-weight: bold;
            padding: 12px 20px;
            border: none;
            width: 160px;
            height: 40px;
            border-radius: 8px;
            cursor: pointer;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            transition: background-color 0.3s ease;
        }

        /* Hover tombol pengaturan */
        .settings-button:hover {
            background-color: rgb(4, 48, 247);
        }

        /* Tombol logout */
        .logout-button {
            background-color: #3182ce;
            color: white;
            font-weight: bold;
            padding: 12px 20px;
            border: none;
            width: 160px;
            height: 40px;
            border-radius: 8px;
            cursor: pointer;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            transition: background-color 0.3s ease;
        }

        /* Hover tombol logout */
        .logout-button:hover {
            background-color: rgb(215, 16, 16);
            /* Merah saat hover */
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>


</head>

<body>

    <!-- ==== Header Navigasi ==== -->
    <header>
        <div class="nav-container">
            <!-- Logo Aplikasi -->
            <h3 class="logo">Aplikasi Pengambilan Barang</h3>

            <!-- Navigasi Menu -->
            <nav class="nav-links">
                <!-- Setiap link akan mendapatkan kelas 'active' jika nama file saat ini cocok -->
                <a href="dashboard_admin.php" class="<?= ($currentPage == 'dashboard_admin.php') ? 'active' : ''; ?>">Dashboard</a>
                <a href="kelolauser_admin.php">Kelola User</a>
                <a href="daftarbarang_admin.php">Daftar Barang</a>
                <a href="tambahbarang_admin.php">Tambah Barang Baru</a>
                <a href="updatebarang_admin.php">Update Barang</a>
                <a href="riwayatpengambilan_admin.php">Riwayat Pengambilan Barang</a>
            </nav>
        </div>
    </header>

    <!-- ==== Halaman Utama (Main Content) ==== -->
    <main>
        <div class="welcome-box">
            <!-- Tampilkan nama lengkap dari sesi login -->
            <h1>Selamat Datang, <?= htmlspecialchars($_SESSION["namalengkap"]); ?></h1>

            <!-- Deskripsi dashboard -->
            <p style="margin-bottom: 30px;">Berikut adalah dashboard laporan total user, total jenis barang dan total stok barang yang tersedia:</p>

            <!-- Kanvas ChartJS untuk menampilkan grafik bar -->
            <canvas id="summaryChart" width="300" height="150"></canvas>
        </div>
    </main>

    <!-- ==== Footer ==== -->
    <footer>
        <div class="footer-container">
            <!-- Tahun dinamis dan kredit -->
            <p>&copy; <?= date("Y"); ?> By: <strong>Asriadi Kreatif</strong></p>
        </div>
    </footer>

    <!-- ==== Tombol Logout & Pengaturan ==== -->
    <?php if (isset($_SESSION["namalengkap"])): ?>
        <div class="logout-settings-wrapper">
            <!-- Tombol menuju halaman pengaturan -->
            <form action="settings.php" method="GET">
                <button type="submit" class="settings-button">⚙️ Pengaturan</button>
            </form>

            <!-- Tombol untuk logout -->
            <form action="logout.php" method="POST">
                <button type="submit" class="logout-button">Logout</button>
            </form>
        </div>
    <?php endif; ?>

    <!-- ==== Script Chart.js untuk Menampilkan Grafik Bar ==== -->
    <script>
        // Ambil konteks dari elemen canvas
        const ctx = document.getElementById('summaryChart').getContext('2d');

        // Buat chart tipe bar menggunakan Chart.js
        const summaryChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Total User', 'Jenis Barang', 'Total Stok'], // Label di sumbu X
                datasets: [{
                    label: 'Statistik Aplikasi', // Label untuk tooltip
                    data: [<?= $totalUsers ?>, <?= $totalJenisBarang ?>, <?= $totalStok ?>], // Data dari PHP
                    backgroundColor: [
                        '#4e54c8', // Warna batang 1
                        '#74ebd5', // Warna batang 2
                        '#9face6' // Warna batang 3
                    ],
                    borderRadius: 10, // Membulatkan ujung batang
                    borderWidth: 1 // Ketebalan border batang
                }]
            },
            options: {
                plugins: {
                    legend: {
                        display: false // Sembunyikan legenda
                    },
                    tooltip: {
                        callbacks: {
                            // Kustomisasi tooltip saat hover
                            label: function(context) {
                                return `${context.dataset.label}: ${context.raw}`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true, // Mulai dari nol
                        ticks: {
                            precision: 0 // Tidak pakai desimal
                        }
                    }
                }
            }
        });
    </script>

</body>


</html>