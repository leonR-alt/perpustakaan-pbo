<?php
// ============================================
// FILE KONEKSI DATABASE
// Laragon default: host=localhost, user=root, password=(kosong)
// ============================================

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "db_perpustakaan";

$koneksi = new mysqli($host, $user, $pass, $dbname);

if ($koneksi->connect_error) {
    die("Koneksi database gagal: " . $koneksi->connect_error);
}

$koneksi->set_charset("utf8mb4");
?>