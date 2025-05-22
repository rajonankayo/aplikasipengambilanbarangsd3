<?php
session_start();

if (!isset($_SESSION['is_login'])) {
    header("location: login.php");
    exit;
}

include 'service/database.php';

$setting = $db->query("SELECT * FROM pengaturan LIMIT 1")->fetch_assoc();

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>APLIKASI PENGAMBILAN BARANG</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
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

        .welcome-box {
            background-color: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
            max-width: 650px;
            width: 100%;
            text-align: center;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #444;
        }

        input[type="text"],
        input[type="file"] {
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
            background-color:rgb(76, 12, 239);
        }

        .message {
            margin-bottom: 20px;
            color: green;
            font-weight: bold;
        }

        footer {
            background: linear-gradient(90deg, #4e54c8, #8f94fb);
            color: white;
            text-align: center;
            padding: 20px 15px;
            font-size: 14px;
            box-shadow: 0 -4px 10px rgba(0, 0, 0, 0.1);
        }

        .footer-container strong {
            color: #ffd700;
        }

        .logout-settings-wrapper {
            position: fixed;
            bottom: 20px;
            right: 20px;
            display: flex;
            gap: 12px;
        }

        .settings-button,
        .logout-button {
            background-color: #3182ce;
            color: white;
            font-weight: bold;
            padding: 12px 20px;
            border: none;
            width: 160px;
            height: 40px;
            border-radius: 8px;
            cursor: pointer;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            transition: background-color 0.3s ease;
        }

        .settings-button:hover {
            background-color: rgb(4, 48, 247);
        }

        .logout-button:hover {
            background-color: rgb(215, 16, 16);
        }
    </style>
</head>
<body>

    <!-- Header -->
    <header>
        <div class="nav-container">
            <h3 class="logo">
                <?php if (!empty($setting['logo_path'])): ?>
                    <img src="<?= $setting['logo_path'] ?>" alt="Logo" style="height: 40px; vertical-align: middle;">
                <?php endif; ?>
                <?= htmlspecialchars($setting['unit_name'] ?? 'Aplikasi Pengambilan Barang') ?>
            </h3>
            <nav class="nav-links">
                <a href="dashboard_admin.php">Dashboard</a>
                <a href="kelolauser_admin.php">Kelola User</a>
                <a href="daftarbarang_admin.php">Daftar Barang</a>
                <a href="tambahbarang_admin.php">Tambah Barang Baru</a>
                <a href="updatebarang_admin.php">Update Barang</a>
                <a href="riwayatpengambilan_admin.php">Riwayat Pengambilan Barang</a>
            </nav>
        </div>
    </header>

    <!-- Form Pengaturan -->
    <main>
        <div class="welcome-box">
            <h1>Form Pengaturan Aplikasi</h1>

            <?php if (isset($_GET['status']) && $_GET['status'] == 'sukses'): ?>
                <div class="message">✅ Pengaturan berhasil disimpan!</div>
            <?php endif; ?>

            <form action="simpan_pengaturan.php" method="POST" enctype="multipart/form-data">
                <label for="unit_name">Nama Unit:</label>
                <input type="text" id="unit_name" name="unit_name" placeholder="Masukkan Nama Unit" required>

                <label for="logo">Upload Logo:</label>
                <input type="file" id="logo" name="logo" accept="image/png, image/jpeg" required>

                <div class="btn-container">
                    <button type="submit">Simpan Pengaturan</button>
                </div>
            </form>
        </div>
    </main>

    <!-- Footer -->
    <footer>
        <div class="footer-container">
            <p>&copy; <?= date("Y"); ?> By: <strong>Asriadi Kreatif</strong></p>
        </div>
    </footer>

    <!-- Logout & Settings -->
    <?php if (isset($_SESSION["namalengkap"])): ?>
        <div class="logout-settings-wrapper">
            <form action="settings.php" method="GET">
                <button type="submit" class="settings-button">⚙️ Pengaturan</button>
            </form>
            <form action="logout.php" method="POST">
                <button type="submit" class="logout-button">Logout</button>
            </form>
        </div>
    <?php endif; ?>

</body>
</html>
