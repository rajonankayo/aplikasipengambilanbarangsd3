<?php
// Sertakan file koneksi database
include "service/database.php";

// Mulai sesi
session_start();

// Cek apakah user sudah login, jika tidak arahkan ke halaman login
if (isset($_SESSION['is_login']) == false) {
    header("location:  login.php");
    exit;
}

// Inisialisasi variabel pencarian
$search = "";

// Jika terdapat parameter pencarian (GET['search']) dari URL
if (isset($_GET['search'])) {
    $search = trim($_GET['search']); // Bersihkan input dari spasi
    $search_param = "%{$search}%"; // Tambahkan wildcard untuk LIKE query

    // Persiapkan statement SQL untuk pencarian pada kolom kodebarang, namabarang, dan kategoribarang
    $stmt = $db->prepare("SELECT * FROM daftarbarang WHERE kodebarang LIKE ? OR namabarang LIKE ? OR kategoribarang LIKE ?");
    $stmt->bind_param("sss", $search_param, $search_param, $search_param);
    $stmt->execute(); // Eksekusi query
    $result = $stmt->get_result(); // Ambil hasil
    $stmt->close(); // Tutup statement
} else {
    // Jika tidak ada pencarian, tampilkan semua data
    $sql = "SELECT * FROM daftarbarang";
    $result = $db->query($sql);
}


// --- Pagination Setup ---
// Jumlah item yang akan ditampilkan per halaman
$limit = 7;

// Tentukan halaman saat ini dari parameter 'page', default ke 1 jika tidak ada
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;

// Hitung offset data berdasarkan halaman saat ini
$offset = ($page - 1) * $limit;

// Query dasar untuk menghitung total data (digunakan untuk menghitung jumlah halaman)
$totalQuery = "SELECT COUNT(*) as total FROM daftarbarang";

