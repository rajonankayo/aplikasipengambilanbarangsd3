<?php
session_start();

if (!isset($_SESSION['is_login'])) {
    header("Location: login.php");
    exit;
}

include 'service/database.php';

// Validasi input
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $unitName = trim($_POST['unit_name']);
    $logoFile = $_FILES['logo'];

    if (!empty($unitName) && $logoFile['error'] === 0) {
        // Validasi ekstensi file
        $allowedExtensions = ['jpg', 'jpeg', 'png'];
        $fileExtension = strtolower(pathinfo($logoFile['name'], PATHINFO_EXTENSION));

        if (in_array($fileExtension, $allowedExtensions)) {
            // Pastikan folder uploads tersedia
            $uploadDir = 'uploads/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // Simpan file logo
            $newFileName = 'logo_' . time() . '.' . $fileExtension;
            $destination = $uploadDir . $newFileName;

            if (move_uploaded_file($logoFile['tmp_name'], $destination)) {
                // Cek apakah sudah ada data pengaturan sebelumnya
                $check = $db->query("SELECT id FROM pengaturan LIMIT 1");

                if ($check->num_rows > 0) {
                    // Update jika sudah ada
                    $db->query("UPDATE pengaturan SET unit_name = '$unitName', logo_path = '$destination' WHERE id = 1");
                } else {
                    // Insert jika belum ada
                    $db->query("INSERT INTO pengaturan (unit_name, logo_path) VALUES ('$unitName', '$destination')");
                }

                // Redirect dengan pesan sukses
                header("Location: settings.php?status=sukses");
                exit;
            } else {
                die("❌ Gagal mengupload file.");
            }
        } else {
            die("❌ Format file tidak didukung. Gunakan JPG, JPEG, atau PNG.");
        }
    } else {
        die("❌ Semua field wajib diisi.");
    }
} else {
    header("Location: settings.php");
    exit;
}