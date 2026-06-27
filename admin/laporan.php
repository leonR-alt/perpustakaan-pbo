<?php
require_once 'cek_login.php';
require_once '../config/koneksi.php';

// ===== AMBIL RENTANG TANGGAL DARI FORM, DEFAULT: 30 HARI TERAKHIR =====
$tanggal_mulai = $_GET['mulai'] ?? date('Y-m-d', strtotime('-30 days'));
$tanggal_selesai = $_GET['selesai'] ?? date('Y-m-d');

// ===== RINGKASAN PEMINJAMAN DALAM RENTANG =====
$stmt1 = $koneksi->prepare("
    SELECT COUNT(*) AS jml_transaksi, COALESCE(SUM(dp.jumlah), 0) AS jml_eksemplar
    FROM peminjaman pm
    LEFT JOIN detail_peminjaman dp ON pm.id_peminjaman = dp.id_peminjaman
    WHERE pm.tanggal_pinjam BETWEEN ? AND ?
");
$stmt1->bind_param("ss", $tanggal_mulai, $tanggal_selesai);
$stmt1->execute();
$ringkasan_pinjam = $stmt1->get_result()->fetch_assoc();
$stmt1->close();

// ===== RINGKASAN PENGEMBALIAN DALAM RENTANG =====
$stmt2 = $koneksi->prepare("
    SELECT COUNT(*) AS jml_transaksi,
           COALESCE(SUM(d.jumlah_denda), 0) AS total_denda,
           SUM(CASE WHEN d.id_denda IS NOT NULL THEN 1 ELSE 0 END) AS jml_telat
    FROM pengembalian pk
    LEFT JOIN denda d ON d.id_pengembalian = pk.id_pengembalian
    WHERE pk.tanggal_kembali BETWEEN ? AND ?
");
$stmt2->bind_param("ss", $tanggal_mulai, $tanggal_selesai);
$stmt2->execute();
$ringkasan_kembali = $stmt2->get_result()->fetch_assoc();
$stmt2->close();

// ===== DETAIL TRANSAKSI PEMINJAMAN DALAM RENTANG =====
$stmt3 = $koneksi->prepare("
    SELECT pm.id_peminjaman, a.nama_anggota, pm.tanggal_pinjam,
           GROUP_CONCAT(b.judul_buku SEPARATOR ', ') AS daftar_buku,
           EXISTS(SELECT 1 FROM pengembalian pk WHERE pk.id_peminjaman = pm.id_peminjaman) AS sudah_kembali
    FROM peminjaman pm
    JOIN anggota a ON pm.id_anggota = a.id_anggota
    LEFT JOIN detail_peminjaman dp ON pm.id_peminjaman = dp.id_peminjaman
    LEFT JOIN buku b ON dp.kode_buku = b.kode_buku
    WHERE pm.tanggal_pinjam BETWEEN ? AND ?
    GROUP BY pm.id_peminjaman
    ORDER BY pm.tanggal_pinjam DESC
");
$stmt3->bind_param("ss", $tanggal_mulai, $tanggal_selesai);
$stmt3->execute();
$detail_pinjam = $stmt3->get_result();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Laporan - SIM Perpustakaan</title>
<?php include 'partials/style.php'; ?>
</head>
<body>

<div class="layout">
  <?php include 'partials/sidebar.php'; ?>

  <main class="main-content">
    <div class="topbar">
      <h1>Laporan</h1>
      <div class="topbar-user">👤 <?= htmlspecialchars($_SESSION['nama_petugas']) ?></div>
    </div>

    <div class="content">

      <!-- FILTER RENTANG TANGGAL -->
      <div class="card" style="margin-bottom:20px;">
        <form method="GET" style="display:flex; gap:12px; align-items:flex-end; flex-wrap:wrap; padding:16px;">
          <div class="form-group" style="margin-bottom:0;">
            <label>Dari Tanggal</label>
            <input type="date" name="mulai" value="<?= htmlspecialchars($tanggal_mulai) ?>">
          </div>
          <div class="form-group" style="margin-bottom:0;">
            <label>Sampai Tanggal</label>
            <input type="date" name="selesai" value="<?= htmlspecialchars($tanggal_selesai) ?>">
          </div>
          <button type="submit" class="btn btn-primary">Tampilkan Laporan</button>
        </form>
      </div>

      <!-- RINGKASAN -->
      <div class="metrics">
        <div class="metric">
          <div class="metric-label">📥 Transaksi Peminjaman</div>
          <div class="metric-value"><?= (int)$ringkasan_pinjam['jml_transaksi'] ?></div>
          <div class="metric-sub"><?= (int)$ringkasan_pinjam['jml_eksemplar'] ?> eksemplar dipinjam</div>
        </div>
        <div class="metric">
          <div class="metric-label">📤 Transaksi Pengembalian</div>
          <div class="metric-value"><?= (int)$ringkasan_kembali['jml_transaksi'] ?></div>
        </div>
        <div class="metric">
          <div class="metric-label">⏰ Pengembalian Telat</div>
          <div class="metric-value"><?= (int)$ringkasan_kembali['jml_telat'] ?></div>
        </div>
        <div class="metric">
          <div class="metric-label">💰 Total Denda Periode Ini</div>
          <div class="metric-value" style="color:#A32D2D">Rp <?= number_format($ringkasan_kembali['total_denda'] ?? 0, 0, ',', '.') ?></div>
        </div>
      </div>

      <p class="page-sub" style="margin: 0 0 14px;">
        Menampilkan data dari <strong><?= date('d M Y', strtotime($tanggal_mulai)) ?></strong>
        sampai <strong><?= date('d M Y', strtotime($tanggal_selesai)) ?></strong>.
      </p>

      <!-- DETAIL TRANSAKSI PEMINJAMAN -->
      <div class="card">
        <div class="card-header">
          <span class="card-title">Detail Transaksi Peminjaman</span>
        </div>
        <table>
          <thead>
            <tr><th>ID</th><th>Anggota</th><th>Buku</th><th>Tgl Pinjam</th><th>Status</th></tr>
          </thead>
          <tbody>
            <?php if ($detail_pinjam->num_rows > 0): ?>
              <?php while ($row = $detail_pinjam->fetch_assoc()): ?>
                <tr>
                  <td>P<?= str_pad($row['id_peminjaman'], 3, '0', STR_PAD_LEFT) ?></td>
                  <td><?= htmlspecialchars($row['nama_anggota']) ?></td>
                  <td><?= htmlspecialchars($row['daftar_buku'] ?? '-') ?></td>
                  <td><?= date('d M Y', strtotime($row['tanggal_pinjam'])) ?></td>
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
              <tr><td colspan="5" class="empty-row">Tidak ada transaksi peminjaman pada rentang tanggal ini.</td></tr>
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