<?php
// Mengimpor file database.php untuk mengakses koneksi database
include "service/database.php";

// Menyiapkan variabel pencarian
$search = "";
$search_condition = "";

// Memeriksa apakah ada parameter pencarian di URL
if (isset($_GET['search']) && $_GET['search'] !== "") {
    // Mengambil nilai pencarian dan memangkas spasi
    $search = trim($_GET['search']);
    // Membuat parameter pencarian dengan wildcard untuk pencarian LIKE
    $search_param = "%{$search}%";
    
    // Menyiapkan query SQL untuk mencari barang berdasarkan kode, nama, atau kategori yang sesuai dengan pencarian
    $stmt = $db->prepare("SELECT * FROM daftarbarang WHERE kodebarang LIKE ? OR namabarang LIKE ? OR kategoribarang LIKE ?");
    // Mengikat parameter pencarian
    $stmt->bind_param("sss", $search_param, $search_param, $search_param);
    // Menjalankan query
    $stmt->execute();
    // Mengambil hasil query
    $result = $stmt->get_result();
    // Menutup statement setelah eksekusi
    $stmt->close();
} else {
    // Jika tidak ada pencarian, mengambil semua data barang dari tabel daftarbarang
    $result = $db->query("SELECT * FROM daftarbarang");
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Daftar Barang</title>
    <style>
        /* Pengaturan ukuran halaman dan margin saat mencetak */
        @page {
            size: A4 landscape;  /* Ukuran kertas A4 dengan orientasi landscape */
            margin-top: 20mm;
            margin-right: 20mm;
            margin-bottom: 20mm;
            margin-left: 20mm;
            counter-increment: page;
        }

        /* Menyembunyikan tombol cetak saat halaman dicetak */
        @media print {
            .print-button {
                display: none;
            }
        }

        /* Pengaturan styling halaman saat dicetak */
        html {
            counter-reset: page 1; /* Mulai nomor halaman dari 1 */
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
        }

        /* Pengaturan header dan footer agar tetap pada posisi tetap */
        header, footer {
            position: fixed;
            left: 0;
            right: 0;
            color: #555;
            font-size: 12px;
            width: 100%;
        }

        header {
            top: 0;
            text-align: center;
            padding: 5px 0;
        }

        footer {
            bottom: 0;
            text-align: left;
            padding: 5px 20mm;
        }

        .pagenum:before {
            content: counter(page);  /* Menampilkan nomor halaman */
        }

        .content {
            margin-top: 60px;   /* Memberikan ruang untuk header */
            margin-bottom: 60px; /* Memberikan ruang untuk footer */
        }

        h2 {
            text-align: center;
        }

        .date {
            text-align: right;
            font-size: 14px;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            border: 1px solid #ccc;
            padding: 10px;
            font-size: 14px;
        }

        th {
            background-color: #4e54c8;
            color: white;
            text-align: center;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .print-button {
            margin: 20px 0;
            text-align: center;
        }
    </style>
</head>
<body>
    <header></header>

    <!-- Tombol untuk mencetak halaman -->
    <div class="print-button">
        <button onclick="window.print()">🖨️ Cetak Halaman</button>
    </div>

    <!-- Judul Halaman -->
    <h2>Daftar Barang</h2>
    
    <!-- Menampilkan tanggal cetak -->
    <div class="date">Tanggal Cetak: <?= date('d-m-Y') ?></div>
    
    <!-- Tabel Daftar Barang -->
    <table>
        <thead>
            <tr>
                <th style="text-align: center";>No</th>
                <th style="text-align: center";>Kode Barang</th>
                <th style="text-align: center";>Nama Barang</th>
                <th style="text-align: center";>Kategori Barang</th>
                <th style="text-align: center";>Stok Barang</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
            <!-- Menampilkan data barang yang ditemukan -->
            <?php $no = 1; ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td style="text-align: center";><?= $no++ ?></td>
                    <td style="text-align: center";><?= htmlspecialchars($row['kodebarang']) ?></td>
                    <td style="text-align: left";><?= htmlspecialchars($row['namabarang']) ?></td>
                    <td style="text-align: left";><?= htmlspecialchars($row['kategoribarang']) ?></td>
                    <td style="text-align: center";><?= htmlspecialchars($row['stokbarang']) ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <!-- Menampilkan pesan jika tidak ada data ditemukan -->
            <tr><td colspan="5">Tidak ada data ditemukan.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>

    <!-- Footer dengan informasi nomor halaman dan aplikasi -->
    <footer>
        <span class="pagenum"></span> | Aplikasi Pengambilan Barang - By: Asriadi Kreatif
    </footer>
</body>
</html>
