<?php
// Mengimpor file koneksi ke database
include "service/database.php";

// Memulai session untuk mengelola status login
session_start();

// Mengecek apakah session 'is_login' tidak ada, jika tidak ada maka pengunjung akan diarahkan ke halaman login
if (!isset($_SESSION['is_login'])) {
    header("location: login.php"); // Redirect ke halaman login
    exit; // Menghentikan eksekusi lebih lanjut setelah redirect
}

// --- Inisialisasi Pencarian ---
// Mengambil nilai dari parameter 'search' jika ada, jika tidak maka kosongkan variabel search
$search = isset($_GET['search']) ? trim($_GET['search']) : "";
$search_param = "%{$search}%"; // Membuat parameter pencarian untuk query, dengan menambahkan wildcard (%)

// --- Pengaturan Pagination (Paginasi) ---
// Menetapkan jumlah data yang ditampilkan per halaman
$limit = 5;

// Mengambil parameter 'page' dari URL, jika ada. Jika tidak ada atau tidak valid, set halaman ke 1
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;

// Menghitung offset untuk query berdasarkan halaman yang dipilih
$offset = ($page - 1) * $limit;

// --- Hitung Total Data ---
// Jika ada parameter pencarian (search), lakukan query pencarian
if ($search !== "") {
    // Menyiapkan query untuk menghitung total data yang sesuai dengan pencarian (username atau nama lengkap)
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM users WHERE username LIKE ? OR namalengkap LIKE ?");
    $stmt->bind_param("ss", $search_param, $search_param); // Bind parameter pencarian untuk username dan nama lengkap
    $stmt->execute(); // Eksekusi query
    $countResult = $stmt->get_result(); // Ambil hasil query
    $totalRow = $countResult->fetch_assoc(); // Ambil data total
    $total = $totalRow['total']; // Ambil nilai total data
    $stmt->close(); // Tutup prepared statement

    // Mengambil data sesuai pencarian dan halaman (pagination)
    $stmt = $db->prepare("SELECT * FROM users WHERE username LIKE ? OR namalengkap LIKE ? LIMIT ?, ?");
    $stmt->bind_param("ssii", $search_param, $search_param, $offset, $limit); // Bind parameter pencarian dan pagination
    $stmt->execute(); // Eksekusi query
    $result = $stmt->get_result(); // Ambil hasil query
    $stmt->close(); // Tutup prepared statement
} else {
    // Jika tidak ada pencarian, hitung total semua data pengguna
    $count = $db->query("SELECT COUNT(*) as total FROM users");
    $totalRow = $count->fetch_assoc(); // Ambil data total
    $total = $totalRow['total']; // Ambil nilai total data

    // Mengambil data pengguna sesuai halaman (pagination)
    $stmt = $db->prepare("SELECT * FROM users LIMIT ?, ?");
    $stmt->bind_param("ii", $offset, $limit); // Bind parameter pagination
    $stmt->execute(); // Eksekusi query
    $result = $stmt->get_result(); // Ambil hasil query
    $stmt->close(); // Tutup prepared statement
}

// Menghitung jumlah total halaman berdasarkan total data dan limit per halaman
$total_pages = ceil($total / $limit);

// --- Ambil Data Edit Jika Ada ---
// Mengecek apakah ada parameter 'edit' di URL, jika ada berarti ingin mengedit data pengguna
$editUser = null;
if (isset($_GET['edit'])) {
    $editUsername = $_GET['edit']; // Ambil nilai username yang ingin diedit
    // Menyiapkan query untuk mengambil data pengguna berdasarkan username
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $editUsername); // Bind parameter username
    $stmt->execute(); // Eksekusi query
    $resultEdit = $stmt->get_result(); // Ambil hasil query
    // Jika data ditemukan, simpan hasilnya di $editUser
    if ($resultEdit->num_rows > 0) {
        $editUser = $resultEdit->fetch_assoc(); // Ambil data pengguna yang ingin diedit
    }
    $stmt->close(); // Tutup prepared statement
}

// Tentukan halaman saat ini untuk keperluan menu aktif
$currentPage = basename($_SERVER['PHP_SELF']); // Ambil nama file dari URL (misalnya 'index.php') untuk digunakan pada menu aktif

?>



