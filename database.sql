-- ============================================
-- DATABASE PERPUSTAKAAN SIM
-- Import file ini lewat phpMyAdmin / HeidiSQL di Laragon
-- Semua tabel kosong, kecuali tabel petugas (1 akun default untuk login)
-- ============================================

CREATE DATABASE IF NOT EXISTS db_perpustakaan;
USE db_perpustakaan;

-- ====================
-- 1. TABEL ANGGOTA
-- ====================
CREATE TABLE anggota (
    id_anggota INT AUTO_INCREMENT PRIMARY KEY,
    nama_anggota VARCHAR(100) NOT NULL,
    alamat VARCHAR(255),
    email VARCHAR(100) UNIQUE,
    tanggal_daftar DATE NOT NULL
);

-- ====================
-- 2. TABEL NOMOR TELEPON (1 anggota bisa banyak nomor)
-- ====================
CREATE TABLE nomor_telepon (
    id_telepon INT AUTO_INCREMENT PRIMARY KEY,
    id_anggota INT NOT NULL,
    nomor_telepon VARCHAR(20) NOT NULL,
    FOREIGN KEY (id_anggota) REFERENCES anggota(id_anggota) ON DELETE CASCADE
);

-- ====================
-- 3. TABEL PETUGAS (login)
-- ====================
CREATE TABLE petugas (
    id_petugas INT AUTO_INCREMENT PRIMARY KEY,
    nama_petugas VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL
);

-- ====================
-- 4. TABEL PENERBIT
-- ====================
CREATE TABLE penerbit (
    id_penerbit INT AUTO_INCREMENT PRIMARY KEY,
    nama_penerbit VARCHAR(100) NOT NULL,
    alamat VARCHAR(255),
    nib VARCHAR(50)
);

-- ====================
-- 5. TABEL BUKU (1 buku = 1 penerbit)
-- ====================
CREATE TABLE buku (
    kode_buku VARCHAR(20) PRIMARY KEY,
    isbn VARCHAR(30),
    judul_buku VARCHAR(150) NOT NULL,
    id_penerbit INT,
    tahun_terbit YEAR,
    stok INT DEFAULT 0,
    FOREIGN KEY (id_penerbit) REFERENCES penerbit(id_penerbit)
);

-- ====================
-- 6. TABEL PENGARANG
-- ====================
CREATE TABLE pengarang (
    id_pengarang INT AUTO_INCREMENT PRIMARY KEY,
    nama_pengarang VARCHAR(100) NOT NULL
);

-- ====================
-- 7. TABEL BUKU_PENGARANG (penghubung many-to-many: 1 buku bisa banyak pengarang)
-- ====================
CREATE TABLE buku_pengarang (
    id_buku_pengarang INT AUTO_INCREMENT PRIMARY KEY,
    kode_buku VARCHAR(20) NOT NULL,
    id_pengarang INT NOT NULL,
    FOREIGN KEY (kode_buku) REFERENCES buku(kode_buku) ON DELETE CASCADE,
    FOREIGN KEY (id_pengarang) REFERENCES pengarang(id_pengarang) ON DELETE CASCADE
);

-- ====================
-- 8. TABEL PEMINJAMAN (dicatat oleh petugas)
-- ====================
CREATE TABLE peminjaman (
    id_peminjaman INT AUTO_INCREMENT PRIMARY KEY,
    id_anggota INT NOT NULL,
    id_petugas INT NOT NULL,
    tanggal_pinjam DATE NOT NULL,
    FOREIGN KEY (id_anggota) REFERENCES anggota(id_anggota),
    FOREIGN KEY (id_petugas) REFERENCES petugas(id_petugas)
);

-- ====================
-- 9. TABEL DETAIL_PEMINJAMAN (1 peminjaman bisa banyak buku)
-- ====================
CREATE TABLE detail_peminjaman (
    id_detail_pinjam INT AUTO_INCREMENT PRIMARY KEY,
    id_peminjaman INT NOT NULL,
    kode_buku VARCHAR(20) NOT NULL,
    jumlah INT DEFAULT 1,
    FOREIGN KEY (id_peminjaman) REFERENCES peminjaman(id_peminjaman) ON DELETE CASCADE,
    FOREIGN KEY (kode_buku) REFERENCES buku(kode_buku)
);

-- ====================
-- 10. TABEL PENGEMBALIAN
-- ====================
CREATE TABLE pengembalian (
    id_pengembalian INT AUTO_INCREMENT PRIMARY KEY,
    id_peminjaman INT NOT NULL,
    id_petugas INT NOT NULL,
    tanggal_kembali DATE NOT NULL,
    FOREIGN KEY (id_peminjaman) REFERENCES peminjaman(id_peminjaman),
    FOREIGN KEY (id_petugas) REFERENCES petugas(id_petugas)
);

-- ====================
-- 11. TABEL DENDA (hanya muncul jika telat)
-- ====================
CREATE TABLE denda (
    id_denda INT AUTO_INCREMENT PRIMARY KEY,
    id_pengembalian INT NOT NULL,
    hari_terlambat INT DEFAULT 0,
    jumlah_denda DECIMAL(10,2) DEFAULT 0,
    status_bayar VARCHAR(20) DEFAULT 'belum bayar',
    FOREIGN KEY (id_pengembalian) REFERENCES pengembalian(id_pengembalian)
);

-- ============================================
-- DATA AWAL: hanya 1 akun petugas untuk login pertama kali
-- Password masih plain text "admin123" untuk kemudahan tugas kuliah.
-- (Di dunia nyata, password wajib di-hash, lihat catatan di login.php)
-- ============================================
INSERT INTO petugas (nama_petugas, email, password) VALUES
('Admin Utama', 'admin@perpustakaan.com', 'admin123');