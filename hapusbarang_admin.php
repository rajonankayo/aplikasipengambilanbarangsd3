<?php
// Sertakan file koneksi database
include "service/database.php";

// Mulai session
session_start();

// ==========================
// Cek Autentikasi Login
// ==========================
// Jika user belum login, redirect ke halaman login
if (!isset($_SESSION['is_login'])) {
    header("location: login.php");
    exit;
}

// ==========================
// Proses Penghapusan Barang
// ==========================
// Cek apakah parameter kodebarang dikirim melalui URL (GET)
if (isset($_GET['kodebarang'])) {
    $kodebarang = $_GET['kodebarang']; // Ambil nilai kodebarang dari URL

    // Siapkan query untuk menghapus data barang berdasarkan kodebarang
    $stmt = $db->prepare("DELETE FROM daftarbarang WHERE kodebarang = ?");
    $stmt->bind_param("s", $kodebarang); // Bind parameter ke query
    $stmt->execute();                    // Eksekusi query DELETE
    $stmt->close();                      // Tutup statement
}

// ==========================
// Redirect setelah proses
// ==========================
// Setelah proses penghapusan selesai, arahkan kembali ke halaman daftar barang
header("location: daftarbarang_admin.php");
exit;

?>