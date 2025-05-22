<?php
// Memasukkan file koneksi ke database
include "service/database.php";
// Memulai sesi untuk pemeriksaan status login pengguna
session_start();

// Mengecek apakah pengguna sudah login, jika tidak maka arahkan ke halaman login
if (!isset($_SESSION['is_login'])) {
    header("Location: login.php");
    exit; // Menghentikan eksekusi script jika pengguna belum login
}

// Mengambil nilai dari parameter GET, dengan pengecekan jika parameter tidak ada, maka menggunakan nilai default
$from = $_GET['from'] ?? ''; // Tanggal mulai (dari)
$to = $_GET['to'] ?? ''; // Tanggal hingga (sampai)
$search = trim($_GET['search'] ?? ''); // Pencarian, menghilangkan spasi kosong
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1; // Halaman yang sedang ditampilkan, default ke halaman 1 jika tidak ada parameter

// Menetapkan jumlah data per halaman (batas)
$limit = 5;
// Menentukan offset (posisi data yang akan diambil pada query SQL)
$offset = ($page - 1) * $limit;

// Validasi format tanggal, hanya menerima format Y-m-d yang valid
if (!empty($from) && !DateTime::createFromFormat('Y-m-d', $from)) {
    $from = ''; // Jika format tanggal salah, kosongkan nilai $from
}
if (!empty($to) && !DateTime::createFromFormat('Y-m-d', $to)) {
    $to = ''; // Jika format tanggal salah, kosongkan nilai $to
}

// Array untuk menampung kondisi WHERE pada query SQL
$whereClauses = [];
$params = []; // Menampung parameter untuk query SQL
$types = ''; // Menyimpan tipe data untuk bind_param (prepared statement)

// Memeriksa apakah ada pencarian, jika ada, tambahkan kondisi pencarian ke dalam query
if ($search !== '') {
    $likeSearch = "%{$search}%"; // Membuat format pencarian dengan wildcard (%)
    $whereClauses[] = "(kodebarang LIKE ? OR namabarang LIKE ? OR tanggal LIKE ? OR pengambil LIKE ?)";
    $params = array_merge($params, [$likeSearch, $likeSearch, $likeSearch, $likeSearch]);
    $types .= 'ssss'; // Menambahkan tipe data untuk 4 parameter string
}

// Memeriksa apakah ada filter tanggal (dari dan sampai), jika ada, tambahkan kondisi tanggal ke query
if (!empty($from) && !empty($to)) {
    $whereClauses[] = "(tanggal BETWEEN ? AND ?)";
    $params[] = $from; // Tanggal mulai
    $params[] = $to; // Tanggal hingga
    $types .= 'ss'; // Menambahkan tipe data untuk dua parameter string (tanggal)
} elseif (!empty($from)) {
    $whereClauses[] = "(tanggal >= ?)";
    $params[] = $from; // Tanggal mulai
    $types .= 's'; // Menambahkan tipe data untuk satu parameter string (tanggal)
} elseif (!empty($to)) {
    $whereClauses[] = "(tanggal <= ?)";
    $params[] = $to; // Tanggal hingga
    $types .= 's'; // Menambahkan tipe data untuk satu parameter string (tanggal)
}

// Menyusun bagian WHERE dari query SQL, jika ada kondisi yang ditambahkan
$whereSQL = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

// Query untuk menghitung total data yang sesuai dengan filter
$countSql = "SELECT COUNT(*) as total FROM riwayatpengambilan $whereSQL";
$stmt = $db->prepare($countSql);
// Mengikat parameter untuk query jika ada
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute(); // Menjalankan query
$countResult = $stmt->get_result(); // Mendapatkan hasil dari query
$totalRow = $countResult->fetch_assoc(); // Mengambil jumlah total data
$total = $totalRow['total'] ?? 0; // Menyimpan total data, jika tidak ada hasil, set ke 0
$stmt->close(); // Menutup statement

// Query untuk mengambil data berdasarkan filter dan pagination
$dataSql = "SELECT * FROM riwayatpengambilan $whereSQL LIMIT ?, ?";
$paramsWithLimit = $params; // Menyalin parameter pencarian
$typesWithLimit = $types . 'ii'; // Menambahkan tipe data untuk parameter offset dan limit
$paramsWithLimit[] = $offset; // Menambahkan offset
$paramsWithLimit[] = $limit; // Menambahkan limit

