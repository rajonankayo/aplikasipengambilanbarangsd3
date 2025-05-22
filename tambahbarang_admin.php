<?php
include "service/database.php";
session_start();

if (!isset($_SESSION['is_login'])) {
    header("Location: login.php");
    exit;
}

$successMessage = "";
$errorMessage = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $kodebarang = trim($_POST['kodebarang']);
    $namabarang = trim($_POST['namabarang']);
    $kategoribarang = trim($_POST['kategoribarang']);
    $stokbarang = intval($_POST['stokbarang']);

    if ($kodebarang && $namabarang && $kategoribarang && is_numeric($stokbarang)) {
        // Cek apakah kode barang sudah ada
        $cekStmt = $db->prepare("SELECT kodebarang FROM daftarbarang WHERE kodebarang = ?");
        $cekStmt->bind_param("s", $kodebarang);
        $cekStmt->execute();
        $cekResult = $cekStmt->get_result();

        if ($cekResult->num_rows > 0) {
            $errorMessage = "❌ Kode barang <strong>$kodebarang</strong> sudah terdaftar di database.";
        } else {
            // Insert jika belum ada
            $stmt = $db->prepare("INSERT INTO daftarbarang (kodebarang, namabarang, kategoribarang, stokbarang) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sssi", $kodebarang, $namabarang, $kategoribarang, $stokbarang);

            if ($stmt->execute()) {
                $successMessage = "✅ Barang berhasil ditambahkan.";
            } else {
                $errorMessage = "❌ Gagal menambahkan barang.";
            }

            $stmt->close();
        }
        $cekStmt->close();
    } else {
        $errorMessage = "❌ Mohon lengkapi semua data dengan benar.";
    }
}

// Tentukan halaman saat ini
$currentPage = basename($_SERVER['PHP_SELF']); // Ambil nama file dari URL (misalnya 'index.php') untuk digunakan pada menu aktif


?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Tambah Barang - Aplikasi Pengambilan Barang</title>
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
            justify-content: center;
            align-items: center;
            padding: 20px;
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
            padding: 30px 30px;
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
        input[type="number"] {
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
            text-align: left;
        }


        th {
            background-color: #4e54c8;
            color: white;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        tr:hover {
            background-color: #e9f0ff;
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
    </style>
</head>

<body>
    <div class="container">
        <header>
            <div class="nav-container">
                <h3 class="logo">Aplikasi Pengambilan Barang</h3>
                <nav class="nav-links">
                    <a href="dashboard_admin.php">Dashboard</a>
                    <a href="kelolauser_admin.php">Kelola User</a>
                    <a href="daftarbarang_admin.php">Daftar Barang</a>
                    <a href="tambahbarang_admin.php" class="<?= ($currentPage == 'tambahbarang_admin.php') ? 'active' : ''; ?>">Tambah Barang Baru</a>
                    <a href="updatebarang_admin.php">Update Barang</a>
                    <a href="riwayatpengambilan_admin.php">Riwayat Pengambilan Barang</a>
                </nav>
            </div>
        </header>

        <main>
            <div class="login-container">
                <h1>Tambah Barang Baru</h1>

                <?php if ($successMessage): ?>
                    <div style="background-color: #d4edda; color: #155724; padding: 12px; border: 1px solid #c3e6cb; border-radius: 6px; margin-bottom: 20px;">
                        <?= $successMessage ?>
                    </div>
                <?php elseif ($errorMessage): ?>
                    <div style="background-color: #f8d7da; color: #721c24; padding: 12px; border: 1px solid #f5c6cb; border-radius: 6px; margin-bottom: 20px;">
                        <?= $errorMessage ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <label for="kodebarang">Kode Barang</label>
                    <input type="text" name="kodebarang" required>

                    <label for="namabarang">Nama Barang</label>
                    <input type="text" name="namabarang" required>

                    <label for="kategoribarang">Kategori Barang</label>
                    <input type="text" name="kategoribarang" required>

                    <label for="stokbarang">Stok Barang</label>
                    <input type="number" name="stokbarang" min="0" required>

                    <div class="btn-container">
                        <button type="submit">Tambah Barang</button>
                    </div>
                </form>
            </div>
        </main>

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
    </div>

    <script>
        setTimeout(() => {
            const alert = document.querySelector('div[style*="background-color: #d4edda"], div[style*="background-color: #f8d7da"]');
            if (alert) alert.style.display = 'none';
        }, 4000);
    </script>

</body>

</html>