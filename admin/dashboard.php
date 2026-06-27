<?php
require_once 'cek_login.php';
require_once '../config/koneksi.php';

// Ambil statistik ringkas dari database
$total_anggota = $koneksi->query("SELECT COUNT(*) AS jml FROM anggota")->fetch_assoc()['jml'];
$total_buku = $koneksi->query("SELECT COALESCE(SUM(stok),0) AS jml FROM buku")->fetch_assoc()['jml'];
$total_judul = $koneksi->query("SELECT COUNT(*) AS jml FROM buku")->fetch_assoc()['jml'];

// Sedang dipinjam = jumlah peminjaman yang belum ada pengembaliannya
$sedang_dipinjam = $koneksi->query("
    SELECT COUNT(*) AS jml FROM peminjaman p
    WHERE NOT EXISTS (SELECT 1 FROM pengembalian pk WHERE pk.id_peminjaman = p.id_peminjaman)
")->fetch_assoc()['jml'];

// Denda belum dibayar
$denda_belum_bayar = $koneksi->query("
    SELECT COALESCE(SUM(jumlah_denda),0) AS total, COUNT(*) AS jml
    FROM denda WHERE status_bayar = 'belum bayar'
")->fetch_assoc();

// 5 peminjaman terbaru
$peminjaman_terbaru = $koneksi->query("
    SELECT pm.id_peminjaman, a.nama_anggota, pm.tanggal_pinjam,
           (SELECT COUNT(*) FROM detail_peminjaman dp WHERE dp.id_peminjaman = pm.id_peminjaman) AS jml_buku,
           EXISTS(SELECT 1 FROM pengembalian pk WHERE pk.id_peminjaman = pm.id_peminjaman) AS sudah_kembali
    FROM peminjaman pm
    JOIN anggota a ON pm.id_anggota = a.id_anggota
    ORDER BY pm.id_peminjaman DESC
    LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard - SIM Perpustakaan</title>
<?php include 'partials/style.php'; ?>
</head>
<body>

<div class="layout">
  <?php include 'partials/sidebar.php'; ?>

  <main class="main-content">
    <div class="topbar">
      <h1>Dashboard</h1>
      <div class="topbar-user">👤 <?= htmlspecialchars($_SESSION['nama_petugas']) ?></div>
    </div>

    <div class="content">
      <div class="metrics">
        <div class="metric">
          <div class="metric-label">👥 Total Anggota</div>
          <div class="metric-value"><?= $total_anggota ?></div>
        </div>
        <div class="metric">
          <div class="metric-label">📚 Judul Buku</div>
          <div class="metric-value"><?= $total_judul ?></div>
          <div class="metric-sub"><?= $total_buku ?> total buku</div>
        </div>
        <div class="metric">
          <div class="metric-label">🔄 Sedang Dipinjam</div>
          <div class="metric-value"><?= $sedang_dipinjam ?></div>
        </div>
        <div class="metric">
          <div class="metric-label">⚠️ Denda Belum Bayar</div>
          <div class="metric-value" style="color:#A32D2D">Rp <?= number_format($denda_belum_bayar['total'], 0, ',', '.') ?></div>
          <div class="metric-sub"><?= $denda_belum_bayar['jml'] ?> transaksi</div>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <span class="card-title">Peminjaman Terbaru</span>
          <a href="peminjaman.php" class="btn btn-sm">Lihat semua</a>
        </div>
        <table>
          <thead>
            <tr><th>ID</th><th>Anggota</th><th>Tgl Pinjam</th><th>Jumlah Buku</th><th>Status</th></tr>
          </thead>
          <tbody>
            <?php if ($peminjaman_terbaru->num_rows > 0): ?>
              <?php while ($row = $peminjaman_terbaru->fetch_assoc()): ?>
                <tr>
                  <td>P<?= str_pad($row['id_peminjaman'], 3, '0', STR_PAD_LEFT) ?></td>
                  <td><?= htmlspecialchars($row['nama_anggota']) ?></td>
                  <td><?= date('d M Y', strtotime($row['tanggal_pinjam'])) ?></td>
                  <td><?= $row['jml_buku'] ?> buku</td>
                  <td>
                    <?php if ($row['sudah_kembali']): ?>
                      <span class="badge badge-success">Selesai</span>
                    <?php else: ?>
                      <span class="badge badge-warning">Dipinjam</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr><td colspan="5" class="empty-row">Belum ada transaksi peminjaman.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</div>

</body>
</html>
<?php $koneksi->close(); ?>