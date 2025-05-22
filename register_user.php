<?php
include "service/database.php"; // Menyertakan file database.php untuk koneksi ke database
session_start(); // Memulai session untuk mengecek apakah user sudah login

// Cek apakah admin sudah login, jika tidak, redirect ke halaman login
if (!isset($_SESSION['is_login'])) {
    header("location: login.php");
    exit;
}

// Proses saat form dikirim
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST['username']); // Mendapatkan data username dari form
    $namalengkap = trim($_POST['namalengkap']); // Mendapatkan data nama lengkap dari form
    $password_raw = trim($_POST['password']); // Mendapatkan data password dari form

    // Enkripsi password dengan SHA-256
    $password = hash('sha256', $password_raw);

    // Validasi sederhana: pastikan semua field terisi
    if ($username && $namalengkap && $password_raw) {
        // Cek apakah username sudah ada dalam database
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->bind_param("s", $username); // Mengikat parameter untuk prepared statement
        $stmt->execute(); // Eksekusi query
        $cek = $stmt->get_result(); // Mendapatkan hasil query

        if ($cek->num_rows > 0) {
            // Jika username sudah ada
            $error = "❌ Username sudah digunakan.";
        } else {
            // Jika username belum ada, lakukan insert data ke database
            $stmt = $db->prepare("INSERT INTO users (username, namalengkap, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $namalengkap, $password); // Mengikat parameter untuk insert
            if ($stmt->execute()) {
                // Jika berhasil, redirect ke halaman kelola user
                header("Location: kelolauser_admin.php?register=success");
                exit;
            } else {
                // Jika gagal, tampilkan pesan error
                $error = "❌ Gagal menyimpan data.";
            }
        }
    } else {
        // Jika ada field yang kosong, tampilkan pesan error
        $error = "❌ Harap lengkapi semua field.";
    }
}

// Tentukan halaman saat ini
$currentPage = basename($_SERVER['PHP_SELF']); // Ambil nama file dari URL (misalnya 'index.php') untuk digunakan pada menu aktif

?>


