<?php
require_once 'cek_login.php';
require_once '../config/koneksi.php';

$pesan = "";
$tipe_pesan = "";

// ===== TANDAI DENDA SUDAH DIBAYAR =====
if (isset($_GET['bayar'])) {
    $id_denda = (int)$_GET['bayar'];
    $stmt = $koneksi->prepare("UPDATE denda SET status_bayar = 'sudah bayar' WHERE id_denda = ?");
    $stmt->bind_param("i", $id_denda);
    if ($stmt->execute()) {
        $pesan = "Denda berhasil ditandai sebagai sudah dibayar.";
        $tipe_pesan = "success";
    } else {
        $pesan = "Gagal memperbarui status denda.";
        $tipe_pesan = "danger";
    }
    $stmt->close();
}

// ===== FILTER STATUS (opsional, lewat query string) =====
$filter = $_GET['filter'] ?? 'semua';
$where = "";
if ($filter === 'belum') {
    $where = "WHERE d.status_bayar = 'belum bayar'";
} elseif ($filter === 'sudah') {
    $where = "WHERE d.status_bayar = 'sudah bayar'";
}

// ===== AMBIL DATA DENDA =====
$result = $koneksi->query("
    SELECT d.id_denda, d.hari_terlambat, d.jumlah_denda, d.status_bayar,
           a.nama_anggota, pm.id_peminjaman, pk.tanggal_kembali
    FROM denda d
    JOIN pengembalian pk ON d.id_pengembalian = pk.id_pengembalian
    JOIN peminjaman pm ON pk.id_peminjaman = pm.id_peminjaman
    JOIN anggota a ON pm.id_anggota = a.id_anggota
    $where
    ORDER BY d.id_denda DESC
");

// ===== RINGKASAN TOTAL =====
$ringkasan = $koneksi->query("
    SELECT
        COALESCE(SUM(CASE WHEN status_bayar = 'belum bayar' THEN jumlah_denda ELSE 0 END), 0) AS total_belum,
        COALESCE(SUM(CASE WHEN status_bayar = 'sudah bayar' THEN jumlah_denda ELSE 0 END), 0) AS total_sudah,
        COUNT(CASE WHEN status_bayar = 'belum bayar' THEN 1 END) AS jml_belum
    FROM denda
")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Denda - SIM Perpustakaan</title>
<?php include 'partials/style.php'; ?>
</head>
<body>

<div class="layout">
  <?php include 'partials/sidebar.php'; ?>

  <main class="main-content">
    <div class="topbar">
      <h1>Denda</h1>
      <div class="topbar-user">👤 <?= htmlspecialchars($_SESSION['nama_petugas']) ?></div>
    </div>

    <div class="content">
      <?php if ($pesan): ?>
        <div class="alert alert-<?= $tipe_pesan ?>"><?= htmlspecialchars($pesan) ?></div>
      <?php endif; ?>

      <p class="page-sub" style="margin-bottom:16px;">
        Denda dibuat otomatis dari halaman Pengembalian saat ada keterlambatan. Halaman ini hanya untuk memantau dan menandai status pembayaran.
      </p>

      <!-- RINGKASAN -->
      <div class="metrics" style="margin-bottom:20px;">
        <div class="metric">
          <div class="metric-label">💸 Belum Dibayar</div>
          <div class="metric-value" style="color:#A32D2D;">Rp<?= number_format($ringkasan['total_belum'], 0, ',', '.') ?></div>
          <div class="metric-sub"><?= $ringkasan['jml_belum'] ?> transaksi</div>
        </div>
        <div class="metric">
          <div class="metric-label">✅ Sudah Dibayar</div>
          <div class="metric-value" style="color:#2E7D32;">Rp<?= number_format($ringkasan['total_sudah'], 0, ',', '.') ?></div>
        </div>
      </div>

      <!-- FILTER -->
      <div style="margin-bottom:14px; display:flex; gap:8px;">
        <a href="?filter=semua" class="btn btn-sm <?= $filter === 'semua' ? 'btn-primary' : '' ?>">Semua</a>
        <a href="?filter=belum" class="btn btn-sm <?= $filter === 'belum' ? 'btn-primary' : '' ?>">Belum Bayar</a>
        <a href="?filter=sudah" class="btn btn-sm <?= $filter === 'sudah' ? 'btn-primary' : '' ?>">Sudah Bayar</a>
      </div>

      <!-- TABEL DENDA -->
      <div class="card">
        <div class="card-header">
          <span class="card-title">Daftar Denda</span>
        </div>
        <table>
          <thead>
            <tr><th>ID Pinjam</th><th>Anggota</th><th>Tgl Kembali</th><th>Hari Telat</th><th>Jumlah Denda</th><th>Status</th><th>Aksi</th></tr>
          </thead>
          <tbody>
            <?php if ($result->num_rows > 0): ?>
              <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                  <td>P<?= str_pad($row['id_peminjaman'], 3, '0', STR_PAD_LEFT) ?></td>
                  <td><?= htmlspecialchars($row['nama_anggota']) ?></td>
                  <td><?= date('d M Y', strtotime($row['tanggal_kembali'])) ?></td>
                  <td><?= $row['hari_terlambat'] ?> hari</td>
                  <td>Rp<?= number_format($row['jumlah_denda'], 0, ',', '.') ?></td>
                  <td>
                    <?php if ($row['status_bayar'] === 'sudah bayar'): ?>
                      <span class="badge badge-success">Sudah Bayar</span>
                    <?php else: ?>
                      <span class="badge badge-danger">Belum Bayar</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if ($row['status_bayar'] === 'belum bayar'): ?>
                      <a href="?bayar=<?= $row['id_denda'] ?>&filter=<?= $filter ?>" class="btn btn-sm btn-primary"
                         onclick="return confirm('Tandai denda ini sudah dibayar?')">Tandai Lunas</a>
                    <?php else: ?>
                      -
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr><td colspan="7" class="empty-row">Tidak ada data denda.</td></tr>
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