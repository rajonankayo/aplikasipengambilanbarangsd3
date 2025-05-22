<?php
// Sertakan file koneksi ke database
include "service/database.php";

// Mulai sesi untuk menyimpan data login
session_start();

// === Cek apakah user sudah login ===
if (isset($_SESSION["is_login"]) && $_SESSION["is_login"] == true) {
    // Jika sudah login, arahkan ke halaman dashboard
    header("location: dashboard.php");
    exit();
}

// Ambil nama file saat ini, digunakan untuk menandai halaman aktif pada navigasi
$currentPage = basename($_SERVER['PHP_SELF']);

// === Proses registrasi saat form dikirim ===
if (isset($_POST["register"])) {
    // Ambil data dari form registrasi dan bersihkan dari whitespace ekstra
    $username = trim($_POST["username"]);
    $namalengkap = trim($_POST["namalengkap"]);
    $password = $_POST["password"];

    // Cek jika ada kolom yang kosong
    if (empty($username) || empty($namalengkap) || empty($password)) {
        // Jika ada kolom kosong, tampilkan pesan error
        $register_message = "❌ Semua kolom harus diisi.";
    } else {
        // Hash password menggunakan algoritma SHA-256
        $hash_password = hash("sha256", $password);

        try {
            // Gunakan prepared statement untuk menghindari SQL Injection
            $stmt = $db->prepare("INSERT INTO users (username, namalengkap, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $namalengkap, $hash_password); // Ikat parameter ke query

            // Cek apakah query berhasil dijalankan
            if ($stmt->execute()) {
                // Jika berhasil, tampilkan pesan sukses
                $register_message = "✅ Daftar akun berhasil, silakan login.";
            } else {
                // Jika gagal, tampilkan pesan error
                $register_message = "❌ Daftar akun gagal, silakan coba lagi.";
            }

            // Tutup prepared statement
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            // Jika ada kesalahan (seperti username sudah ada), tampilkan pesan error
            $register_message = "⚠️ Username sudah ada, coba yang lain.";
        }
    }

    // Tutup koneksi database
    $db->close();
}
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
            box-sizing: border-box; /* Menetapkan box-sizing untuk semua elemen menjadi border-box, agar padding dan border dihitung dalam ukuran elemen */
        }

        body {
            margin: 0;
            height: 100vh;
            display: flex;
            flex-direction: column;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; /* Menetapkan font untuk body */
            background: linear-gradient(135deg, #74ebd5, #9face6); /* Latar belakang dengan gradasi warna */
        }

        main {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center; /* Menempatkan konten utama di tengah layar */
        }

        /* ==== Header ==== */
        header {
            background: linear-gradient(90deg, #4e54c8, #8f94fb); /* Latar belakang dengan gradasi warna biru */
            padding: 20px 30px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1); /* Bayangan di bawah header */
        }

        .nav-container {
            max-width: 1200px;
            margin: auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap; /* Mengatur tampilan agar item dapat melipat (wrap) di layar kecil */
        }

        .logo {
            color: white;
            margin: 0;
            font-size: 22px;
            font-weight: 600;
        }

        .nav-links a.active {
            color: yellow; /* Mengubah warna teks menjadi kuning untuk link aktif */
            font-weight: bold; /* Membuat teks menjadi tebal */
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            margin-left: 20px;
            font-size: 16px;
            transition: color 0.3s, transform 0.3s; /* Transisi untuk perubahan warna dan transformasi */
        }

        .nav-links a:hover {
            color: #ffd700; /* Mengubah warna teks saat hover menjadi warna emas */
            transform: translateY(-2px); /* Memberikan efek transformasi saat hover */
        }

        @media (max-width: 600px) {
            .nav-container {
                flex-direction: column;
                align-items: flex-start; /* Menata ulang layout untuk layar kecil */
            }

            .nav-links {
                margin-top: 10px;
            }

            .nav-links a {
                margin-left: 0;
                margin-right: 15px;
            }
        }

        /* ==== Register Box ==== */
        .login-container {
            background: white;
            padding: 40px 30px;
            border-radius: 12px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15); /* Efek bayangan pada kotak login */
            width: 100%;
            max-width: 400px;
            animation: fadeIn 0.6s ease-in-out; /* Menambahkan animasi fade-in pada login form */
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px); /* Menyembunyikan elemen sedikit lebih rendah di awal animasi */
            }

            to {
                opacity: 1;
                transform: translateY(0); /* Mengembalikan elemen ke posisi semula */
            }
        }

        .login-container h1 {
            text-align: center;
            margin-bottom: 25px;
            font-size: 26px;
            color: #333; /* Warna teks untuk judul */
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #444; /* Warna teks label */
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px 14px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 15px;
            transition: border-color 0.3s; /* Transisi untuk perubahan warna border */
        }

        input:focus {
            border-color: #5A67D8; /* Mengubah warna border input saat fokus */
            outline: none; /* Menghilangkan outline saat elemen dalam fokus */
        }

        .btn-container {
            text-align: center;
        }

        button {
            width: 160px;
            padding: 12px;
            background-color: #5A67D8; /* Warna latar belakang tombol */
            color: white;
            font-weight: bold;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s ease; /* Transisi untuk perubahan warna tombol */
        }

        button:hover {
            background-color: #434190; /* Warna latar belakang tombol saat hover */
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 30px 20px; /* Menyesuaikan padding untuk layar kecil */
            }
        }

        /* ==== Footer ==== */
        footer {
            background: linear-gradient(90deg, #4e54c8, #8f94fb); /* Gradasi warna latar belakang footer */
            color: white;
            text-align: center;
            padding: 20px 15px;
            font-size: 14px;
            box-shadow: 0 -4px 10px rgba(0, 0, 0, 0.1); /* Bayangan pada bagian atas footer */
        }

        .footer-container p {
            margin: 0;
            font-style: italic;
            letter-spacing: 0.5px; /* Efek jarak antar huruf */
        }

        .footer-container strong {
            color: #ffd700; /* Warna teks tebal footer */
        }

        /* ==== Toast Notification ==== */
        .toast {
            visibility: hidden;
            min-width: 250px;
            background-color: #333;
            color: #fff;
            text-align: center;
            border-radius: 8px;
            padding: 16px 20px;
            position: fixed;
            z-index: 1000;
            left: 50%;
            top: 30px; /* Menempatkan toast di bagian atas halaman */
            transform: translateX(-50%); /* Memastikan toast berada di tengah layar */
            font-size: 16px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); /* Efek bayangan */
            transition: visibility 0.3s, opacity 0.3s ease-in-out;
            opacity: 0;
        }

        .toast.show {
            visibility: visible;
            opacity: 1; /* Menampilkan toast dengan animasi */
        }
    </style>

