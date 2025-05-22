<?php
session_start();
include 'service/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $unit_name = $_POST['unit_name'];

    // Proses upload logo
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $logoTmpPath = $_FILES['logo']['tmp_name'];
        $logoName = basename($_FILES['logo']['name']);
        $logoDestination = 'uploads/logo/' . $logoName;

        // Pastikan folder uploads/logo/ ada
        if (!is_dir('uploads/logo')) {
            mkdir('uploads/logo', 0777, true);
        }

        if (move_uploaded_file($logoTmpPath, $logoDestination)) {
            // Simpan ke database atau file config, contoh simpan ke tabel `pengaturan`
            $stmt = $db->prepare("REPLACE INTO pengaturan (id, unit_name, logo_path) VALUES (1, ?, ?)");
            $stmt->bind_param("ss", $unit_name, $logoDestination);
            $stmt->execute();
            $stmt->close();
            header("Location: dashboard_admin.php?status=sukses");
            exit;
        } else {
            echo "Gagal mengunggah logo.";
        }
    } else {
        echo "File logo tidak ditemukan atau terjadi kesalahan upload.";
    }
} else {
    header("Location: dashboard_admin.php");
    exit;
}
