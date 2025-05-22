<?php
// Sertakan file koneksi database
include "service/database.php";

// Mulai session
session_start();

// ==========================
// Cek Autentikasi Login
// ==========================
// Jika pengguna belum login, arahkan ke halaman login
if (!isset($_SESSION['is_login'])) {
    header("Location: login.php");
    exit;
}

// ==========================
// Proses Update User
// ==========================
// Mengecek apakah form dikirim dengan metode POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Ambil data dari form
    $username_lama = $_POST['username_lama']; // username sebelum diubah
    $username_baru = $_POST['username'];      // username baru
    $namalengkap = $_POST['namalengkap'];     // nama lengkap baru
    $password = $_POST['password'];           // password baru (jika diisi)

    // Cek apakah field password diisi
    if (!empty($password)) {
        // Jika password diisi, hash password menggunakan SHA-256
        $hashed_password = hash('sha256', $password);

        // Siapkan query untuk update username, nama lengkap, dan password
        $stmt = $db->prepare("UPDATE users SET username=?, namalengkap=?, password=? WHERE username=?");
        $stmt->bind_param("ssss", $username_baru, $namalengkap, $hashed_password, $username_lama);
    } else {
        // Jika password tidak diisi, hanya update username dan nama lengkap
        $stmt = $db->prepare("UPDATE users SET username=?, namalengkap=? WHERE username=?");
        $stmt->bind_param("sss", $username_baru, $namalengkap, $username_lama);
    }

    // Eksekusi query update ke database
    if ($stmt->execute()) {
        // Jika berhasil, arahkan kembali ke halaman kelola user dengan notifikasi sukses
        header("Location: kelolauser_admin.php?update=success");
    } else {
        // Jika gagal update
        echo "Gagal mengupdate data.";
    }

    // Tutup prepared statement
    $stmt->close();
}
?>