<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>APLIKASI PENGAMBILAN BARANG</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* ==== Global Styles ==== */
        /* Atur box-sizing agar padding & border tidak menambah ukuran elemen */
        * {
            box-sizing: border-box;
        }

        /* Atur tampilan dasar body */
        body {
            margin: 0;
            height: 100vh;
            display: flex;
            flex-direction: column;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #74ebd5, #9face6);
            /* Gradasi background */
        }

        /* Main container agar fleksibel & responsif */
        main {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            align-items: center;
            padding-bottom: 40px;
        }

        /* ==== Header ==== */
        /* Styling untuk bagian header atas halaman */
        header {
            background: linear-gradient(90deg, #4e54c8, #8f94fb);
            padding: 20px 30px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        /* Container navigasi */
        .nav-container {
            max-width: 1200px;
            margin: auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
        }

        /* Judul/logo aplikasi */
        .logo {
            color: white;
            margin: 0;
            font-size: 22px;
            font-weight: 600;
        }

        /* Tautan aktif diberi warna dan bold */
        .nav-links a.active {
            color: yellow;
            font-weight: bold;
        }

        /* Gaya umum tautan navigasi */
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
        /* Container form login */
        .login-container {
            background: white;
            padding: 40px 30px;
            border-radius: 12px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
            width: 100%;
            max-width: 400px;
            animation: fadeIn 0.6s ease-in-out;
        }

        /* Animasi muncul dari bawah */
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

        /* Judul login */
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

        /* Input teks dan password */
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

        /* Efek fokus input */
        input:focus {
            border-color: #5A67D8;
            outline: none;
        }

        /* Tombol login */
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

        /* Responsif login container */
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

        /* Tombol logout tetap terlihat */
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

        /* ==== Tabel Data ==== */
        /* Atur tampilan tabel */
        table {
            border-collapse: collapse;
            width: 90%;
            margin: 0 auto;
            background-color: white;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        /* Header tabel */
        th {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: center;
            background-color: #4e54c8;
            color: white;
        }

        /* Isi tabel */
        td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }

        /* Zebra striping */
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        /* Hover effect */
        tr:hover {
            background-color: rgb(254, 254, 0);
        }

        /* Judul section */
        h2 {
            font-size: 28px;
            color: #333;
        }

        /* Container halaman */
        .container {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* Konten utama */
        .content-container {
            width: 95%;
            max-width: 1000px;
            text-align: center;
        }

        /* Form pencarian user */
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

        /* Atur ulang tabel untuk konten user */
        table {
            width: 100%;
            max-width: 1000px;
            margin: 0 auto;
            border-radius: 8px;
            overflow: hidden;
            table-layout: auto;
        }

        th,
        td {
            padding: 4px 8px;
            font-size: 14px;
        }

        td:nth-child(1),
        td:nth-child(5) {
            text-align: center;
        }

        /* ==== Tombol Aksi Edit & Delete ==== */
        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 10px;
        }

        .action-buttons a {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
            font-weight: bold;
            color: white;
        }

        .action-buttons .edit {
            background-color: #38a169;
        }

        .action-buttons .delete {
            background-color: #e53e3e;
        }

        /* Responsif tombol aksi */
        @media (max-width: 768px) {
            table {
                font-size: 14px;
            }

            .action-buttons {
                flex-direction: column;
                align-items: center;
            }

            .action-buttons a {
                width: 80px;
                text-align: center;
                font-size: 13px;
            }
        }

        /* ==== Modal Edit User ==== */
        /* Overlay hitam transparan */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 999;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        /* Konten modal form */
        .modal-content {
            background: white;
            padding: 40px 30px;
            border-radius: 12px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.25);
            width: 100%;
            max-width: 400px;
            animation: fadeIn 0.3s ease-in-out;
        }

        /* ==== Tombol Register ==== */
        .register-button {
            display: inline-block;
            background-color: #2b6cb0;
            color: white;
            padding: 10px 18px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            font-size: 14px;
            transition: background-color 0.3s ease;
        }

        .register-button:hover {
            background-color: #2c5282;
        }

        /* ==== Lebar Khusus Kolom Tabel ==== */
        /* Kolom nama user */
        th:nth-child(2),
        td:nth-child(2) {
            width: 100px;
        }

        /* Kolom nama lengkap */
        th:nth-child(3),
        td:nth-child(3) {
            width: 350px;
        }
    </style>
</head>

<body>
    <div class="container">

        <!-- ======= HEADER ======= -->
        <header>
            <div class="nav-container">
                <h3 class="logo">Aplikasi Pengambilan Barang</h3>
                <nav class="nav-links">
                    <!-- Navigasi antar halaman admin -->
                    <a href="dashboard_admin.php">Dashboard</a>
                    <a href="kelolauser_admin.php" class="<?= ($currentPage == 'kelolauser_admin.php') ? 'active' : ''; ?>">Kelola User</a>
                    <a href="daftarbarang_admin.php">Daftar Barang</a>
                    <a href="tambahbarang_admin.php">Tambah Barang Baru</a>
                    <a href="updatebarang_admin.php">Update Barang</a>
                    <a href="riwayatpengambilan_admin.php">Riwayat Pengambilan Barang</a>
                </nav>
            </div>
        </header>

        <!-- ======= MAIN CONTENT ======= -->
        <main>
            <div class="content-container">

                <!-- Form pencarian user -->
                <form method="GET" class="search-form">
                    <input type="text" name="search" placeholder="Cari User..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit">Cari</button>
                </form>

                <h2>Data Users</h2>

                <!-- Tombol untuk menambahkan user baru -->
                <div style="display: flex; justify-content: flex-end; margin-bottom: 16px;">
                    <a href="register_user.php" class="register-button">+ Register User</a>
                </div>

                <!-- Alert jika update sukses -->
                <?php if (isset($_GET['update']) && $_GET['update'] === 'success'): ?>
                    <div style="background-color: #d4edda; color: #155724; padding: 12px; border: 1px solid #c3e6cb; border-radius: 6px; margin-bottom: 20px;">
                        ✅ Data barang berhasil diperbarui.
                    </div>
                <?php endif; ?>

                <!-- Alert jika user berhasil atau gagal dihapus -->
                <?php if (isset($_GET['hapus']) && $_GET['hapus'] === 'success'): ?>
                    <div style="background-color: #f8d7da; color: #721c24; padding: 12px; border: 1px solid #f5c6cb; border-radius: 6px; margin-bottom: 20px;">
                        🗑️ User berhasil dihapus.
                    </div>
                <?php elseif (isset($_GET['hapus']) && $_GET['hapus'] === 'failed'): ?>
                    <div style="background-color: #fff3cd; color: #856404; padding: 12px; border: 1px solid #ffeeba; border-radius: 6px; margin-bottom: 20px;">
                        ⚠️ Gagal menghapus user.
                    </div>
                <?php endif; ?>

                <!-- ======= TABEL DATA USER ======= -->
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama User</th>
                            <th>Nama Lengkap</th>
                            <th>Password Terinkripsi</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php $no = $offset + 1; ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <!-- Menampilkan data user -->
                                    <td><?= $no++ ?></td>
                                    <td><?= htmlspecialchars($row['username']) ?></td>
                                    <td><?= htmlspecialchars($row['namalengkap']) ?></td>
                                    <td><?= htmlspecialchars($row['password']) ?></td>
                                    <td class="action-buttons">
                                        <!-- Aksi edit dan hapus -->
                                        <a href="?edit=<?= urlencode($row['username']) ?>" class="edit">Edit</a>
                                        <a href="hapususer_admin.php?username=<?= urlencode($row['username']) ?>" class="delete" onclick="return confirm('Yakin ingin menghapus user ini?')">Hapus</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <!-- Jika tidak ada data -->
                            <tr>
                                <td colspan="5">Tidak ada data user.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <!-- ======= MODAL EDIT USER ======= -->
                <?php if ($editUser): ?>
                    <div class="modal-overlay" onclick="closeModal(event)">
                        <div class="modal-content" onclick="event.stopPropagation();">
                            <h1>Edit User</h1>
                            <form action="edituser_admin.php" method="POST">
                                <!-- Input tersembunyi untuk username lama -->
                                <input type="hidden" name="username_lama" value="<?= htmlspecialchars($editUser['username']) ?>">

                                <!-- Form pengeditan user -->
                                <label for="username">Username</label>
                                <input type="text" name="username" id="username" value="<?= htmlspecialchars($editUser['username']) ?>" required>

                                <label for="namalengkap">Nama Lengkap</label>
                                <input type="text" name="namalengkap" id="namalengkap" value="<?= htmlspecialchars($editUser['namalengkap']) ?>" required>

                                <label for="password">Password Baru</label>
                                <input type="password" name="password" id="password" placeholder="Kosongkan jika tidak ingin ubah password">

                                <!-- Tombol simpan atau batal -->
                                <div class="btn-container" style="margin-top: 20px; display: flex; justify-content: space-between; gap: 10px;">
                                    <button type="submit">Simpan Perubahan</button>
                                    <button type="button" onclick="closeModal()">Batal</button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>

        <!-- ======= PAGINATION (jika ada banyak user) ======= -->
        <?php if ($total_pages > 1): ?>
            <div style="margin-top: 20px;">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>" style="margin: 0 5px; padding: 8px 12px; background-color: <?= $i == $page ? '#4e54c8' : '#ccc' ?>; color: white; text-decoration: none; border-radius: 5px;">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>

        <!-- ======= FOOTER ======= -->
        <footer>
            <div class="footer-container">
                <p>&copy; <?= date("Y"); ?> By: <strong>Asriadi Kreatif</strong></p>
            </div>
        </footer>

        <!-- ======= LOGOUT BUTTON ======= -->
        <?php if (isset($_SESSION["username"])): ?>
            <form action="logout.php" method="POST">
                <button type="submit" class="logout-button">Logout</button>
            </form>
        <?php endif; ?>

        <!-- ======= JAVASCRIPT ======= -->

        <!-- Menyembunyikan notifikasi update setelah 4 detik -->
        <script>
            setTimeout(() => {
                const alert = document.querySelector('div[style*="background-color: #d4edda"]');
                if (alert) alert.style.display = 'none';
            }, 4000);
        </script>

        <!-- Fungsi untuk menutup modal -->
        <script>
            function closeModal(event) {
                window.location.href = 'kelolauser_admin.php';
            }
        </script>

        <!-- Menghilangkan alert hapus user (sukses/gagal) setelah 4 detik -->
        <script>
            setTimeout(() => {
                const deleteSuccessAlert = document.querySelector('div[style*="background-color: #f8d7da"]');
                if (deleteSuccessAlert) deleteSuccessAlert.style.display = 'none';
            }, 4000);

            setTimeout(() => {
                const deleteFailedAlert = document.querySelector('div[style*="background-color: #fff3cd"]');
                if (deleteFailedAlert) deleteFailedAlert.style.display = 'none';
            }, 4000);
        </script>

    </div>
</body>

</html>