$stmt = $db->prepare($dataSql);
$stmt->bind_param($typesWithLimit, ...$paramsWithLimit); // Mengikat parameter untuk query
$stmt->execute(); // Menjalankan query
$result = $stmt->get_result(); // Mendapatkan hasil dari query
$stmt->close(); // Menutup statement

// Menghitung jumlah total halaman berdasarkan total data dan limit per halaman
$total_pages = ceil($total / $limit);

// Menentukan halaman saat ini (untuk penggunaan pada menu atau navigasi aktif)
$currentPage = basename($_SERVER['PHP_SELF']); // Mengambil nama file PHP yang sedang dieksekusi (misalnya 'index.php') untuk digunakan sebagai referensi halaman aktif

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
            box-sizing: border-box;
            /* Menetapkan box-sizing untuk semua elemen menjadi border-box agar padding dan border termasuk dalam ukuran elemen */
        }

        body {
            margin: 0;
            /* Menghilangkan margin default pada body */
            height: 100vh;
            /* Membuat body memiliki tinggi penuh layar */
            display: flex;
            /* Menggunakan flexbox untuk layout */
            flex-direction: column;
            /* Mengatur arah fleksibel elemen menjadi kolom */
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            /* Menentukan font global */
            background: linear-gradient(135deg, #74ebd5, #9face6);
            /* Memberikan background gradien */
        }

        main {
            flex: 1;
            /* Memastikan elemen main mengambil ruang yang tersisa */
            display: flex;
            /* Menggunakan flexbox untuk layout */
            flex-direction: column;
            /* Mengatur arah fleksibel elemen menjadi kolom */
            justify-content: top;
            /* Menyusun elemen ke atas */
            align-items: center;
            /* Menyusun elemen secara horizontal di tengah */
            padding-bottom: 40px;
            /* Memberikan padding bawah */
        }

        /* ==== Header ==== */
        header {
            background: linear-gradient(90deg, #4e54c8, #8f94fb);
            /* Gradien untuk latar belakang header */
            padding: 20px 30px;
            /* Padding dalam header */
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            /* Memberikan bayangan pada header */
            margin-bottom: 0px;
            /* Menghilangkan margin bawah */
        }

        .nav-container {
            max-width: 1200px;
            /* Membatasi lebar maksimal kontainer navigasi */
            margin: auto;
            /* Membuat kontainer menjadi terpusat */
            display: flex;
            /* Menggunakan flexbox untuk menu navigasi */
            align-items: center;
            /* Menyusun elemen vertikal di tengah */
            justify-content: space-between;
            /* Menyusun elemen dengan jarak antar elemen */
            flex-wrap: wrap;
            /* Membungkus elemen jika ruang terbatas */
        }

        .logo {
            color: white;
            /* Warna teks logo */
            margin: 0;
            /* Menghilangkan margin */
            font-size: 22px;
            /* Ukuran font logo */
            font-weight: 600;
            /* Menambahkan ketebalan pada font logo */
        }

        .nav-links a.active {
            color: yellow;
            /* Warna teks untuk link yang aktif */
            font-weight: bold;
            /* Menebalkan teks link yang aktif */
        }

        .nav-links a {
            color: white;
            /* Warna teks untuk link */
            text-decoration: none;
            /* Menghilangkan garis bawah pada link */
            margin-left: 20px;
            /* Memberikan jarak antar link */
            font-size: 16px;
            /* Ukuran font untuk link */
            transition: color 0.3s, transform 0.3s;
            /* Efek transisi saat hover */
        }

        .nav-links a:hover {
            color: #ffd700;
            /* Mengubah warna link saat hover */
            transform: translateY(-2px);
            /* Efek pergerakan link sedikit ke atas saat hover */
        }

        /* Responsif untuk layar kecil (max-width: 600px) */
        @media (max-width: 600px) {
            .nav-container {
                flex-direction: column;
                /* Mengubah layout menjadi kolom pada layar kecil */
                align-items: flex-start;
                /* Menyusun elemen ke kiri */
            }

            .nav-links {
                margin-top: 10px;
                /* Memberikan jarak pada link setelah berubah layout */
            }

            .nav-links a {
                margin-left: 0;
                /* Menghilangkan margin kiri */
                margin-right: 15px;
                /* Memberikan margin kanan */
            }
        }

        /* ==== Login Box ==== */
        .login-container {
            background: white;
            /* Warna latar belakang putih pada kotak login */
            padding: 40px 30px;
            /* Padding dalam kotak login */
            border-radius: 12px;
            /* Memberikan sudut melengkung pada kotak */
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
            /* Bayangan kotak */
            width: 100%;
            /* Memastikan lebar kotak 100% */
            max-width: 400px;
            /* Membatasi lebar maksimal kotak */
            animation: fadeIn 0.6s ease-in-out;
            /* Animasi untuk muncul secara perlahan */
        }

        /* Animasi fadeIn untuk kotak login */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
                /* Efek pergeseran vertikal */
            }

            to {
                opacity: 1;
                transform: translateY(0);
                /* Posisi akhir dari animasi */
            }
        }

        .login-container h1 {
            text-align: center;
            /* Menyusun judul di tengah */
            margin-bottom: 25px;
            /* Memberikan jarak bawah pada judul */
            font-size: 26px;
            /* Ukuran font judul */
            color: #333;
            /* Warna teks judul */
        }

        label {
            display: block;
            /* Menampilkan label dalam baris baru */
            margin-bottom: 8px;
            /* Memberikan jarak bawah pada label */
            font-weight: 600;
            /* Menebalkan font label */
            color: #444;
            /* Warna teks label */
        }

        input[type="text"],
        input[type="date"] {
            width: 100%;
            /* Input mengambil lebar penuh */
            padding: 12px 14px;
            /* Padding dalam input */
            margin-bottom: 20px;
            /* Jarak bawah antar input */
            border: 1px solid #ccc;
            /* Border input */
            border-radius: 8px;
            /* Sudut melengkung pada input */
            font-size: 15px;
            /* Ukuran font input */
            transition: border-color 0.3s;
            /* Efek transisi border saat fokus */
        }

        input:focus {
            border-color: #5A67D8;
            /* Warna border saat input fokus */
            outline: none;
            /* Menghilangkan outline default */
        }

        .btn-container {
            text-align: center;
            /* Menyusun tombol di tengah */
        }

        button {
            width: 160px;
            /* Lebar tombol */
            padding: 12px;
            /* Padding tombol */
            background-color: #5A67D8;
            /* Warna latar belakang tombol */
            color: white;
            /* Warna teks tombol */
            font-weight: bold;
            /* Menebalkan teks tombol */
            border: none;
            /* Menghilangkan border */
            border-radius: 8px;
            /* Sudut melengkung pada tombol */
            cursor: pointer;
            /* Mengubah pointer saat tombol di-hover */
            transition: background-color 0.3s ease;
            /* Efek transisi pada latar belakang */
        }

        button:hover {
            background-color: #434190;
            /* Warna latar belakang tombol saat hover */
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 30px 20px;
                /* Mengurangi padding pada layar kecil */
            }
        }

        /* ==== Footer ==== */
        footer {
            background: linear-gradient(90deg, #4e54c8, #8f94fb);
            /* Gradien latar belakang footer */
            color: white;
            /* Warna teks footer */
            text-align: center;
            /* Menyusun teks di tengah */
            padding: 20px 15px;
            /* Padding footer */
            font-size: 14px;
            /* Ukuran font footer */
            box-shadow: 0 -4px 10px rgba(0, 0, 0, 0.1);
            /* Bayangan footer */
        }

        .footer-container p {
            margin: 0;
            /* Menghilangkan margin pada paragraf footer */
            font-style: italic;
            /* Memberikan efek italic pada teks */
            letter-spacing: 0.5px;
            /* Jarak antar huruf */
        }

        .footer-container strong {
            color: #ffd700;
            /* Warna teks untuk elemen strong */
        }

        .logout-button {
            position: fixed;
            /* Tombol logout berada di posisi tetap */
            bottom: 20px;
            /* Jarak dari bawah */
            right: 20px;
            /* Jarak dari kanan */
            background-color: #e53e3e;
            /* Warna latar belakang merah pada tombol logout */
            color: white;
            /* Warna teks tombol logout */
            font-weight: bold;
            /* Menebalkan teks tombol logout */
            padding: 12px 20px;
            /* Padding tombol */
            border: none;
            /* Menghilangkan border tombol */
            border-radius: 8px;
            /* Sudut melengkung tombol */
            cursor: pointer;
            /* Mengubah pointer saat tombol di-hover */
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            /* Bayangan tombol logout */
            transition: background-color 0.3s ease;
            /* Efek transisi warna latar belakang */
        }

        .logout-button:hover {
            background-color: #c53030;
            /* Warna latar belakang tombol logout saat hover */
        }

        /* ==== Tabel ==== */
        table {
            border-collapse: collapse;
            /* Menghilangkan jarak antara sel tabel */
            width: 100%;
            /* Lebar tabel */
            margin: 0 auto;
            /* Menyusun tabel di tengah */
            background-color: white;
            /* Warna latar belakang tabel */
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            /* Bayangan tabel */
        }

        th {
            border: 1px solid #ddd;
            /* Border pada header tabel */
            padding: 12px;
            /* Padding dalam header tabel */
            text-align: center;
            /* Menyusun teks di tengah header tabel */
        }

        td {
            border: 1px solid #ddd;
            /* Border pada sel tabel */
            padding: 12px;
            /* Padding dalam sel tabel */
        }

        th {
            background-color: #4e54c8;
            /* Warna latar belakang header tabel */
            color: white;
            /* Warna teks header tabel */
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
            /* Warna latar belakang baris genap */
        }

        tr:hover {
            background-color:rgb(254, 254, 0);
            /* Warna latar belakang saat baris di-hover */
        }

        h2 {
            font-size: 28px;
            /* Ukuran font untuk heading 2 */
            color: #333;
            /* Warna teks heading 2 */
            text-align: center;
            /* menyusun teks di tengah */
        }

        /* ==== Form Pencarian ==== */
        .search-form {
            display: flex;
            /* Menggunakan flexbox pada form pencarian */
            flex-direction: column;            ;
            /* Elemen dalam form disusun secara vertikal */
            align-items: center;
            /* Menyusun elemen secara horizontal di tengah */
            margin-top: 0px;
            /* Memberikan jarak atas pada form */
        }

        .search-form input {
            padding: 10px;
            /* Padding dalam input pencarian */
            width: 80%;
            /* Lebar input pencarian */
            margin-bottom: 2px;
            /* Memberikan jarak bawah */
            border-radius: 8px;
            /* Sudut melengkung pada input pencarian */
            border: 1px solid #ccc;
            /* Border input pencarian */
            font-size: 16px;
            /* Ukuran font untuk input pencarian */
            outline: none;
            /* Menghilangkan outline saat fokus */
        }

        .search-form button {
            padding: 12px 20px;
            /* Padding tombol pencarian */
            background-color: #5A67D8;
            /* Warna latar belakang tombol pencarian */
            color: white;
            /* Warna teks tombol pencarian */
            border: none;
            /* Menghilangkan border tombol */
            border-radius: 8px;
            /* Sudut melengkung tombol */
            cursor: pointer;
            /* Mengubah pointer saat tombol di-hover */
            transition: background-color 0.3s ease;
            /* Efek transisi warna latar belakang */
        }

        .search-form button:hover {
            background-color: #434190;
            /* Warna latar belakang tombol pencarian saat hover */
        }


        /* ==== Baris Tanggal ==== */
        .date-row {
            display: flex;
            /* Menggunakan flexbox pada baris tanggal */
            gap: 0px;
            /* Memberikan jarak antar elemen */
            justify-content: center;
            /* Menyusun elemen ke tengah */
            flex-wrap: wrap;
            /* Membungkus elemen jika ruang terbatas */
        }

        .date-group {
            
            display: flex;
            /* Menggunakan flexbox pada grup tanggal */
            flex-direction: column;
            /* Mengatur elemen dalam grup tanggal menjadi vertikal */
            align-items: center;
            /* Menyusun elemen di tengah */
        }

        .date-group label {
            font-size: 13px;
            /* Ukuran font label pada grup tanggal */
            color: #333;
            /* Warna teks label */
            margin-bottom: 4px;
            /* Memberikan jarak bawah pada label */
        }

        /* ==== Baris Atas ==== */
        .top-row {
            display: flex;
            align-items: center;
            /* Menyelaraskan vertikal input dan tombol */
            gap: 10px;
            /* Jarak antar input dan tombol */
            justify-content: flex-start;
            /* Susun dari kiri (atau pakai center jika ingin di tengah halaman) */
            flex-wrap: nowrap;
            /* Hindari pembungkus agar tetap sejajar */
        }


        /* ==== Pembungkus Aksi Tabel ==== */
        .table-action-wrapper {
            width: 100%;
            /* Lebar pembungkus aksi tabel */
            margin: 0 auto;
            /* Menyusun pembungkus di tengah */
            display: flex;
            /* Menggunakan flexbox pada pembungkus aksi tabel */
            justify-content: flex-end;
            /* Menyusun elemen aksi ke kanan */
            margin-bottom: 15px;
            /* Memberikan margin bawah */
        }

        /* ==== Pembungkus Tombol Print ==== */
        .print-button-wrapper {
            text-align: right;
            /* Menyusun tombol print ke kanan */
        }

        /* ==== Tombol Print ==== */
        .print-button {
            background: linear-gradient(90deg, #2b6cb0, #4e54c8);
            /* Gradien latar belakang tombol print */
            color: white;
            /* Warna teks tombol print */
            padding: 10px 20px;
            /* Padding tombol print */
            font-size: 15px;
            /* Ukuran font tombol print */
            font-weight: 600;
            /* Menebalkan teks tombol print */
            border: none;
            /* Menghilangkan border tombol print */
            width: 270px;
            /* Lebar tombol print */
            border-radius: 8px;
            /* Sudut melengkung pada tombol print */
            cursor: pointer;
            /* Mengubah pointer saat tombol di-hover */
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
            /* Bayangan pada tombol print */
            transition: background 0.3s, transform 0.2s ease-in-out;
            /* Efek transisi latar belakang dan transformasi tombol */
            display: inline-flex;
            /* Menggunakan flexbox untuk tombol print */
            align-items: center;
            /* Menyusun elemen di tengah tombol */
            gap: 8px;
            /* Memberikan jarak antar elemen dalam tombol */
        }

        .print-button:hover {
            background: linear-gradient(90deg, #1a365d, #2c5282);
            /* Warna latar belakang saat tombol print di-hover */
            transform: translateY(-2px);
            /* Efek pergerakan tombol saat di-hover */
        }

        .content-container {
            width: 100%;
            max-width: 100%;
            padding: 0px;
            box-sizing: border-box;
        }

        .content-container h2 {
            margin-top: 0;
            /* Menghilangkan margin atas pada judul */
        }

        @media (max-width: 600px) {
            .print-button {
                width: 100%;
                /* Lebar tombol print pada layar kecil */
            }
        }
    </style>

</head>

<body>

    <!-- Header -->
    <header>
        <div class="nav-container">
            <h3 class="logo">Aplikasi Pengambilan Barang</h3> <!-- Logo aplikasi dengan teks "Aplikasi Pengambilan Barang" -->
            <nav class="nav-links">
                <!-- Menu navigasi dengan link menuju berbagai halaman admin -->
                <a href="dashboard_admin.php">Dashboard</a>
                <a href="kelolauser_admin.php">Kelola User</a>
                <a href="daftarbarang_admin.php">Daftar Barang</a>
                <a href="tambahbarang_admin.php">Tambah Barang Baru</a>
                <a href="updatebarang_admin.php">Update Barang</a>
                <!-- Menambahkan kelas "active" pada link jika halaman ini adalah "Riwayat Pengambilan Barang" -->
                <a href="riwayatpengambilan_admin.php" class="<?= ($currentPage == 'riwayatpengambilan_admin.php') ? 'active' : ''; ?>">Riwayat Pengambilan Barang</a>
            </nav>
        </div>
    </header>

    <!-- Halaman Login -->
    <main>
        <div class="content-container">
            <!-- Form Pencarian Barang -->
            <form method="GET" class="search-form">
                <!-- Form pencarian dengan input untuk mencari barang berdasarkan nama -->
                <div class="top-row">
                    <input type="text" name="search" placeholder="Cari barang..." value="<?= htmlspecialchars($search) ?>"> <!-- Input pencarian barang -->
                    <button type="submit">Cari</button> <!-- Tombol untuk melakukan pencarian -->
                </div>

                <!-- Form untuk memilih rentang tanggal -->
                <div class="date-row">
                    <div class="date-group">
                        <label for="from">Dari</label> <!-- Label untuk input tanggal mulai -->
                        <input type="date" id="from" name="from" value="<?= isset($_GET['from']) ? date('Y-m-d', strtotime($_GET['from'])) : '' ?>"> <!-- Input untuk memilih tanggal mulai -->
                    </div>
                    <div class="date-group">
                        <label for="to">Sampai</label> <!-- Label untuk input tanggal akhir -->
                        <input type="date" id="to" name="to" value="<?= isset($_GET['to']) ? date('Y-m-d', strtotime($_GET['to'])) : '' ?>"> <!-- Input untuk memilih tanggal akhir -->
                    </div>
                </div>
            </form>

            <h2>Riwayat Pengambilan Barang</h2> <!-- Judul halaman untuk riwayat pengambilan barang -->

            <!-- Wrapper untuk tombol cetak -->
            <div class="table-action-wrapper">
                <div class="print-button-wrapper">
                    <!-- Tombol untuk mencetak riwayat pengambilan barang -->
                    <button onclick="printRiwayat()" class="print-button">
                        🖨️ Cetak Riwayat Pengambilan
                    </button>
                </div>
            </div>

            <!-- Tabel untuk menampilkan riwayat pengambilan barang -->
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
                    <?php if (isset($result) && $result->num_rows > 0): ?> <!-- Mengecek apakah ada data barang -->
                        <?php $no = $offset + 1; ?>
                        <?php while ($row = $result->fetch_assoc()): ?> <!-- Menampilkan data riwayat pengambilan barang dalam baris tabel -->
                            <tr>
                                <td style="text-align:center";><?= $no++ ?></td>
                                <td style="text-align:center";><?= htmlspecialchars($row['kodebarang']) ?></td>
                                <td><?= htmlspecialchars($row['namabarang']) ?></td>
                                <td style="text-align:center";><?= htmlspecialchars($row['jumlah']) ?></td>
                                <td><?= date('d-m-Y', strtotime($row['tanggal'])) ?></td>
                                <td><?= htmlspecialchars($row['pengambil']) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6">Tidak ada data barang.</td> <!-- Menampilkan pesan jika tidak ada data -->
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>


    <!-- Menampilkan navigasi halaman jika ada lebih dari satu halaman -->
    <?php if ($total_pages > 1): ?>
        <div style="margin-top: 20px;">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <!-- Link untuk berpindah halaman dengan penandaan halaman aktif -->
                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>" style="margin: 0 5px; padding: 8px 12px; background-color: <?= $i == $page ? '#4e54c8' : '#ccc' ?>; color: white; text-decoration: none; border-radius: 5px;">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>

    </main>

    <!-- Footer -->
    <footer>
        <div class="footer-container">
            <p>&copy; <?= date("Y"); ?> By: <strong>Asriadi Kreatif</strong></p> <!-- Menampilkan copyright dengan nama pembuat -->
        </div>
    </footer>

    <!-- Form logout jika pengguna sudah login -->
    <?php if (isset($_SESSION["namalengkap"])): ?>
        <form action="logout.php" method="POST">
            <button type="submit" class="logout-button">Logout</button> <!-- Tombol logout -->
        </form>
    <?php endif; ?>

    <!-- JavaScript untuk mencetak riwayat pengambilan barang -->
    <script>
        function printRiwayat() {
            const params = new URLSearchParams(window.location.search);
            const search = params.get('search') || '';
            const from = params.get('from') || '';
            const to = params.get('to') || '';

            // Membuka halaman baru untuk mencetak riwayat pengambilan berdasarkan filter pencarian dan tanggal
            const printUrl = `print_riwayat_pengambilan.php?search=${encodeURIComponent(search)}&from=${encodeURIComponent(from)}&to=${encodeURIComponent(to)}`;
            window.open(printUrl, '_blank');
        }
    </script>

</body>


</html>