</head>

<body>

    <!-- Header -->
    <header>
        <!-- Container untuk menata logo dan navigasi -->
        <div class="nav-container">
            <!-- Logo aplikasi, biasanya berfungsi sebagai identitas aplikasi -->
            <h3 class="logo">Aplikasi Pengambilan Barang</h3>
            <!-- Navigasi utama -->
            <nav class="nav-links">
                <!-- Link ke halaman Home -->
                <a href="index.php">Home</a>
                <!-- Link ke halaman Login -->
                <a href="login.php">Login</a>
                <!-- Link ke halaman Register, dengan kelas aktif jika halaman ini adalah 'register.php' -->
                <a href="register.php" class="<?= ($currentPage == 'register.php') ? 'active' : ''; ?>">Register</a>
            </nav>
        </div>
    </header>

    <!-- Halaman Register -->
    <main>
        <!-- Kontainer untuk form register -->
        <div class="login-container">
            <!-- Judul halaman Register -->
            <h1>Halaman Register</h1>
            <!-- Form untuk pengisian data registrasi -->
            <form action="register.php" method="POST">
                <!-- Input untuk Username -->
                <label for="username">Username</label>
                <input type="text" name="username" id="username" required>

                <!-- Input untuk Nama Lengkap -->
                <label for="namalengkap">Nama Lengkap</label>
                <input type="text" name="namalengkap" id="namalengkap" required>

                <!-- Input untuk Password -->
                <label for="password">Password</label>
                <input type="password" name="password" id="password" required>

                <!-- Kontainer untuk tombol submit -->
                <div class="btn-container">
                    <!-- Tombol untuk mengirimkan form register -->
                    <button type="submit" name="register">Register</button>
                </div>
            </form>
        </div>
    </main>

    <!-- Toast message -->
    <!-- Div untuk menampilkan pesan toast (notifikasi) -->
    <div id="toast" class="toast <?= isset($register_message) ? 'show' : '' ?>">
        <!-- Menampilkan pesan yang diambil dari PHP jika ada -->
        <?= isset($register_message) ? htmlspecialchars($register_message, ENT_QUOTES) : ''; ?>
    </div>

    <script>
        // Fungsi untuk menangani toast yang muncul ketika ada pesan register
        window.onload = function() {
            // Mengambil elemen toast
            var toast = document.getElementById("toast");
            // Jika toast ada dan sedang ditampilkan
            if (toast && toast.classList.contains('show')) {
                // Menghapus kelas 'show' setelah 5 detik, sehingga toast hilang
                setTimeout(function() {
                    toast.classList.remove('show');
                }, 5000); // toast hilang setelah 5 detik
            }
        };
    </script>

    <!-- Footer -->
    <footer>
        <!-- Container untuk footer -->
        <div class="footer-container">
            <!-- Copyright dan nama pembuat aplikasi -->
            <p>&copy; <?= date("Y"); ?> By: <strong>Asriadi Kreatif</strong></p>
        </div>
    </footer>

</body>


</html>