// Jika ada pencarian, hitung total data yang sesuai pencarian
if (isset($_GET['search']) && $_GET['search'] !== "") {
    $search = trim($_GET['search']);
    $search_param = "%{$search}%";

    // Hitung total hasil pencarian
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM daftarbarang WHERE kodebarang LIKE ? OR namabarang LIKE ? OR kategoribarang LIKE ?");
    $stmt->bind_param("sss", $search_param, $search_param, $search_param);
    $stmt->execute();
    $countResult = $stmt->get_result();
    $totalRow = $countResult->fetch_assoc();
    $total = $totalRow['total'];
    $stmt->close();

    // Ambil data hasil pencarian dengan limit dan offset untuk pagination
    $stmt = $db->prepare("SELECT * FROM daftarbarang WHERE kodebarang LIKE ? OR namabarang LIKE ? OR kategoribarang LIKE ? LIMIT ?, ?");
    // Gunakan 'sssss' untuk string binding (LIMIT dan OFFSET juga dibaca sebagai string)
    $stmt->bind_param("sssss", $search_param, $search_param, $search_param, $offset, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
} else {
    // Jika tidak ada pencarian, hitung total data
    $count = $db->query($totalQuery);
    $totalRow = $count->fetch_assoc();
    $total = $totalRow['total'];

    // Ambil data sesuai halaman (tanpa filter pencarian)
    $sql = "SELECT * FROM daftarbarang LIMIT $offset, $limit";
    $result = $db->query($sql);
}

// Hitung total halaman berdasarkan total data dan item per halaman
$total_pages = ceil($total / $limit);

// Ambil nama file saat ini (untuk penanda menu aktif, jika diperlukan di tampilan)
$currentPage = basename($_SERVER['PHP_SELF']);
?>




<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>APLIKASI PENGAMBILAN BARANG</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* ==== Global ==== */
        /* Mengatur box-sizing agar padding dan border tidak menambah ukuran elemen */
        * {
            box-sizing: border-box;
        }

        /* Gaya dasar untuk <body>: mengatur tinggi penuh, font, dan latar belakang gradien */
        body {
            margin: 0;
            height: 100vh;
            display: flex;
            flex-direction: column;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #74ebd5, #9face6);
        }

        /* Mengatur elemen <main> agar berada di tengah secara vertikal */
        main {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            align-items: center;
            padding-bottom: 40px;
        }

        /* ==== Header ==== */
        /* Gaya untuk elemen header: latar gradien, padding dan bayangan */
        header {
            background: linear-gradient(90deg, #4e54c8, #8f94fb);
            padding: 20px 30px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        /* Container navigasi agar sejajar dan responsif */
        .nav-container {
            max-width: 1200px;
            margin: auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
        }

        /* Logo aplikasi di header */
        .logo {
            color: white;
            margin: 0;
            font-size: 22px;
            font-weight: 600;
        }

        /* Gaya khusus untuk link yang aktif */
        .nav-links a.active {
            color: yellow;
            font-weight: bold;
        }

        /* Gaya dasar untuk semua link navigasi */
        .nav-links a {
            color: white;
            text-decoration: none;
            margin-left: 20px;
            font-size: 16px;
            transition: color 0.3s, transform 0.3s;
        }

        /* Efek hover untuk link navigasi */
        .nav-links a:hover {
            color: #ffd700;
            transform: translateY(-2px);
        }

        /* Responsif navigasi untuk layar kecil */
        @media (max-width: 600px) {
            .nav-container {
                flex-direction: column;
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
        /* Kotak login tengah dengan animasi dan gaya bersih */
        .login-container {
            background: white;
            padding: 40px 30px;
            border-radius: 12px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
            width: 100%;
            max-width: 400px;
            animation: fadeIn 0.6s ease-in-out;
        }

        /* Animasi masuk untuk login box */
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

        /* Judul dalam login box */
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

        /* Gaya untuk input teks dan password */
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

        /* Fokus pada input */
        input:focus {
            border-color: #5A67D8;
            outline: none;
        }

        /* Container untuk tombol login */
        .btn-container {
            text-align: center;
        }

        /* Gaya tombol umum */
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

        /* Efek hover untuk tombol */
        button:hover {
            background-color: #434190;
        }

        /* Responsif login box di layar kecil */
        @media (max-width: 480px) {
            .login-container {
                padding: 30px 20px;
            }
        }

        /* ==== Footer ==== */
        footer {
            background: linear-gradient(90deg, #4e54c8, #8f94fb);
            color: white;
            text-align: center;
            padding: 20px 15px;
            font-size: 14px;
            box-shadow: 0 -4px 10px rgba(0, 0, 0, 0.1);
        }

        /* Gaya teks footer */
        .footer-container p {
            margin: 0;
            font-style: italic;
            letter-spacing: 0.5px;
        }

        /* Penekanan nama pada footer */
        .footer-container strong {
            color: #ffd700;
        }

        /* ==== Tombol Logout ==== */
        .logout-button {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background-color: #e53e3e;
            /* merah */
            color: white;
            font-weight: bold;
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            transition: background-color 0.3s ease;
        }

        /* Efek hover tombol logout */
        .logout-button:hover {
            background-color: #c53030;
        }

        /* ==== Tabel Data ==== */
        table {
            border-collapse: collapse;
            width: 90%;
            margin: 0 auto;
            background-color: white;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        th {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: center;
            background-color: #4e54c8;
            color: white;
        }

        td {
            border: 1px solid #ddd;
            padding: 12px;
        }

        /* Warna baris genap berbeda */
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        /* Efek hover baris */
        tr:hover {
            background-color: rgb(254, 254, 0);
        }

        /* Judul halaman */
        h2 {
            font-size: 28px;
            color: #333;
        }

        /* Kontainer halaman penuh */
        .container {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* Kontainer konten utama */
        .content-container {
            width: 95%;
            max-width: 1000px;
            text-align: center;
        }

        /* ==== Formulir Pencarian ==== */
        .search-form {
            margin-bottom: 20px;
        }

        /* Input teks untuk pencarian */
        .search-form input[type="text"] {
            padding: 10px;
            width: 320px;
            height: 38px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }

        /* Tombol cari */
        .search-form button {
            padding: 10px 16px;
            background-color: #4e54c8;
            color: white;
            border: none;
            height: 38px;
            width: 100px;
            border-radius: 6px;
            cursor: pointer;
        }

        /* ==== Tombol Cetak Data ==== */
        .print-button-wrapper {
            text-align: right;
            margin-bottom: 20px;
        }

        /* Tombol print dengan gaya elegan */
        .print-button {
            background: linear-gradient(90deg, #2b6cb0, #4e54c8);
            color: white;
            padding: 10px 20px;
            font-size: 15px;
            font-weight: 600;
            border: none;
            width: 250px;
            border-radius: 8px;
            cursor: pointer;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
            transition: background 0.3s, transform 0.2s ease-in-out;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        /* Hover dan active effect pada tombol print */
        .print-button:hover {
            background: linear-gradient(90deg, #1a365d, #2c5282);
            transform: translateY(-2px);
        }

        .print-button:active {
            transform: scale(0.97);
        }

        /* Wrapper untuk aksi di atas tabel (misalnya tombol tambah/cetak) */
        .table-action-wrapper {
            width: 90%;
            margin: 0 auto;
            display: flex;
            justify-content: flex-end;
            margin-bottom: 15px;
        }
    </style>
</head>

<body>

    <!-- Header -->
    <header>
        <div class="nav-container">
            <!-- Logo aplikasi -->
            <h3 class="logo">Aplikasi Pengambilan Barang</h3>

            <!-- Navigasi utama admin -->
            <nav class="nav-links">
                <a href="dashboard_admin.php">Dashboard</a>
                <a href="kelolauser_admin.php">Kelola User</a>
                <!-- Menambahkan kelas 'active' jika halaman saat ini adalah daftarbarang_admin.php -->
                <a href="daftarbarang_admin.php" class="<?= ($currentPage == 'daftarbarang_admin.php') ? 'active' : ''; ?>">Daftar Barang</a>
                <a href="tambahbarang_admin.php">Tambah Barang Baru</a>
                <a href="updatebarang_admin.php">Update Barang</a>
                <a href="riwayatpengambilan_admin.php">Riwayat Pengambilan Barang</a>
            </nav>
        </div>
    </header>

    <!-- Main content -->
    <main>
        <div class="content-container">

            <!-- Form pencarian barang -->
            <form method="GET" class="search-form">
                <!-- Input untuk kata kunci pencarian, disanitasi untuk keamanan -->
                <input type="text" name="search" placeholder="Cari barang..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit">Cari</button>
            </form>

            <!-- Judul halaman -->
            <h2>Daftar Barang</h2>

            <!-- Tombol cetak data -->
            <div class="table-action-wrapper">
                <div class="print-button-wrapper">
                    <button onclick="printAllData()" class="print-button">
                        🖨️ Cetak Daftar Barang
                    </button>
                </div>
            </div>

            <!-- Tabel data barang -->
            <table id="dataTable">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Kode Barang</th>
                        <th>Nama Barang</th>
                        <th>Kategori Barang</th>
                        <th>Stok Barang</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Jika hasil query ditemukan -->
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php $no = $offset + 1; ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <!-- Menampilkan data dari database dengan aman -->
                                <td><?= htmlspecialchars($row['kodebarang']) ?></td>
                                <td style="text-align: left;"><?= htmlspecialchars($row['namabarang']) ?></td>
                                <td style="text-align: left;"><?= htmlspecialchars($row['kategoribarang']) ?></td>
                                <td><?= htmlspecialchars($row['stokbarang']) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <!-- Jika tidak ada data -->
                        <tr>
                            <td colspan="5">Tidak ada data barang.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <!-- Navigasi halaman jika total halaman > 1 -->
    <?php if ($total_pages > 1): ?>
        <div style="margin-top: 20px;">
            <!-- Tampilkan pagination -->
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"
                    style="margin: 0 5px; padding: 8px 12px; background-color: <?= $i == $page ? '#4e54c8' : '#ccc' ?>; color: white; text-decoration: none; border-radius: 5px;">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>

    <!-- Footer -->
    <footer>
        <div class="footer-container">
            <!-- Tampilkan tahun saat ini dan nama pembuat -->
            <p>&copy; <?= date("Y"); ?> By: <strong>Asriadi Kreatif</strong></p>
        </div>
    </footer>

    <!-- Tombol logout jika pengguna sudah login -->
    <?php if (isset($_SESSION["namalengkap"])): ?>
        <form action="logout.php" method="POST">
            <button type="submit" class="logout-button">Logout</button>
        </form>
    <?php endif; ?>

    <!-- Script JavaScript untuk cetak data -->
    <script>
        // Fungsi untuk mencetak seluruh data barang
        function printAllData() {
            // Ambil parameter search dari URL (jika ada)
            const params = new URLSearchParams(window.location.search);
            const search = params.get('search') || '';

            // Redirect ke halaman print dengan parameter pencarian
            const printUrl = `print_all_barang.php?search=${encodeURIComponent(search)}`;
            window.open(printUrl, '_blank'); // Buka halaman print di tab baru
        }
    </script>

</body>


</html>