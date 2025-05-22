<?php
// Sertakan file koneksi database
include "service/database.php";

// Mulai session
session_start();

// Cek apakah user sudah login, jika tidak arahkan ke halaman login
if (!isset($_SESSION['is_login'])) {
    header("Location: login.php");
    exit;
}

// Ambil nilai filter tanggal dari parameter URL (GET)
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';

// Konversi format tanggal dari HTML (Y-m-d) ke format yang sesuai
if (!empty($from)) {
    $fromObj = DateTime::createFromFormat('Y-m-d', $from);
    $from = $fromObj ? $fromObj->format('Y-m-d') : $from;
}

if (!empty($to)) {
    $toObj = DateTime::createFromFormat('Y-m-d', $to);
    $to = $toObj ? $toObj->format('Y-m-d') : $to;
}

// Ambil keyword pencarian dari URL
$search = trim($_GET['search'] ?? '');

// Ambil halaman saat ini dari URL, jika tidak ada maka default ke 1
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;

// Tentukan jumlah data per halaman
$limit = 5;

// Hitung offset data untuk query SQL berdasarkan halaman
$offset = ($page - 1) * $limit;

// Array untuk menyimpan kondisi WHERE dan parameter untuk query
$whereClauses = [];
$params = [];
$types = ''; // Tipe parameter untuk bind_param (s = string, i = integer)

