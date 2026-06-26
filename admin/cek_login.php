<?php
// File ini di-include di paling atas setiap halaman di folder admin/
// Fungsinya: pastikan hanya petugas yang sudah login yang bisa akses halaman ini.

session_start();

if (!isset($_SESSION['id_petugas'])) {
    header("Location: ../login.php");
    exit;
}
?>