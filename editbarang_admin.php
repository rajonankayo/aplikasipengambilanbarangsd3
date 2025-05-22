<?php
// Mengimpor file koneksi database
include "service/database.php";

// Memulai sesi untuk mengakses data login
session_start();

// Mengecek apakah user sudah login, jika tidak maka akan diarahkan ke halaman login
if (!isset($_SESSION['is_login'])) {
    header("Location: login.php");
    exit;
}

// Mengambil kode barang dari parameter URL (GET), jika tidak ada maka beri nilai kosong
$kodebarang = $_GET['kodebarang'] ?? '';

// Jika kode barang tidak disediakan, tampilkan pesan kesalahan
if (!$kodebarang) {
    echo "Kode barang tidak ditemukan.";
    exit;
}

// --- Ambil data barang dari database berdasarkan kodebarang yang dikirim ---
$stmt = $db->prepare("SELECT * FROM daftarbarang WHERE kodebarang = ?");
$stmt->bind_param("s", $kodebarang);
$stmt->execute();
$result = $stmt->get_result();
$barang = $result->fetch_assoc(); // Mengambil hasil query sebagai array asosiatif
$stmt->close();

// Jika data barang tidak ditemukan di database, tampilkan pesan kesalahan
if (!$barang) {
    echo "Barang tidak ditemukan.";
    exit;
}

// --- Proses update data barang saat form disubmit ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ambil data dari form POST
    $namabarang = $_POST['namabarang'];
    $kategoribarang = $_POST['kategoribarang'];
    $stokbarang = $_POST['stokbarang'];

    // Update data barang di database dengan data yang baru
    $stmt = $db->prepare("UPDATE daftarbarang SET namabarang = ?, kategoribarang = ?, stokbarang = ? WHERE kodebarang = ?");
    $stmt->bind_param("ssis", $namabarang, $kategoribarang, $stokbarang, $kodebarang);

    // Jika update berhasil, redirect ke halaman update dengan pesan sukses
    if ($stmt->execute()) {
        header("Location: updatebarang_admin.php?update=success");
        exit;
    } else {
        // Jika gagal, tampilkan pesan kesalahan
        echo "Gagal memperbarui data.";
    }

    $stmt->close();
}

// Menentukan halaman yang sedang aktif, digunakan untuk menandai menu navigasi
$currentPage = basename($_SERVER['PHP_SELF']); // Mengambil nama file PHP yang sedang dijalankan (contoh: updatebarang_form.php)

?>



