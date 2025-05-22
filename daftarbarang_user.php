<?php
    // Mengimpor file koneksi ke database
    include "service/database.php";
    
    // Memulai session untuk melacak status login pengguna
    session_start();

    // Mengecek apakah pengguna sudah login, jika tidak, arahkan ke halaman login
    if(isset($_SESSION['is_login']) == false) {
        // Redirect pengguna ke halaman login jika session is_login tidak ditemukan
        header("location: login.php");
        exit; // Menghentikan eksekusi lebih lanjut setelah redirect
    }

    // Inisialisasi variabel pencarian
    $search = "";
    // Mengecek apakah ada parameter 'search' pada URL
    if (isset($_GET['search'])) {
        // Mengambil nilai pencarian dan membersihkan spasi di awal dan akhir
        $search = trim($_GET['search']);
        // Menyiapkan parameter pencarian dengan format wildcard (%)
        $search_param = "%{$search}%";
        
        // Menyiapkan query untuk mencari barang berdasarkan kode, nama, atau kategori
        $stmt = $db->prepare("SELECT * FROM daftarbarang WHERE kodebarang LIKE ? OR namabarang LIKE ? OR kategoribarang LIKE ?");
        // Mengikat parameter pencarian ke query
        $stmt->bind_param("sss", $search_param, $search_param, $search_param);
        // Menjalankan query
        $stmt->execute();
        // Mendapatkan hasil query
        $result = $stmt->get_result();
        // Menutup statement setelah selesai digunakan
        $stmt->close();
    } else {
        // Jika tidak ada pencarian, ambil semua data dari tabel daftarbarang
        $sql = "SELECT * FROM daftarbarang";
        $result = $db->query($sql);
    }

    // --- Pengaturan Pagination (Paginasi) ---
    $limit = 5; // Menentukan jumlah item yang ditampilkan per halaman
    // Mendapatkan nomor halaman saat ini dari parameter URL 'page'
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
    // Menghitung offset untuk query berdasarkan halaman yang diminta
    $offset = ($page - 1) * $limit;

    // Query untuk menghitung total jumlah data di tabel daftarbarang
    $totalQuery = "SELECT COUNT(*) as total FROM daftarbarang";
    // Mengecek apakah ada pencarian, jika ada hitung total hasil pencarian
    if (isset($_GET['search']) && $_GET['search'] !== "") {
        // Mengambil parameter pencarian yang dimasukkan pengguna
        $search = trim($_GET['search']);
        $search_param = "%{$search}%";
        
        // Menyiapkan query untuk menghitung total data berdasarkan pencarian
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM daftarbarang WHERE kodebarang LIKE ? OR namabarang LIKE ? OR kategoribarang LIKE ?");
        // Mengikat parameter pencarian ke query
        $stmt->bind_param("sss", $search_param, $search_param, $search_param);
        // Menjalankan query
        $stmt->execute();
        // Mendapatkan hasil query untuk jumlah data
        $countResult = $stmt->get_result();
        $totalRow = $countResult->fetch_assoc();
        // Mengambil total jumlah data dari hasil query
        $total = $totalRow['total'];
        // Menutup statement setelah selesai digunakan
        $stmt->close();

        // Ambil data sesuai dengan halaman yang diminta dengan parameter pencarian
        $stmt = $db->prepare("SELECT * FROM daftarbarang WHERE kodebarang LIKE ? OR namabarang LIKE ? OR kategoribarang LIKE ? LIMIT ?, ?");
        // Mengikat parameter untuk query pencarian dan pagination
        $stmt->bind_param("sssss", $search_param, $search_param, $search_param, $offset, $limit);
        // Menjalankan query
        $stmt->execute();
        // Mendapatkan hasil query
        $result = $stmt->get_result();
        // Menutup statement setelah selesai digunakan
        $stmt->close();
    } else {
        // Jika tidak ada pencarian, hitung total data tanpa filter
        $count = $db->query($totalQuery);
        $totalRow = $count->fetch_assoc();
        // Mendapatkan total jumlah data
        $total = $totalRow['total'];

        // Query untuk mengambil data sesuai dengan pagination
        $sql = "SELECT * FROM daftarbarang LIMIT $offset, $limit";
        $result = $db->query($sql);
    }

    // Menghitung jumlah halaman berdasarkan total data dan limit per halaman
    $total_pages = ceil($total / $limit);

    // Tentukan halaman saat ini (untuk menu aktif atau breadcrumb)
    $currentPage = basename($_SERVER['PHP_SELF']); // Mengambil nama file dari URL (misalnya 'index.php') untuk digunakan pada menu aktif

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
            box-sizing: border-box; /* Semua elemen menghitung padding dan border dalam ukuran total */
        }

        body {
            margin: 0;
            height: 100vh; /* Menetapkan tinggi penuh viewport */
            display: flex;
            flex-direction: column; /* Elemen anak (seperti header, main, footer) tersusun vertikal */
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; /* Font default */
            background: linear-gradient(135deg, #74ebd5, #9face6); /* Gradien latar belakang */
        }

        main {
            flex: 1; /* Memenuhi ruang sisa setelah header & footer */
            display: flex;
            flex-direction: column;
            justify-content: flex-start; /* Konten dimulai dari atas */
            align-items: center;
            padding-bottom: 40px;
        }

        /* ==== Header ==== */
        header {
            background: linear-gradient(90deg, #4e54c8, #8f94fb); /* Gradien horizontal */
            padding: 20px 30px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1); /* Bayangan bawah */
        }

        .nav-container {
            max-width: 1200px;
            margin: auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap; /* Navigasi tidak overflow jika sempit */
        }

        .logo {
            color: white;
            margin: 0;
            font-size: 22px;
            font-weight: 600;
        }

        .nav-links a.active {
            color: yellow; /* Tautan aktif */
            font-weight: bold;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            margin-left: 20px;
            font-size: 16px;
            transition: color 0.3s, transform 0.3s; /* Efek hover */
        }

        .nav-links a:hover {
            color: #ffd700;
            transform: translateY(-2px); /* Sedikit naik saat hover */
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

        /* ==== Login Container ==== */
        .login-container {
            background: white;
            padding: 40px 30px;
            border-radius: 12px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15); /* Bayangan lebih besar */
            width: 100%;
            max-width: 400px;
            animation: fadeIn 0.6s ease-in-out; /* Animasi masuk */
        }

        /* Animasi fadeIn */
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

        .login-container h1 {
            text-align: center;
            margin-bottom: 25px;
            font-size: 26px;
            color: #333;
        }

        /* ==== Form Elements ==== */
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
            transition: border-color 0.3s;
        }

        input:focus {
            border-color: #5A67D8;
            outline: none;
        }

        /* ==== Buttons ==== */
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

        button:hover {
            background-color: #434190;
        }

        /* Tombol logout di pojok bawah kanan */
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

        .logout-button:hover {
            background-color: #c53030;
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

        .footer-container p {
            margin: 0;
            font-style: italic;
            letter-spacing: 0.5px;
        }

        .footer-container strong {
            color: #ffd700;
        }

        /* ==== Table (Tabel Data Barang, Riwayat, dll.) ==== */
        table {
            border-collapse: collapse; /* Menggabungkan border */
            width: 90%;
            margin: 0 auto;
            background-color: white;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        th, td {
            border: 1px solid #ddd;
            padding: 12px;
        }

        th {
            text-align: center;
            background-color: #4e54c8;
            color: white;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        tr:hover {
            background-color: rgb(254, 254, 0);
        }

        /* ==== Container & Content ==== */
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

        h2 {
            font-size: 28px;
            color: #333;
        }

        /* ==== Search Form ==== */
        .search-form {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
            margin: 0;
            padding-top: 0;
        }

        .top-row {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap; /* Responsif */
        }

        .search-form input[type="text"] {
            padding: 10px;
            font-size: 14px;
            border-radius: 6px;
            border: 1px solid rgb(7, 196, 248); /* Warna border biru muda */
            width: 320px;
            height: 38px;
        }

        .search-form label {
            font-size: 13px;
            color: #333;
        }

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

        .search-form button:hover {
            background-color: #3b3fc1;
        }

        /* ==== Pagination ==== */
        .pagination, .pagination-fixed {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 30px;
            margin-bottom: 40px;
        }

        /* Pagination yang tetap di bagian bawah layar */
        .pagination-fixed {
            position: sticky;
            bottom: 0;
            background-color: #fff;
            padding: 12px 0;
            z-index: 10;
        }

        .pagination a, .pagination-fixed a {
            padding: 8px 14px;
            background-color: #ccc;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            transition: background-color 0.3s ease;
        }

.pagination a:hover, .pagination-fixed a:hover,
.pagination a.active, .pagination-fixed a.active {
    background-color: #4e54c8;
}



    </style>
</head>
<body>

    <!-- Header -->
    <header>
        <div class="nav-container">
            <h3 class="logo">Aplikasi Pengambilan Barang</h3>

            <!-- Navigasi Menu -->
            <nav class="nav-links">
                <!-- Link ke halaman Dashboard -->
                <a href="dashboard_user.php">Dashboard</a>

                <!-- Link ke Daftar Barang, akan diberi kelas 'active' jika halaman saat ini adalah daftarbarang_user.php -->
                <a href="daftarbarang_user.php" class="<?= ($currentPage == 'daftarbarang_user.php') ? 'active' : ''; ?>">Daftar Barang</a>

                <!-- Link ke halaman pengambilan barang -->
                <a href="ambilbarang_user.php">Ambil Barang</a>

                <!-- Link ke halaman riwayat pengambilan -->
                <a href="riwayatpengambilan_user.php">Riwayat Pengambilan</a>
            </nav>
        </div>
    </header>

    <!-- Main Content: Menampilkan daftar barang -->
    <main>
        <div class="content-container">

            <!-- Form pencarian barang -->
            <form method="GET" class="search-form">
                <div class="top-row">
                    <!-- Input pencarian, mempertahankan nilai pencarian sebelumnya -->
                    <input type="text" name="search" placeholder="Cari barang..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit">Cari</button>
                </div>
            </form>

            <!-- Judul Tabel -->
            <h2>Daftar Barang</h2>

            <!-- Tabel daftar barang -->
            <table>
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
                    <!-- Cek apakah ada hasil dari database -->
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php $no = $offset + 1; // Nomor urut berdasarkan halaman ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <!-- Menghindari XSS dengan htmlspecialchars -->
                                <td><?= htmlspecialchars($row['kodebarang']) ?></td>
                                <td style="text-align: left;"><?= htmlspecialchars($row['namabarang']) ?></td>
                                <td style="text-align: left;"><?= htmlspecialchars($row['kategoribarang']) ?></td>
                                <td><?= htmlspecialchars($row['stokbarang']) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <!-- Jika tidak ada data -->
                        <tr><td colspan="5">Tidak ada data barang.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <!-- Pagination: navigasi antar halaman -->
    <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <!-- Tautan ke halaman tertentu, aktif jika sedang di halaman tersebut -->
                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>" class="<?= $i == $page ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>

    <!-- Footer -->
    <footer>
        <div class="footer-container">
            <p>&copy; <?= date("Y"); ?> By: <strong>Asriadi Kreatif</strong></p>
        </div>
    </footer>

    <!-- Tombol logout hanya ditampilkan jika user sudah login -->
    <?php if (isset($_SESSION["namalengkap"])): ?>
        <form action="logout.php" method="POST">
            <button type="submit" class="logout-button">Logout</button>
        </form>
    <?php endif; ?>

</body>

</html>
