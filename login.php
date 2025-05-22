<?php
// Sertakan file koneksi ke database
include "service/database.php";

// Mulai sesi untuk menyimpan data login
session_start();

// === Cek apakah user sudah login ===
if (isset($_SESSION['is_login']) && $_SESSION['is_login'] === true) {
    // Jika sudah login, arahkan langsung ke dashboard yang sesuai (admin atau user)
    $redirectPage = ($_SESSION['username'] === 'admin') ? 'dashboard_admin.php' : 'dashboard_user.php';
    header("Location: $redirectPage");
    exit;
}

// Inisialisasi variabel untuk pesan error login
$login_message = "";

// Ambil nama file saat ini, bisa digunakan untuk penanda halaman aktif di navigasi
$currentPage = basename($_SERVER['PHP_SELF']);

// === Proses login saat form dikirim ===
if (isset($_POST['login'])) {
    // Ambil username dan password dari form, lalu bersihkan whitespace
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Hash password menggunakan algoritma SHA-256
    $hashed_password = hash('sha256', $password);

    // Siapkan query SQL untuk mencari user berdasarkan username dan password
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ? AND password = ?");
    $stmt->bind_param("ss", $username, $hashed_password); // Ikat parameter ke query
    $stmt->execute(); // Jalankan query
    $result = $stmt->get_result(); // Ambil hasil query

    // === Cek apakah data user ditemukan ===
    if ($result && $result->num_rows > 0) {
        // Ambil data user dari hasil query
        $data = $result->fetch_assoc();

        // Simpan data penting ke dalam session
        $_SESSION["namalengkap"] = $data["namalengkap"];
        $_SESSION["username"] = $data["username"];
        $_SESSION["is_login"] = true;

        // Arahkan ke dashboard sesuai role user
        $redirectPage = ($data["username"] === 'admin') ? 'dashboard_admin.php' : 'dashboard_user.php';
        header("Location: $redirectPage");
        exit;
    } else {
        // Jika login gagal, tampilkan pesan error
        $login_message = "Username atau password salah!";
    }

    // Tutup statement dan koneksi database
    $stmt->close();
    $db->close();
}
?>