<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Edit Barang</title>
    <style>
        /* ==== Global ==== */

        /* Semua elemen menggunakan box-sizing border-box agar padding dan border tidak menambah ukuran elemen */
        * {
            box-sizing: border-box;
        }

        /* Styling untuk elemen <body> */
        body {
            margin: 0;
            /* Menghilangkan margin bawaan */
            height: 100vh;
            /* Tinggi penuh layar */
            display: flex;
            flex-direction: column;
            /* Susun vertikal */
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            /* Font default */
            background: linear-gradient(135deg, #74ebd5, #9face6);
            /* Gradien background */
        }

        /* ==== Layout ==== */

        /* Container utama halaman agar memiliki layout fleksibel vertikal */
        .container {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* Elemen <main> berfungsi sebagai konten utama dan diratakan di tengah */
        main {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        /* Kontainer isi utama halaman */
        .content-container {
            width: 95%;
            max-width: 1000px;
            text-align: center;
        }

        /* ==== Header ==== */

        /* Styling untuk header navigasi */
        header {
            background: linear-gradient(90deg, #4e54c8, #8f94fb);
            /* Gradien horizontal */
            padding: 20px 30px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            /* Bayangan bawah */
        }

        /* Container dalam header yang mengatur posisi logo dan navigasi */
        .nav-container {
            max-width: 1200px;
            margin: auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
        }

        /* Logo aplikasi */
        .logo {
            color: white;
            font-size: 22px;
            font-weight: 600;
            margin: 0;
        }

        /* Link navigasi yang aktif */
        .nav-links a.active {
            color: yellow;
            font-weight: bold;
        }

        /* Styling umum untuk link navigasi */
        .nav-links a {
            color: white;
            text-decoration: none;
            margin-left: 20px;
            font-size: 16px;
            transition: color 0.3s, transform 0.3s;
        }

        /* Efek hover pada navigasi */
        .nav-links a:hover {
            color: #ffd700;
            transform: translateY(-2px);
        }

        /* Responsive: untuk layar kecil */
        @media (max-width: 600px) {
            .nav-container {
                flex-direction: column;
                align-items: flex-start;
            }

            .nav-links {
                margin-top: 10px;
            }

            .nav-links a {
                margin: 0 15px 0 0;
            }
        }

        /* ==== Form Container ==== */
        /* Kontainer untuk form (login, edit, dll.) */
        .form-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
            max-width: 500px;
            margin: auto;
            opacity: 0;
            transform: translateY(30px);
            animation: slideUp 0.8s ease-out forwards;
        }

        /* Animasi untuk form agar muncul naik */
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Form akan terlihat penuh jika class 'show' ditambahkan */
        .form-container.show {
            opacity: 1;
            transform: translateY(0);
        }

        /* Judul form menggunakan animasi */
        .form-container h2 {
            animation: fadeInHeader 1s ease-out forwards;
        }

        /* Animasi untuk header form */
        @keyframes fadeInHeader {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        /* Label form */
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #444;
        }

        /* ==== Input Fields ==== */
        /* Styling untuk semua input */
        input[type="text"],
        input[type="number"],
        input[type="password"] {
            width: 100%;
            padding: 12px 14px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 15px;
            transition: border-color 0.3s;
        }

        /* Efek saat input dalam fokus */
        input:focus {
            border-color: #5A67D8;
            outline: none;
        }

        /* ==== Button ==== */
        /* Styling umum tombol */
        button {
            width: 100%;
            padding: 12px;
            background-color: #5A67D8;
            color: white;
            font-weight: bold;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        /* Efek hover pada tombol */
        button:hover {
            background-color: #434190;
        }

        /* Container tombol */
        .btn-container {
            text-align: center;
        }

        /* Responsive: padding form pada layar kecil */
        @media (max-width: 480px) {
            .login-container {
                padding: 30px 20px;
            }
        }

        /* ==== Footer ==== */
        /* Footer halaman */
        footer {
            background: linear-gradient(90deg, #4e54c8, #8f94fb);
            color: white;
            text-align: center;
            padding: 20px 15px;
            font-size: 14px;
            box-shadow: 0 -4px 10px rgba(0, 0, 0, 0.1);
        }

        .footer-container p {
            margin: 0;
            font-style: italic;
            letter-spacing: 0.5px;
        }

        .footer-container strong {
            color: #ffd700;
        }

        /* ==== Logout Button ==== */
        /* Tombol logout yang muncul di kanan bawah */
        .logout-button {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background-color: #e53e3e;
            color: white;
            font-weight: bold;
            width: 160px;
            height: 40px;
            padding: 0;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            transition: background-color 0.3s ease;
            text-align: center;
            font-size: 14px;
        }

        /* Efek hover pada tombol logout */
        .logout-button:hover {
            background-color: #c53030;
        }

        /* ==== Table ==== */
        /* Styling untuk tabel data */
        table {
            border-collapse: collapse;
            width: 90%;
            margin: 0 auto;
            background-color: white;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        /* Styling untuk header dan sel tabel */
        th,
        td {
            border: 1px solid #ddd;
            padding: 12px;
        }

        th {
            text-align: center;
            background-color: #4e54c8;
            color: white;
        }

        td {
            text-align: left;
        }

        /* Warna latar baris genap */
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        /* Efek hover pada baris tabel */
        tr:hover {
            background-color: #e9f0ff;
        }

        /* ==== Search Form ==== */
        /* Form pencarian di atas tabel */
        .search-form {
            margin-bottom: 20px;
        }

        .search-form input[type="text"] {
            padding: 10px;
            width: 250px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }

        .search-form button {
            padding: 10px 16px;
            background-color: #4e54c8;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        /* ==== Link Styling ==== */
        /* Styling untuk tag <a> */
        a {
            display: inline-block;
            margin-top: 15px;
            text-align: center;
            text-decoration: none;
            color: #4e54c8;
        }

        a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>

    <!-- ==== Header ==== -->
    <header>
        <div class="nav-container">
            <!-- Logo Aplikasi -->
            <h3 class="logo">Aplikasi Pengambilan Barang</h3>

            <!-- Navigasi Menu -->
            <nav class="nav-links">
                <!-- Link ke halaman dashboard admin -->
                <a href="dashboard_admin.php">Dashboard</a>

                <!-- Link ke halaman kelola user -->
                <a href="kelolauser_admin.php">Kelola User</a>

                <!-- Link ke daftar barang -->
                <a href="daftarbarang_admin.php">Daftar Barang</a>

                <!-- Link ke halaman tambah barang -->
                <a href="tambahbarang_admin.php">Tambah Barang Baru</a>

                <!-- Link ke halaman update barang, diberi kelas 'active' jika halaman aktif -->
                <a href="updatebarang_admin.php" class="<?= ($currentPage == 'editbarang_admin.php') ? 'active' : ''; ?>">Update Barang</a>

                <!-- Link ke halaman riwayat pengambilan barang -->
                <a href="riwayatpengambilan_admin.php">Riwayat Pengambilan Barang</a>
            </nav>
        </div>
    </header>

    <!-- ==== Form Edit Barang ==== -->
    <div class="form-container">
        <!-- Judul halaman -->
        <h2>Edit Barang</h2>

        <!-- Form untuk mengedit data barang, dikirim menggunakan metode POST -->
        <form method="POST">
            <!-- Input kode barang (readonly karena tidak bisa diubah) -->
            <label>Kode Barang</label>
            <input type="text" name="kodebarang" value="<?= htmlspecialchars($barang['kodebarang']) ?>" readonly>

            <!-- Input nama barang -->
            <label>Nama Barang</label>
            <input type="text" name="namabarang" value="<?= htmlspecialchars($barang['namabarang']) ?>" required>

            <!-- Input kategori barang -->
            <label>Kategori Barang</label>
            <input type="text" name="kategoribarang" value="<?= htmlspecialchars($barang['kategoribarang']) ?>" required>

            <!-- Input stok barang -->
            <label>Stok Barang</label>
            <input type="number" name="stokbarang" value="<?= htmlspecialchars($barang['stokbarang']) ?>" min="0" required>

            <!-- Tombol untuk menyimpan perubahan -->
            <button type="submit">Simpan Perubahan</button>
        </form>

        <!-- Link kembali ke halaman daftar update barang -->
        <a href="updatebarang_admin.php">← Kembali ke Daftar Barang</a>
    </div>

    <!-- ==== Footer ==== -->
    <footer>
        <div class="footer-container">
            <!-- Informasi hak cipta -->
            <p>&copy; <?= date("Y"); ?> By: <strong>Asriadi Kreatif</strong></p>
        </div>
    </footer>

    <!-- ==== Logout Button ==== -->
    <?php if (isset($_SESSION["username"])): ?>
        <!-- Form logout yang muncul jika user sudah login -->
        <form action="logout.php" method="POST">
            <button type="submit" class="logout-button">Logout</button>
        </form>
    <?php endif; ?>

</body>


</html>