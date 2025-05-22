<?php
// Menyertakan file koneksi database
include "service/database.php";

// Memulai session
session_start();

// ============================
// Validasi Login
// ============================
// Jika user belum login, arahkan ke halaman login
if (!isset($_SESSION['is_login'])) {
    header("location: login.php");
    exit;
}

// ============================
// Proses Hapus User
// ============================
// Mengecek apakah parameter `username` dikirim melalui metode GET
if (isset($_GET['username'])) {
    $username = $_GET['username']; // Ambil nilai username dari parameter URL

    // Mencegah SQL Injection dengan prepared statement
    $stmt = $db->prepare("DELETE FROM users WHERE username = ?");
    $stmt->bind_param("s", $username); // Bind parameter ke query SQL
    $stmt->execute(); // Jalankan query untuk menghapus user

    // ============================
    // Cek Hasil Eksekusi
    // ============================
    if ($stmt->affected_rows > 0) {
        // Jika baris terpengaruh > 0, maka data berhasil dihapus
        header("Location: kelolauser_admin.php?hapus=success");
    } else {
        // Jika tidak ada baris yang terhapus, bisa jadi username tidak ditemukan
        header("Location: kelolauser_admin.php?hapus=failed");
    }

    // Tutup prepared statement
    $stmt->close();
} else {
    // Jika tidak ada parameter username, arahkan kembali ke halaman user
    header("Location: kelolauser_admin.php");
}
?>