<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Halaman Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
    /* ==== Global ==== */
    * {
        box-sizing: border-box; /* Mengatur agar padding dan border dihitung dalam lebar/tinggi elemen */
    }

    body {
        margin: 0; /* Menghilangkan margin default browser */
        height: 100vh; /* Mengatur tinggi body agar 100% dari viewport */
        display: flex; /* Menggunakan flexbox untuk mengatur layout */
        flex-direction: column; /* Elemen anak akan ditata secara vertikal */
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; /* Font utama */
        background: linear-gradient(135deg, #74ebd5, #9face6); /* Background gradasi */
    }

    main {
        flex: 1; /* Mengisi ruang kosong antara header dan footer */
        display: flex; /* Flex untuk konten utama */
        justify-content: center; /* Pusat secara horizontal */
        align-items: center; /* Pusat secara vertikal */
    }

    /* ==== Header ==== */
    header {
        background: linear-gradient(90deg, #4e54c8, #8f94fb); /* Gradasi horizontal */
        padding: 20px 30px; /* Padding atas-bawah dan kiri-kanan */
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1); /* Bayangan bawah */
    }

    .nav-container {
        max-width: 1200px; /* Batas lebar maksimum */
        margin: auto; /* Pusatkan */
        display: flex; /* Flexbox untuk menu */
        align-items: center; /* Pusat vertikal */
        justify-content: space-between; /* Spasi antar elemen kiri dan kanan */
        flex-wrap: wrap; /* Bungkus saat layar kecil */
    }

    .logo {
        color: white; /* Warna teks putih */
        margin: 0;
        font-size: 22px;
        font-weight: 600; /* Teks tebal */
    }

    .nav-links a.active {
        color: yellow; /* Warna tautan aktif */
        font-weight: bold; /* Teks tebal */
    }

    .nav-links a {
        color: white; /* Warna tautan normal */
        text-decoration: none; /* Hapus garis bawah */
        margin-left: 20px; /* Jarak antar tautan */
        font-size: 16px;
        transition: color 0.3s, transform 0.3s; /* Transisi halus saat hover */
    }

    .nav-links a:hover {
        color: #ffd700; /* Warna hover */
        transform: translateY(-2px); /* Geser sedikit ke atas */
    }

    @media (max-width: 600px) {
        .nav-container {
            flex-direction: column; /* Layout menjadi vertikal */
            align-items: flex-start; /* Rata kiri */
        }

        .nav-links {
            margin-top: 10px; /* Jarak atas menu */
        }

        .nav-links a {
            margin-left: 0;
            margin-right: 15px; /* Jarak kanan antar link */
        }
    }

    /* ==== Login Box ==== */
    .login-container {
        background: white; /* Warna latar kotak */
        padding: 40px 30px;
        border-radius: 12px; /* Sudut membulat */
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15); /* Bayangan */
        width: 100%;
        max-width: 400px; /* Maksimum lebar login box */
        animation: fadeIn 0.6s ease-in-out; /* Animasi saat muncul */
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px); /* Mulai dari bawah dan transparan */
        }

        to {
            opacity: 1;
            transform: translateY(0); /* Muncul ke posisi semula */
        }
    }

    .login-container h1 {
        text-align: center; /* Teks tengah */
        margin-bottom: 25px;
        font-size: 26px;
        color: #333;
    }

    label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #444;
    }

    input[type="text"],
    input[type="password"] {
        width: 100%;
        padding: 12px 14px;
        margin-bottom: 20px;
        border: 1px solid #ccc;
        border-radius: 8px;
        font-size: 15px;
        transition: border-color 0.3s; /* Efek saat fokus */
    }

    input:focus {
        border-color: #5A67D8; /* Warna border saat input aktif */
        outline: none;
    }

    .btn-container {
        text-align: center; /* Tombol di tengah */
    }

    button {
        width: 160px;
        padding: 12px;
        background-color: #5A67D8; /* Warna tombol */
        color: white;
        font-weight: bold;
        border: none;
        border-radius: 8px;
        cursor: pointer; /* Tanda tangan saat hover */
        transition: background-color 0.3s ease;
    }

    button:hover {
        background-color: #434190; /* Warna saat tombol dihover */
    }

    @media (max-width: 480px) {
        .login-container {
            padding: 30px 20px; /* Ubah padding untuk layar kecil */
        }
    }

    /* ==== Footer ==== */
    footer {
        background: linear-gradient(90deg, #4e54c8, #8f94fb); /* Warna gradasi */
        color: white;
        text-align: center;
        padding: 20px 15px;
        font-size: 14px;
        box-shadow: 0 -4px 10px rgba(0, 0, 0, 0.1); /* Bayangan atas */
    }

    .footer-container p {
        margin: 0;
        font-style: italic; /* Teks miring */
        letter-spacing: 0.5px; /* Spasi antar huruf */
    }

    .footer-container strong {
        color: #ffd700; /* Warna emas untuk nama pembuat */
    }
</style>

</head>

<body>

    <!-- ======= Header (Bagian atas halaman) ======= -->
    <header>
        <div class="nav-container"> <!-- Kontainer navigasi -->
            <h3 class="logo">Aplikasi Pengambilan Barang</h3> <!-- Nama/logo aplikasi -->
            <nav class="nav-links"> <!-- Navigasi halaman -->
                <a href="index.php">Home</a> <!-- Tautan ke halaman utama -->
                <!-- Tautan ke halaman login, akan menampilkan class 'active' jika halaman saat ini adalah login.php -->
                <a href="login.php" class="<?= ($currentPage == 'login.php') ? 'active' : ''; ?>">Login</a>
                <a href="register.php">Register</a> <!-- Tautan ke halaman registrasi -->
            </nav>
        </div>
    </header>

    <!-- ======= Main Content: Halaman Login ======= -->
    <main>
        <div class="login-container"> <!-- Kontainer formulir login -->
            <h1>Halaman Login</h1> <!-- Judul halaman -->

            <!-- Menampilkan pesan error jika username atau password salah -->
            <?php if (!empty($login_message)) : ?>
                <p style="color:red; text-align:center;"><?= htmlspecialchars($login_message) ?></p>
            <?php endif; ?>

            <!-- Form login -->
            <form action="login.php" method="POST"> <!-- Kirim data login ke file login.php -->
                <label for="username">Username</label> <!-- Label input username -->
                <input type="text" name="username" id="username" required> <!-- Input username -->

                <label for="password">Password</label> <!-- Label input password -->
                <input type="password" name="password" id="password" required> <!-- Input password -->

                <!-- Tombol submit login -->
                <div class="btn-container">
                    <button type="submit" name="login">Login</button> <!-- Tombol untuk mengirim form -->
                </div>
            </form>
        </div>
    </main>

    <!-- ======= Footer (Bagian bawah halaman) ======= -->
    <footer>
        <div class="footer-container"> <!-- Kontainer footer -->
            <!-- Informasi hak cipta dan pembuat -->
            <p>&copy; <?= date("Y"); ?> By: <strong>Asriadi Kreatif</strong></p>
        </div>
    </footer>

</body>


</html>