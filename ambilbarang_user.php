<?php
    // Menyertakan file koneksi database
    include "service/database.php";

    // Memulai session
    session_start();

    // Mengecek apakah user sudah login, jika belum, redirect ke halaman login
    if (!isset($_SESSION['is_login'])) {
        header("Location: login.php");
        exit;
    }

    // Variabel pesan untuk ditampilkan ke pengguna
    $successMessage = "";  // Pesan jika proses berhasil
    $errorMessage = "";    // Pesan jika terjadi kesalahan

    // Ambil semua data barang dari tabel `daftarbarang`
    $barangResult = $db->query("SELECT kodebarang, namabarang, stokbarang FROM daftarbarang");

    $barangArray = []; // Array untuk menyimpan data barang

    // Jika data barang ditemukan, simpan dalam array asosiatif berdasarkan kodebarang
    if ($barangResult && $barangResult->num_rows > 0) {
        while ($row = $barangResult->fetch_assoc()) {
            $barangArray[$row['kodebarang']] = $row;
        }
    } else {
        // Jika tidak ada data barang, tampilkan pesan error
        $errorMessage = "❌ Tidak ada data barang ditemukan.";
    }

    // Menangani pengiriman form pengambilan barang (POST)
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        // Mengamankan inputan user
        $kodebarang = htmlspecialchars($_POST['kodebarang']); // Kode barang yang dipilih
        $jumlah = (int) $_POST['jumlah']; // Jumlah barang yang diambil
        $pengambil = $_SESSION['namalengkap']; // Username dari user yang login

        // Validasi input: pastikan kode barang tidak kosong dan jumlah lebih dari 0
        if (empty($kodebarang) || $jumlah <= 0) {
            $errorMessage = "❌ Kode barang atau jumlah tidak valid.";
        } else {
            // Ambil data barang berdasarkan kode
            $stmt = $db->prepare("SELECT namabarang, stokbarang FROM daftarbarang WHERE kodebarang = ?");
            $stmt->bind_param("s", $kodebarang);
            $stmt->execute();
            $result = $stmt->get_result();
            $barang = $result->fetch_assoc();
            $stmt->close();

            // Cek apakah barang tersedia dan stok mencukupi
            if ($barang && $barang['stokbarang'] >= $jumlah) {
                $namabarang = $barang['namabarang'];

                // Kurangi stok barang di tabel `daftarbarang`
                $stmt = $db->prepare("UPDATE daftarbarang SET stokbarang = stokbarang - ? WHERE kodebarang = ?");
                $stmt->bind_param("is", $jumlah, $kodebarang);
                $stmt->execute();
                $stmt->close();

                // Catat transaksi pengambilan ke dalam tabel `riwayatpengambilan`
                $stmt = $db->prepare("INSERT INTO riwayatpengambilan (kodebarang, namabarang, jumlah, tanggal, pengambil) VALUES (?, ?, ?, NOW(), ?)");
                $stmt->bind_param("ssis", $kodebarang, $namabarang, $jumlah, $pengambil);
                $stmt->execute();
                $stmt->close();

                // Set pesan sukses
                $successMessage = "✅ Pengambilan barang berhasil.";
            } else {
                // Barang tidak ditemukan atau stok tidak cukup
                $errorMessage = "❌ Stok tidak mencukupi atau barang tidak ditemukan.";
            }
        }
    }

    // Menyimpan nama file halaman saat ini ke variabel untuk penanda menu aktif di navigasi
    $currentPage = basename($_SERVER['PHP_SELF']); // Misalnya: "ambilbarang_user.php"
?>