// Fungsi untuk validasi tanggal
function isValidDate($date)
{
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

// ===== Filter pencarian berdasarkan kata kunci =====
if ($search !== '') {
    $likeSearch = "%{$search}%";
    $whereClauses[] = "(kodebarang LIKE ? OR namabarang LIKE ? OR tanggal LIKE ? OR pengambil LIKE ?)";
    $params = array_merge($params, [$likeSearch, $likeSearch, $likeSearch, $likeSearch]);
    $types .= 'ssss'; // 4 parameter string
}

// ===== Filter berdasarkan tanggal =====
if (!empty($from) && !empty($to)) {
    $whereClauses[] = "(tanggal BETWEEN ? AND ?)";
    $params[] = $from;
    $params[] = $to;
    $types .= 'ss';
} elseif (!empty($from)) {
    $whereClauses[] = "(tanggal >= ?)";
    $params[] = $from;
    $types .= 's';
} elseif (!empty($to)) {
    $whereClauses[] = "(tanggal <= ?)";
    $params[] = $to;
    $types .= 's';
}

// Gabungkan semua kondisi WHERE jika ada
$whereSQL = '';
if (!empty($whereClauses)) {
    $whereSQL = 'WHERE ' . implode(' AND ', $whereClauses);
}

// ===== Query untuk menghitung total data (digunakan untuk pagination) =====
$countSql = "SELECT COUNT(*) as total FROM riwayatpengambilan $whereSQL";
$stmt = $db->prepare($countSql);

if (!empty($params)) {
    // Bind parameter jika ada filter
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$countResult = $stmt->get_result();
$totalRow = $countResult->fetch_assoc();
$total = $totalRow['total'] ?? 0;
$stmt->close();

// ===== Query untuk mengambil data berdasarkan limit dan offset (pagination) =====
$dataSql = "SELECT * FROM riwayatpengambilan $whereSQL LIMIT ?, ?";
$paramsWithLimit = $params; // Salin parameter sebelumnya
$typesWithLimit = $types . 'ii'; // Tambahkan 2 parameter integer untuk limit dan offset
$paramsWithLimit[] = $offset;
$paramsWithLimit[] = $limit;

$stmt = $db->prepare($dataSql);
$stmt->bind_param($typesWithLimit, ...$paramsWithLimit);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

// Hitung total halaman
$total_pages = ceil($total / $limit);

// Ambil nama file saat ini untuk menandai menu aktif (misalnya: ambilbarang_user.php)
$currentPage = basename($_SERVER['PHP_SELF']);
?>





<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>APLIKASI PENGAMBILAN BARANG</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* ==== GLOBAL ==== */

        /* Semua elemen akan menggunakan box-sizing border-box agar padding dan border tidak menambah ukuran elemen */
        * {
            box-sizing: border-box;
        }

        /* Gaya dasar untuk body */
        body {
            margin: 0;
            /* Hilangkan margin bawaan */
            height: 100vh;
            /* Gunakan seluruh tinggi viewport */
            display: flex;
            /* Gunakan flexbox untuk pengaturan layout */
            flex-direction: column;
            /* Elemen disusun secara vertikal */
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #74ebd5, #9face6);
            /* Latar gradasi */
        }

        /* Area utama konten */
        main {
            flex: 1;
            /* Mengisi sisa ruang dari body */
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            align-items: center;
            padding-bottom: 40px;
        }

        /* ==== HEADER ==== */

        /* Gaya header dengan gradasi dan bayangan */
        header {
            background: linear-gradient(90deg, #4e54c8, #8f94fb);
            padding: 20px 30px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        /* Kontainer navigasi */
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

        /* Gaya link navigasi aktif */
        .nav-links a.active {
            color: yellow;
            font-weight: bold;
        }

        /* Gaya umum link navigasi */
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

        /* Responsive header untuk layar kecil */
        @media (max-width: 600px) {
            .nav-container {
                flex-direction: column;
                align-items: flex-start;
            }

            .nav-links {
                margin-top: 10px;
            }

            .nav-links a {
                margin: 5px 15px 0 0;
            }
        }

        /* ==== FOOTER ==== */

        /* Gaya footer dengan warna dan bayangan */
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

        /* ==== TOMBOL LOGOUT ==== */

        .logout-button {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background-color: #e53e3e;
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

        /* ==== TABEL ==== */

        /* Gaya dasar tabel */
        table {
            border-collapse: collapse;
            width: 90%;
            margin: 0 auto;
            background-color: white;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        /* Gaya sel tabel */
        th,
        td {
            border: 1px solid #ddd;
            padding: 12px;
        }

        /* Header tabel */
        th {
            text-align: center;
            background-color: #4e54c8;
            color: white;
        }

        /* Warna latar baris genap */
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        /* Efek hover baris */
        tr:hover {
            background-color: rgb(254, 254, 0);
        }

        /* ==== JUDUL & KONTEN ==== */
        h2 {
            font-size: 28px;
            color: #333;
        }

        .container {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .content-container {
            width: 95%;
            max-width: 1000px;
            text-align: center;
        }

        /* ==== FORM PENCARIAN ==== */

        /* Struktur dasar form pencarian */
        .search-form {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
            margin-bottom: 0px;
            margin-top: 0;
            padding-top: 0px;
        }

        /* Baris pertama: input teks dan tombol */
        .top-row {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }

        /* Input teks pencarian */
        .search-form input[type="text"] {
            padding: 10px;
            font-size: 14px;
            border-radius: 6px;
            border: 1px solid #74ebd5;
            width: 320px;
            height: 38px;
        }

        /* Label form */
        .search-form label {
            font-size: 13px;
            color: #333;
        }

        /* Tombol submit pencarian */
        .search-form button {
            padding: 10px 14px;
            font-size: 14px;
            background-color: #4e54c8;
            color: white;
            border: none;
            border-radius: 6px;
            height: 38px;
            width: 100px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Hover tombol cari */
        .search-form button:hover {
            background-color: #3b3fc1;
        }

        /* Input tanggal */
        .search-form input[type="date"] {
            padding: 10px;
            font-size: 14px;
            border-radius: 6px;
            border: 1px solid #ccc;
            width: 130px;
        }

        /* ==== BARIS TANGGAL ==== */
        .date-row {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }

        /* Grup input tanggal */
        .date-group {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        /* Label tanggal */
        .date-group label {
            font-size: 13px;
            color: #333;
            margin-bottom: 4px;
        }

        /* ==== PAGINATION ==== */

        /* Gaya pagination normal dan sticky */
        .pagination,
        .pagination-fixed {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 10px;
            margin: 30px 0 40px;
        }

        /* Pagination sticky di bawah */
        .pagination-fixed {
            position: sticky;
            bottom: 0;
            background-color: #fff;
            padding: 12px 0;
            z-index: 10;
        }

        /* Link pagination */
        .pagination a,
        .pagination-fixed a {
            padding: 8px 14px;
            background-color: #ccc;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            transition: background-color 0.3s ease;
        }

        /* Efek hover dan aktif pada pagination */
        .pagination a.active,
        .pagination a:hover,
        .pagination-fixed a.active,
        .pagination-fixed a:hover {
            background-color: #4e54c8;
        }
    </style>
</head>

<body>

    <!-- ===== HEADER NAVIGATION ===== -->
    <header>
        <div class="nav-container">
            <!-- Judul aplikasi -->
            <h3 class="logo">Aplikasi Pengambilan Barang</h3>

            <!-- Navigasi menu pengguna -->
            <nav class="nav-links">
                <a href="dashboard_user.php">Dashboard</a>
                <a href="daftarbarang_user.php">Daftar Barang</a>
                <a href="ambilbarang_user.php">Ambil Barang</a>
                <a href="riwayatpengambilan_user.php" class="<?= ($currentPage == 'riwayatpengambilan_user.php') ? 'active' : ''; ?>">Riwayat Pengambilan</a>
            </nav>
        </div>
    </header>

    <!-- ===== HALAMAN UTAMA - TAMPILKAN RIWAYAT PENGAMBILAN ===== -->
    <main>
        <div class="content-container">

            <!-- Form pencarian berdasarkan kata kunci dan filter tanggal -->
            <form method="GET" class="search-form">
                <div class="top-row">
                    <!-- Input untuk pencarian nama/kode barang -->
                    <input type="text" name="search" placeholder="Cari barang..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit">Cari</button>
                </div>

                <!-- Filter berdasarkan rentang tanggal -->
                <div class="date-row">
                    <div class="date-group">
                        <label for="from">Dari</label>
                        <input type="date" id="from" name="from" value="<?= isset($_GET['from']) ? date('Y-m-d', strtotime($_GET['from'])) : '' ?>">
                    </div>
                    <div class="date-group">
                        <label for="to">Sampai</label>
                        <input type="date" id="to" name="to" value="<?= isset($_GET['to']) ? date('Y-m-d', strtotime($_GET['to'])) : '' ?>">
                    </div>
                </div>
            </form>

            <!-- Judul tabel -->
            <h2>Riwayat Pengambilan Barang</h2>

            <!-- Tabel riwayat pengambilan -->
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Kode Barang</th>
                        <th>Nama Barang</th>
                        <th>Jumlah Barang</th>
                        <th>Tanggal</th>
                        <th>Pengambil</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Cek apakah data tersedia -->
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php $no = $offset + 1; ?>
                        <!-- Loop data hasil query -->
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= htmlspecialchars($row['kodebarang']) ?></td>
                                <td style="text-align: left"><?= htmlspecialchars($row['namabarang']) ?></td>
                                <td><?= htmlspecialchars($row['jumlah']) ?></td>
                                <td><?= date('d-m-Y', strtotime($row['tanggal'])) ?></td>
                                <td style="text-align: left"><?= htmlspecialchars($row['pengambil']) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <!-- Jika tidak ada data -->
                        <tr>
                            <td colspan="6">Tidak ada data barang.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <!-- ===== PAGINATION (Jika halaman lebih dari 1) ===== -->
    <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>"
                    class="<?= $i == $page ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>

    <!-- ===== FOOTER ===== -->
    <footer>
        <div class="footer-container">
            <p>&copy; <?= date("Y"); ?> By: <strong>Asriadi Kreatif</strong></p>
        </div>
    </footer>

    <!-- ===== TOMBOL LOGOUT (hanya jika user login) ===== -->
    <?php if (isset($_SESSION["namalengkap"])): ?>
        <form action="logout.php" method="POST">
            <button type="submit" class="logout-button">Logout</button>
        </form>
    <?php endif; ?>

</body>


</html>