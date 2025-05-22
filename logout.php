<?php
// Memulai session untuk dapat mengakses data session yang sudah ada
session_start();

// Menghapus semua data yang ada dalam session (variabel session)
session_unset();

// Menghancurkan session yang sedang aktif, sehingga pengguna keluar dari sesi tersebut
session_destroy();

// Mengarahkan pengguna ke halaman 'index.php' setelah logout
header("Location: index.php");

// Menghentikan eksekusi lebih lanjut pada skrip setelah redirect
exit;
?>