<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Form Pengambilan Barang</title>
    <style>
        /* ==== Global Styles ==== */
        * {
            box-sizing: border-box; /* Menjadikan ukuran elemen menyertakan padding dan border */
        }

        body {
            margin: 0;
            height: 100vh;
            display: flex;
            flex-direction: column; /* Tata letak vertikal */
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #74ebd5, #9face6); /* Gradasi latar belakang */
        }

        main {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 30px 20px; /* Ruang dalam untuk konten utama */
        }

        /* ==== Header ==== */
        header {
            background: linear-gradient(90deg, #4e54c8, #8f94fb); /* Gradasi horizontal */
            padding: 20px 30px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1); /* Bayangan lembut bawah */
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
            color: yellow;             /* Warna khusus untuk halaman aktif */
            font-weight: bold;
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
            transform: translateY(-2px); /* Efek hover mengangkat link */
        }

        /* ==== Form Login Box ==== */
        .login-container {
            background: white;
            padding: 30px 25px;
            border-radius: 12px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15); /* Bayangan dalam */
            width: 100%;
            max-width: 400px;
            animation: fadeIn 0.6s ease-in-out; /* Animasi muncul */
        }

        .login-container h2 {
            text-align: center;
            margin-bottom: 20px;
            font-size: 24px;
            color: #333;
        }

        label {
            display: block;
            font-size: 12px;
            font-weight: 500;
            color: #444;
        }

        /* ==== Input Elements ==== */
        select,
        input[type="text"],
        input[type="number"],
        input[type="password"] {
            width: 100%;
            padding: 8px 10px;
            margin-bottom: 3px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        input:focus {
            border-color: #5A67D8; /* Warna border saat input aktif */
            outline: none;
        }

        .btn-container {
            text-align: center;
            margin-top: 10px;
        }

        /* ==== Tombol ==== */
        button {
            width: 140px;
            padding: 10px;
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

        /* ==== Pesan Status ==== */
        .message {
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
        }

        .success {
            background-color: #d4edda; /* Latar hijau muda */
            color: #155724;            /* Teks hijau tua */
        }

        .error {
            background-color: #f8d7da; /* Latar merah muda */
            color: #721c24;            /* Teks merah tua */
        }

        /* ==== Link Kembali ==== */
        .back-link {
            margin-top: 20px;
            text-align: center;
        }

        .back-link a {
            text-decoration: none;
            color: #5A67D8;
            font-weight: bold;
            transition: color 0.3s;
        }

        .back-link a:hover {
            color: #434190;
        }

        /* ==== Kotak Sambutan (Opsional) ==== */
        .welcome-box {
            background-color: #fff;
            padding: 40px 35px;
            border-radius: 12px;
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            width: 100%;
            animation: slideFadeIn 0.6s ease-in-out;
            text-align: center;
        }

        .welcome-box h1 {
            font-size: 28px;
            color: #4e54c8;
            margin-bottom: 15px;
        }

        .welcome-box p {
            font-size: 16px;
            color: #333;
            line-height: 1.6;
        }

        /* ==== Animasi ==== */
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

        @keyframes slideFadeIn {
            0% {
                opacity: 0;
                transform: translateY(20px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
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

        /* ==== Tombol Logout Tetap di Posisi ==== */
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

        /* ==== Responsif untuk Layar Kecil ==== */
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

        @media (max-width: 480px) {
            .login-container {
                padding: 20px 15px;
            }

            button {
                width: 100%; /* Tombol memenuhi lebar container */
            }
        }




    </style>
</head>
<body>

    <!-- ===== HEADER NAVIGATION ===== -->
    <header>
        <div class="nav-container">
            <!-- Judul aplikasi -->
            <h3 class="logo">Aplikasi Pengambilan Barang</h3>

            <!-- Navigasi antar halaman -->
            <nav class="nav-links">
                <a href="dashboard_user.php">Dashboard</a>
                <a href="daftarbarang_user.php">Daftar Barang</a>
                <a href="ambilbarang_user.php" class="<?= ($currentPage == 'ambilbarang_user.php') ? 'active' : ''; ?>">Ambil Barang</a>
                <a href="riwayatpengambilan_user.php">Riwayat Pengambilan</a>
            </nav>
        </div>
    </header>

    <!-- ===== MAIN CONTENT (FORM) ===== -->
    <main>
        <div class="login-container">
            <!-- Judul Form -->
            <h2 style="text-align: center; margin-bottom: 20px;">Form Pengambilan Barang</h2>

            <!-- Menampilkan pesan sukses atau error dari proses form -->
            <?php if ($successMessage): ?>
                <p class="message success"><?= $successMessage ?></p>
            <?php elseif ($errorMessage): ?>
                <p class="message error"><?= $errorMessage ?></p>
            <?php endif; ?>

            <!-- Form pengambilan barang -->
            <form method="POST" action="">
                <!-- Dropdown pilihan barang -->
                <label for="kodebarang">Pilih Barang:</label>
                <select name="kodebarang" id="kodebarang" required onchange="updateForm()">
                    <option value="" disabled selected>-- Pilih Barang --</option>
                    <?php foreach ($barangArray as $kode => $data): ?>
                        <!-- Menampilkan nama barang + stok -->
                        <option value="<?= $kode ?>"><?= $data['namabarang'] ?> (Stok: <?= $data['stokbarang'] ?>)</option>
                    <?php endforeach; ?>
                </select>

                <!-- Field yang menampilkan kode dan nama barang secara otomatis -->
                <label>Kode Barang:</label>
                <input type="text" id="showKode" readonly>

                <label>Nama Barang:</label>
                <input type="text" id="showNama" readonly>

                <!-- Input jumlah barang yang ingin diambil -->
                <label for="jumlah">Jumlah:</label>
                <input type="number" name="jumlah" min="1" required>

                <!-- Menampilkan tanggal hari ini -->
                <label>Tanggal:</label>
                <input type="text" value="<?= date('d-m-Y') ?>" readonly>

                <!-- Menampilkan nama user yang sedang login -->
                <label>Pengambil:</label>
                <input type="text" value="<?= $_SESSION['namalengkap'] ?>" readonly>

                <!-- Tombol submit form -->
                <div class="btn-container">
                    <button type="submit">Ambil Barang</button>
                </div>
            </form>

            <!-- Link kembali ke daftar barang -->
            <div class="back-link">
                <a href="daftarbarang_user.php">← Kembali ke Daftar Barang</a>
            </div>

        </div>
    </main>

    <!-- ===== JAVASCRIPT UNTUK UPDATE FIELD DINAMIS ===== -->
    <script>
        // Data barang dalam bentuk objek JavaScript dari PHP
        const barangData = <?= json_encode($barangArray) ?>;

        // Fungsi untuk mengupdate tampilan kode & nama barang berdasarkan pilihan
        function updateForm() {
            const select = document.getElementById('kodebarang');
            const kode = select.value;
            const nama = barangData[kode]?.namabarang || '';

            // Menampilkan informasi barang yang dipilih
            document.getElementById('showKode').value = kode;
            document.getElementById('showNama').value = nama;
        }
    </script>

    <!-- ===== FOOTER ===== -->
    <footer>
        <div class="footer-container">
            <p>&copy; <?= date("Y"); ?> By: <strong>Asriadi Kreatif</strong></p>
        </div>
    </footer>

    <!-- ===== TOMBOL LOGOUT (jika user login) ===== -->
    <?php if (isset($_SESSION["namalengkap"])): ?>
        <form action="logout.php" method="POST">
            <button type="submit" class="logout-button">Logout</button>
        </form>
    <?php endif; ?>

</body>

</html>
