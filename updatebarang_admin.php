<?php
include "service/database.php";
session_start();

if (isset($_SESSION['is_login']) == false) {
    header("location:  login.php");
    exit;
}

$search = "";
if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
    $search_param = "%{$search}%";
    $stmt = $db->prepare("SELECT * FROM daftarbarang WHERE kodebarang LIKE ? OR namabarang LIKE ? OR kategoribarang LIKE ?");
    $stmt->bind_param("sss", $search_param, $search_param, $search_param);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
} else {
    $sql = "SELECT * FROM daftarbarang";
    $result = $db->query($sql);
}


// --- Pagination Setup ---
$limit = 7; // jumlah item per halaman
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Hitung total data
$totalQuery = "SELECT COUNT(*) as total FROM daftarbarang";
if (isset($_GET['search']) && $_GET['search'] !== "") {
    $search = trim($_GET['search']);
    $search_param = "%{$search}%";
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM daftarbarang WHERE kodebarang LIKE ? OR namabarang LIKE ? OR kategoribarang LIKE ?");
    $stmt->bind_param("sss", $search_param, $search_param, $search_param);
    $stmt->execute();
    $countResult = $stmt->get_result();
    $totalRow = $countResult->fetch_assoc();
    $total = $totalRow['total'];
    $stmt->close();

    // Ambil data sesuai halaman
    $stmt = $db->prepare("SELECT * FROM daftarbarang WHERE kodebarang LIKE ? OR namabarang LIKE ? OR kategoribarang LIKE ? LIMIT ?, ?");
    $stmt->bind_param("sssss", $search_param, $search_param, $search_param, $offset, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
} else {
    $count = $db->query($totalQuery);
    $totalRow = $count->fetch_assoc();
    $total = $totalRow['total'];

    $sql = "SELECT * FROM daftarbarang LIMIT $offset, $limit";
    $result = $db->query($sql);
}

$total_pages = ceil($total / $limit);

// Tentukan halaman saat ini
$currentPage = basename($_SERVER['PHP_SELF']); // Ambil nama file dari URL (misalnya 'index.php') untuk digunakan pada menu aktif


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
        }

        body {
            margin: 0;
            height: 100vh;
            display: flex;
            flex-direction: column;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #74ebd5, #9face6);
        }

        main {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            align-items: center;
            padding-bottom: 40px;
        }




        /* ==== Header ==== */
        header {
            background: linear-gradient(90deg, #4e54c8, #8f94fb);
            padding: 20px 30px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .nav-container {
            max-width: 1200px;
            margin: auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
        }

        .logo {
            color: white;
            margin: 0;
            font-size: 22px;
            font-weight: 600;
        }

        .nav-links a.active {
            color: yellow;
            /* Warna teks jika halaman aktif */
            font-weight: bold;
            /* Teks tebal untuk halaman aktif */
        }


        .nav-links a {
            color: white;
            text-decoration: none;
            margin-left: 20px;
            font-size: 16px;
            transition: color 0.3s, transform 0.3s;
        }

        .nav-links a:hover {
            color: #ffd700;
            transform: translateY(-2px);
        }

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
        .login-container {
            background: white;
            padding: 40px 30px;
            border-radius: 12px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
            width: 100%;
            max-width: 400px;
            animation: fadeIn 0.6s ease-in-out;
        }

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

        .btn-container {
            text-align: center;
        }

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

        .footer-container p {
            margin: 0;
            font-style: italic;
            letter-spacing: 0.5px;
        }

        .footer-container strong {
            color: #ffd700;
        }

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

        .logout-button:hover {
            background-color: #c53030;
        }

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
        }

        td {
            border: 1px solid #ddd;
            padding: 12px;
        }


        th {
            background-color: #4e54c8;
            color: white;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        tr:hover {
            background-color: rgb(254, 254, 0);
        }

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

        .search-form {
            margin-bottom: 20px;
        }

        .search-form input[type="text"] {
            padding: 10px;
            width: 320px;
            height: 38px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }

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
    </style>
</head>

<body>
    <div class="container">

        <!-- Header -->
        <header>
            <div class="nav-container">
                <h3 class="logo">Aplikasi Pengambilan Barang</h3>
                <nav class="nav-links">
                    <a href="dashboard_admin.php">Dashboard</a>
                    <a href="kelolauser_admin.php">Kelola User</a>
                    <a href="daftarbarang_admin.php">Daftar Barang</a>
                    <a href="tambahbarang_admin.php">Tambah Barang Baru</a>
                    <a href="updatebarang_admin.php" class="<?= ($currentPage == 'updatebarang_admin.php') ? 'active' : ''; ?>">Update Barang</a>
                    <a href="riwayatpengambilan_admin.php">Riwayat Pengambilan Barang</a>
                </nav>
            </div>
        </header>

        <!-- Halaman Login -->


        <main>
            <div class="content-container">
                <form method="GET" class="search-form">
                    <input type="text" name="search" placeholder="Cari barang..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit">Cari</button>
                </form>

                <h2>Update Barang</h2>

                <?php if (isset($_GET['update']) && $_GET['update'] === 'success'): ?>
                    <div style="background-color: #d4edda; color: #155724; padding: 12px; border: 1px solid #c3e6cb; border-radius: 6px; margin-bottom: 20px;">
                        ✅ Data barang berhasil diperbarui.
                    </div>
                <?php endif; ?>

                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Kode Barang</th>
                            <th>Nama Barang</th>
                            <th>Kategori Barang</th>
                            <th>Stok Barang</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php $no = $offset + 1; ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= htmlspecialchars($row['kodebarang']) ?></td>
                                    <td style="text-align: left" ;><?= htmlspecialchars($row['namabarang']) ?></td>
                                    <td style="text-align: left" ;><?= htmlspecialchars($row['kategoribarang']) ?></td>
                                    <td><?= htmlspecialchars($row['stokbarang']) ?></td>
                                    <td style="text-align: center;">
                                        <a href="editbarang_admin.php?kodebarang=<?= urlencode($row['kodebarang']) ?>" style="color: white; background-color: #38a169; padding: 6px 12px; border-radius: 4px; text-decoration: none; margin-right: 5px;">Edit</a>
                                        <a href="hapusbarang_admin.php?kodebarang=<?= urlencode($row['kodebarang']) ?>" style="color: white; background-color: #e53e3e; padding: 6px 12px; border-radius: 4px; text-decoration: none;" onclick="return confirm('Yakin ingin menghapus barang ini?')">Hapus</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5">Tidak ada data barang.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

            </div>
        </main>



        <?php if ($total_pages > 1): ?>
            <div style="margin-top: 20px;">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>" style="margin: 0 5px; padding: 8px 12px; background-color: <?= $i == $page ? '#4e54c8' : '#ccc' ?>; color: white; text-decoration: none; border-radius: 5px;">
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

        <?php if (isset($_SESSION["username"])): ?>
            <form action="logout.php" method="POST">
                <button type="submit" class="logout-button">Logout</button>
            </form>
        <?php endif; ?>


        <script>
            setTimeout(() => {
                const alert = document.querySelector('div[style*="background-color: #d4edda"]');
                if (alert) alert.style.display = 'none';
            }, 4000); // hilang dalam 4 detik
        </script>

    </div>
</body>

</html>