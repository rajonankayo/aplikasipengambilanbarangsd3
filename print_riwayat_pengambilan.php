<?php
// Mengimpor file database.php untuk mengakses koneksi database
include "service/database.php";
// Memulai session untuk memeriksa status login
session_start();

// Memeriksa apakah user sudah login (cek session)
if (!isset($_SESSION['is_login'])) {
    // Jika tidak login, redirect ke halaman login
    header("Location: login.php");
    exit;
}

// Mendapatkan parameter pencarian dan periode dari URL (GET)
$search = $_GET['search'] ?? '';  // Mengambil parameter pencarian (default kosong)
$from = $_GET['from'] ?? '';  // Mengambil tanggal mulai (default kosong)
$to = $_GET['to'] ?? '';  // Mengambil tanggal akhir (default kosong)

// Menyiapkan klausa WHERE untuk query SQL
$whereClauses = [];
$params = [];  // Array untuk menampung parameter query
$types = '';  // Tipe parameter untuk bind_param

// Jika ada pencarian, tambahkan kondisi pencarian ke klausa WHERE
if (!empty($search)) {
    $like = "%$search%";  // Menambahkan wildcard '%' untuk pencarian LIKE
    // Menambahkan kondisi pencarian berdasarkan kodebarang, namabarang, tanggal, dan pengambil
    $whereClauses[] = "(kodebarang LIKE ? OR namabarang LIKE ? OR tanggal LIKE ? OR pengambil LIKE ?)";
    // Menambahkan parameter pencarian ke array params
    $params = array_merge($params, [$like, $like, $like, $like]);
    $types .= 'ssss';  // Jenis parameter untuk bind_param (semua string)
}

// Jika ada rentang tanggal (from dan to), tambahkan kondisi ke klausa WHERE
if (!empty($from) && !empty($to)) {
    $whereClauses[] = "tanggal BETWEEN ? AND ?";  // Kondisi untuk rentang tanggal
    $params[] = $from;
    $params[] = $to;
    $types .= 'ss';  // Jenis parameter untuk bind_param (dua string)
} elseif (!empty($from)) {
    $whereClauses[] = "tanggal >= ?";  // Kondisi untuk tanggal mulai
    $params[] = $from;
    $types .= 's';  // Jenis parameter untuk bind_param (satu string)
} elseif (!empty($to)) {
    $whereClauses[] = "tanggal <= ?";  // Kondisi untuk tanggal akhir
    $params[] = $to;
    $types .= 's';  // Jenis parameter untuk bind_param (satu string)
}

// Jika ada klausa WHERE, gabungkan dengan operator AND
$whereSQL = '';
if (!empty($whereClauses)) {
    $whereSQL = 'WHERE ' . implode(' AND ', $whereClauses);
}

// Menyiapkan query SQL untuk mengambil data riwayat pengambilan
$sql = "SELECT * FROM riwayatpengambilan $whereSQL ORDER BY tanggal DESC";
$stmt = $db->prepare($sql);

// Jika ada parameter pencarian atau periode, bind parameter ke statement SQL
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

// Menjalankan query
$stmt->execute();
// Mendapatkan hasil query
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Cetak Riwayat Pengambilan</title>
    <style>
        /* Mengatur margin dan font untuk tampilan */
        body {
            font-family: Arial, sans-serif;
            margin: 30px;
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            padding: 10px;
            border: 1px solid #ccc;
            text-align: center;
        }

        th {
            background-color: #f2f2f2;
        }

        /* Tampilan catatan di atas tabel */
        .note {
            margin-bottom: 20px;
            text-align: center;
            font-style: italic;
            color: #555;
        }

        /* Tombol cetak */
        .print-button {
            margin: 20px 0;
            text-align: center;
        }

        @media print {
            .print-button {
                display: none;
                /* Menyembunyikan tombol cetak saat mencetak */
            }
        }

        /* Mengatur ukuran halaman dan margin saat mencetak */
        @page {
            size: A4 landscape;
            margin-top: 20mm;
            margin-right: 20mm;
            margin-bottom: 20mm;
            margin-left: 20mm;
            counter-increment: page;
        }

        html {
            counter-reset: page 1;
            /* Memulai nomor halaman dari 1 */
        }

        /* Styling untuk header dan footer agar tetap terlihat saat mencetak */
        header,
        footer {
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
            content: counter(page);
            /* Menampilkan nomor halaman */
        }

        .content {
            margin-top: 60px;
            /* Memberikan ruang untuk header */
            margin-bottom: 60px;
            /* Memberikan ruang untuk footer */
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

        th,
        td {
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
    </style>
</head>

<body>
    <header> </header>
    <!-- Tombol untuk mencetak halaman -->
    <div class="print-button">
        <button onclick="window.print()">🖨️ Cetak Halaman</button>
    </div>

    <!-- Judul dan catatan periode pencarian -->
    <h2>Riwayat Pengambilan Barang</h2>

    <div class="note">
        <?php if (!empty($from) && !empty($to)): ?>
            Periode: <?= date('d-m-Y', strtotime($from)) ?> sampai <?= date('d-m-Y', strtotime($to)) ?>
        <?php elseif (!empty($from)): ?>
            Mulai dari: <?= date('d-m-Y', strtotime($from)) ?>
        <?php elseif (!empty($to)): ?>
            Hingga: <?= date('d-m-Y', strtotime($to)) ?>
        <?php else: ?>
            Semua Data
        <?php endif; ?>
        <?php if (!empty($search)): ?>
            | Pencarian: "<?= htmlspecialchars($search) ?>"
        <?php endif; ?>
    </div>

    <!-- Tabel Riwayat Pengambilan Barang -->
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Kode Barang</th>
                <th>Nama Barang</th>
                <th>Jumlah</th>
                <th>Tanggal</th>
                <th>Pengambil</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): ?>
                <!-- Menampilkan hasil query riwayat pengambilan -->
                <?php $no = 1; ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= htmlspecialchars($row['kodebarang']) ?></td>
                        <td><?= htmlspecialchars($row['namabarang']) ?></td>
                        <td><?= htmlspecialchars($row['jumlah']) ?></td>
                        <td><?= date('d-m-Y', strtotime($row['tanggal'])) ?></td>
                        <td><?= htmlspecialchars($row['pengambil']) ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <!-- Menampilkan pesan jika tidak ada data -->
                <tr>
                    <td colspan="6">Tidak ada data yang ditemukan.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Footer dengan informasi nomor halaman dan aplikasi -->
    <footer>
        <span class="pagenum"></span> | Aplikasi Pengambilan Barang - By: Asriadi Kreatif
    </footer>
</body>

</html>

<?php
// Menutup statement SQL dan koneksi database
$stmt->close();
$db->close();
?>