<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Register User</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* Menetapkan box-sizing ke border-box untuk semua elemen */
        * {
            box-sizing: border-box;
        }

        /* Mengatur tampilan body halaman dengan latar belakang gradien */
        body {
            background: linear-gradient(135deg, #74ebd5, #9face6);
            /* Gradien dari hijau muda ke biru muda */
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            /* Font keluarga */
            margin: 0;
            /* Menghilangkan margin default */
            height: 100vh;
            /* Menetapkan tinggi halaman 100% dari viewport */
            display: flex;
            /* Menggunakan flexbox untuk layout */
            flex-direction: column;
            /* Menyusun elemen-elemen secara vertikal */
        }

        /* Menetapkan struktur container untuk halaman */
        .container {
            flex: 1;
            display: flex;
            flex-direction: column;
            /* Menyusun elemen-elemen secara vertikal */
        }

        /* Menetapkan gaya untuk elemen main */
        main {
            flex: 1;
            display: flex;
            justify-content: center;
            /* Menyusun konten secara horizontal ke tengah */
            align-items: center;
            /* Menyusun konten secara vertikal ke tengah */
            padding: 40px 20px;
        }

        /* ==== Header ==== */
        header {
            background: linear-gradient(90deg, #4e54c8, #8f94fb);
            /* Gradien warna ungu-biru */
            padding: 20px 30px;
            /* Memberikan padding pada header */
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            /* Menambahkan bayangan ringan pada header */
        }

        .nav-container {
            max-width: 1200px;
            /* Membatasi lebar maksimal navigasi */
            margin: auto;
            /* Menyusun konten di tengah */
            display: flex;
            /* Menggunakan flexbox untuk tata letak */
            justify-content: space-between;
            /* Menyusun elemen-elemen secara merata */
            align-items: center;
            /* Menyusun elemen secara vertikal di tengah */
            flex-wrap: wrap;
            /* Membuat elemen-elemen wrap jika layar kecil */
        }

        .logo {
            color: white;
            /* Warna teks putih */
            font-size: 22px;
            /* Ukuran font logo */
            font-weight: 600;
            /* Menetapkan ketebalan font */
            margin: 0;
            /* Menghapus margin */
        }

        /* Menetapkan gaya untuk link navigasi yang aktif */
        .nav-links a.active {
            color: yellow;
            /* Warna teks untuk halaman aktif */
            font-weight: bold;
            /* Menebalkan teks untuk halaman aktif */
        }

        /* Menetapkan gaya untuk link navigasi */
        .nav-links a {
            color: white;
            /* Warna teks putih */
            text-decoration: none;
            /* Menghapus garis bawah */
            margin-left: 20px;
            /* Memberikan jarak antar link */
            font-size: 16px;
            /* Ukuran font link */
            transition: color 0.3s ease, transform 0.3s ease;
            /* Transisi efek ketika hover */
        }

        /* Efek saat hover pada link navigasi */
        .nav-links a:hover {
            color: #ffd700;
            /* Mengubah warna teks saat hover menjadi emas */
            transform: translateY(-2px);
            /* Memberikan efek gerakan naik */
        }

        /* Responsif untuk layar kecil (maksimal 600px) */
        @media (max-width: 600px) {
            .nav-container {
                flex-direction: column;
                /* Menyusun elemen-elemen secara vertikal */
                align-items: flex-start;
                /* Menyusun elemen-elemen ke kiri */
            }

            .nav-links {
                margin-top: 10px;
                /* Memberikan margin atas untuk link */
            }

            .nav-links a {
                margin: 5px 15px 0 0;
                /* Memberikan margin pada link */
            }
        }

        /* ==== Register Form ==== */
        .login-container {
            background: white;
            /* Latar belakang putih untuk form */
            padding: 40px 30px;
            /* Memberikan padding pada form */
            border-radius: 12px;
            /* Membulatkan sudut form */
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
            /* Menambahkan bayangan pada form */
            width: 100%;
            /* Lebar penuh */
            max-width: 400px;
            /* Lebar maksimal form */
            animation: fadeIn 0.5s ease-in-out;
            /* Animasi fade-in */
        }

        /* Animasi fadeIn untuk efek tampilan */
        @keyframes fadeIn {
            from {
                opacity: 0;
                /* Mulai dengan transparan */
                transform: translateY(20px);
                /* Mulai dari bawah */
            }

            to {
                opacity: 1;
                /* Akhirnya menjadi terlihat */
                transform: translateY(0);
                /* Bergerak ke posisi semula */
            }
        }

        h1 {
            text-align: center;
            /* Menyusun judul di tengah */
            margin-bottom: 25px;
            /* Memberikan margin bawah */
            font-size: 26px;
            /* Ukuran font judul */
            color: #333;
            /* Warna teks */
        }

        label {
            display: block;
            /* Menjadikan label sebagai blok */
            margin-bottom: 8px;
            /* Memberikan margin bawah */
            font-weight: 600;
            /* Ketebalan font */
            color: #444;
            /* Warna teks label */
        }

        /* Gaya untuk input field (username, password) */
        input[type="text"],
        input[type="password"] {
            width: 100%;
            /* Lebar input penuh */
            padding: 12px 14px;
            /* Padding dalam input */
            margin-bottom: 20px;
            /* Margin bawah */
            border: 1px solid #ccc;
            /* Border abu-abu */
            border-radius: 8px;
            /* Membulatkan sudut input */
            font-size: 15px;
            /* Ukuran font input */
            transition: border-color 0.3s ease;
            /* Transisi warna border saat fokus */
        }

        /* Efek fokus pada input field */
        input:focus {
            border-color: #5A67D8;
            /* Mengubah warna border saat fokus */
            outline: none;
            /* Menghilangkan outline default */
        }

        /* Gaya untuk tombol submit */
        button {
            width: 100%;
            /* Lebar tombol penuh */
            padding: 12px;
            /* Padding dalam tombol */
            background-color: #5A67D8;
            /* Warna latar belakang tombol */
            color: white;
            /* Warna teks putih */
            font-weight: bold;
            /* Menebalkan teks tombol */
            border: none;
            /* Menghapus border default */
            border-radius: 8px;
            /* Membulatkan sudut tombol */
            cursor: pointer;
            /* Menampilkan pointer saat hover */
            transition: background-color 0.3s ease;
            /* Transisi latar belakang saat hover */
        }

        /* Efek hover pada tombol */
        button:hover {
            background-color: #434190;
            /* Mengubah warna tombol saat hover */
        }

        /* Gaya untuk menampilkan error */
        .error {
            background-color: #ffe0e0;
            /* Latar belakang merah muda untuk error */
            color: #b30000;
            /* Warna teks merah */
            padding: 10px;
            /* Padding dalam error box */
            border-radius: 6px;
            /* Membulatkan sudut error box */
            margin-bottom: 15px;
            /* Margin bawah */
            text-align: center;
            /* Menyusun teks error di tengah */
        }

        /* Gaya untuk tautan kembali */
        .back-link {
            display: block;
            /* Membuat tautan menjadi blok */
            text-align: center;
            /* Menyusun teks di tengah */
            margin-top: 20px;
            /* Memberikan margin atas */
            color: #5A67D8;
            /* Warna teks biru */
            text-decoration: none;
            /* Menghapus garis bawah */
        }

        /* Efek hover pada tautan kembali */
        .back-link:hover {
            text-decoration: underline;
            /* Menambahkan garis bawah saat hover */
        }

        /* ==== Footer ==== */
        footer {
            background: linear-gradient(90deg, #4e54c8, #8f94fb);
            /* Gradien warna footer */
            color: white;
            /* Warna teks putih */
            text-align: center;
            /* Menyusun teks footer di tengah */
            padding: 20px 15px;
            /* Padding dalam footer */
            font-size: 14px;
            /* Ukuran font footer */
            box-shadow: 0 -4px 10px rgba(0, 0, 0, 0.1);
            /* Bayangan ringan pada footer */
        }

        .footer-container p {
            margin: 0;
            /* Menghilangkan margin pada paragraf */
            font-style: italic;
            /* Menjadikan teks italic */
            letter-spacing: 0.5px;
            /* Menambahkan jarak antar huruf */
        }

        .footer-container strong {
            color: #ffd700;
            /* Warna emas untuk teks strong */
        }

        /* ==== Logout Button ==== */
        .logout-button {
            position: fixed;
            /* Menetapkan posisi tombol logout tetap di kanan bawah */
            bottom: 20px;
            /* Posisi dari bawah */
            right: 20px;
            /* Posisi dari kanan */
            width: 160px;
            /* Lebar tombol */
            background-color: #e53e3e;
            /* Warna latar belakang merah */
            color: white;
            /* Warna teks putih */
            font-weight: bold;
            /* Menebalkan teks tombol */
            padding: 12px 20px;
            /* Padding dalam tombol */
            border: none;
            /* Menghapus border default */
            border-radius: 8px;
            /* Membulatkan sudut tombol */
            cursor: pointer;
            /* Menampilkan pointer saat hover */
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            /* Menambahkan bayangan tombol */
            transition: background-color 0.3s ease;
            /* Transisi warna latar belakang saat hover */
        }

        /* Efek hover pada tombol logout */
        .logout-button:hover {
            background-color: #c53030;
            /* Mengubah warna tombol saat hover */
        }
    </style>

</head>

<body>
    <div class="container">
        <!-- Header -->
        <header>
            <div class="nav-container">
                <h3 class="logo">Aplikasi Pengambilan Barang</h3>
                <nav class="nav-links">
                    <!-- Navigasi menu dengan penandaan aktif jika halaman yang dipilih sesuai -->
                    <a href="dashboard_admin.php">Dashboard</a>
                    <a href="kelolauser_admin.php" class="<?= ($currentPage == 'register_user.php') ? 'active' : ''; ?>">Kelola User</a>
                    <a href="daftarbarang_admin.php">Daftar Barang</a>
                    <a href="tambahbarang_admin.php">Tambah Barang</a>
                    <a href="updatebarang_admin.php">Update Barang</a>
                    <a href="riwayatpengambilan_admin.php">Riwayat Pengambilan Barang</a>
                </nav>
            </div>
        </header>

        <main>
            <!-- Form Registrasi User -->
            <div class="login-container">
                <h1>Register User</h1>

                <!-- Menampilkan error jika ada kesalahan -->
                <?php if (!empty($error)): ?>
                    <div class="error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST">
                    <!-- Input field untuk Username -->
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>

                    <!-- Input field untuk Nama Lengkap -->
                    <label for="namalengkap">Nama Lengkap</label>
                    <input type="text" id="namalengkap" name="namalengkap" required>

                    <!-- Input field untuk Password -->
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>

                    <!-- Tombol submit untuk mendaftar -->
                    <button type="submit">Daftarkan User</button>
                </form>

                <!-- Tautan kembali ke halaman daftar user -->
                <a href="kelolauser_admin.php" class="back-link">← Kembali ke Daftar User</a>
            </div>

        </main>

        <!-- Footer -->
        <footer>
            <div class="footer-container">
                <p>&copy; <?= date("Y"); ?> By: <strong>Asriadi Kreatif</strong></p>
            </div>
        </footer>

        <!-- Tombol logout jika session login ada -->
        <?php if (isset($_SESSION["username"])): ?>
            <form action="logout.php" method="POST">
                <button type="submit" class="logout-button">Logout</button>
            </form>
        <?php endif; ?>

    </div>
</body>

</html>