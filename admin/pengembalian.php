<?php
require_once 'cek_login.php';
require_once '../config/koneksi.php';

$pesan = "";
$tipe_pesan = "";

const BATAS_HARI = 7;       // tenggang waktu peminjaman (hari)
const DENDA_PER_HARI = 1000; // rupiah per hari telat

// ===== PROSES PENGEMBALIAN =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['proses_kembali'])) {
    $id_peminjaman = (int)$_POST['id_peminjaman'];
    $tanggal_kembali = $_POST['tanggal_kembali'];
    $id_petugas = $_SESSION['id_petugas'];

    // Ambil tanggal pinjam untuk hitung selisih hari
    $cek = $koneksi->prepare("SELECT tanggal_pinjam FROM peminjaman WHERE id_peminjaman = ?");
    $cek->bind_param("i", $id_peminjaman);
    $cek->execute();
    $row = $cek->get_result()->fetch_assoc();
    $cek->close();

    if (!$row) {
        $pesan = "Data peminjaman tidak ditemukan.";
        $tipe_pesan = "danger";
    } else {
        $tgl_pinjam = new DateTime($row['tanggal_pinjam']);
        $tgl_kembali = new DateTime($tanggal_kembali);
        $selisih_hari = (int)$tgl_pinjam->diff($tgl_kembali)->days;
        $hari_terlambat = max(0, $selisih_hari - BATAS_HARI);

        $koneksi->begin_transaction();
        try {
            // 1. Catat pengembalian
            $stmt = $koneksi->prepare("INSERT INTO pengembalian (id_peminjaman, id_petugas, tanggal_kembali) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $id_peminjaman, $id_petugas, $tanggal_kembali);
            $stmt->execute();
            $id_pengembalian_baru = $koneksi->insert_id;
            $stmt->close();

            // 2. Kembalikan stok buku sesuai detail peminjaman
            $stmtStok = $koneksi->prepare("
                UPDATE buku b
                JOIN detail_peminjaman dp ON b.kode_buku = dp.kode_buku
                SET b.stok = b.stok + dp.jumlah
                WHERE dp.id_peminjaman = ?
            ");
            $stmtStok->bind_param("i", $id_peminjaman);
            $stmtStok->execute();
            $stmtStok->close();

            // 3. Jika telat, otomatis buat catatan denda
            if ($hari_terlambat > 0) {
                $jumlah_denda = $hari_terlambat * DENDA_PER_HARI;
                $stmtDenda = $koneksi->prepare("INSERT INTO denda (id_pengembalian, hari_terlambat, jumlah_denda, status_bayar) VALUES (?, ?, ?, 'belum bayar')");
                $stmtDenda->bind_param("iid", $id_pengembalian_baru, $hari_terlambat, $jumlah_denda);
                $stmtDenda->execute();
                $stmtDenda->close();
            }

            $koneksi->commit();

            if ($hari_terlambat > 0) {
                $pesan = "Pengembalian dicatat. Terlambat $hari_terlambat hari, denda Rp" . number_format($hari_terlambat * DENDA_PER_HARI, 0, ',', '.') . " otomatis dibuat.";
                $tipe_pesan = "warning";
            } else {
                $pesan = "Pengembalian berhasil dicatat. Tidak ada keterlambatan.";
                $tipe_pesan = "success";
            }
        } catch (Exception $e) {
            $koneksi->rollback();
            $pesan = "Gagal mencatat pengembalian: " . $e->getMessage();
            $tipe_pesan = "danger";
        }
    }
}

// ===== DAFTAR PEMINJAMAN YANG BELUM DIKEMBALIKAN (untuk dropdown form) =====
$belum_kembali = $koneksi->query("
    SELECT pm.id_peminjaman, a.nama_anggota, pm.tanggal_pinjam,
           GROUP_CONCAT(b.judul_buku SEPARATOR ', ') AS daftar_buku
    FROM peminjaman pm
    JOIN anggota a ON pm.id_anggota = a.id_anggota
    LEFT JOIN detail_peminjaman dp ON pm.id_peminjaman = dp.id_peminjaman
    LEFT JOIN buku b ON dp.kode_buku = b.kode_buku
    WHERE NOT EXISTS (SELECT 1 FROM pengembalian pk WHERE pk.id_peminjaman = pm.id_peminjaman)
    GROUP BY pm.id_peminjaman
    ORDER BY pm.tanggal_pinjam ASC
");

// ===== RIWAYAT PENGEMBALIAN =====
$riwayat = $koneksi->query("
    SELECT pk.id_pengembalian, pm.id_peminjaman, a.nama_anggota, pm.tanggal_pinjam, pk.tanggal_kembali,
           pt.nama_petugas,
           d.hari_terlambat, d.jumlah_denda
    FROM pengembalian pk
    JOIN peminjaman pm ON pk.id_peminjaman = pm.id_peminjaman
    JOIN anggota a ON pm.id_anggota = a.id_anggota
    JOIN petugas pt ON pk.id_petugas = pt.id_petugas
    LEFT JOIN denda d ON d.id_pengembalian = pk.id_pengembalian
    ORDER BY pk.id_pengembalian DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pengembalian - SIM Perpustakaan</title>
<?php include 'partials/style.php'; ?>
</head>
<body>

<div class="layout">
  <?php include 'partials/sidebar.php'; ?>

  <main class="main-content">
    <div class="topbar">
      <h1>Pengembalian</h1>
      <div class="topbar-user">👤 <?= htmlspecialchars($_SESSION['nama_petugas']) ?></div>
    </div>

    <div class="content">
      <?php if ($pesan): ?>
        <div class="alert alert-<?= $tipe_pesan ?>"><?= htmlspecialchars($pesan) ?></div>
      <?php endif; ?>

      <div style="display:flex; gap:20px; align-items:flex-start; flex-wrap:wrap;">

        <!-- FORM PROSES PENGEMBALIAN -->
        <div class="form-card">
          <div class="card-title" style="margin-bottom:14px;">Proses Pengembalian</div>
          <p class="page-sub" style="margin-bottom:14px;">
            Batas pinjam <?= BATAS_HARI ?> hari. Lewat dari itu otomatis kena denda Rp<?= number_format(DENDA_PER_HARI, 0, ',', '.') ?>/hari.
          </p>

          <?php if ($belum_kembali->num_rows === 0): ?>
            <div class="alert alert-success">Tidak ada peminjaman yang masih berjalan saat ini.</div>
          <?php else: ?>
            <form method="POST">
              <div class="form-group">
                <label>Peminjaman</label>
                <select name="id_peminjaman" required>
                  <option value="">-- Pilih Transaksi Peminjaman --</option>
                  <?php while ($p = $belum_kembali->fetch_assoc()): ?>
                    <option value="<?= $p['id_peminjaman'] ?>">
                      P<?= str_pad($p['id_peminjaman'], 3, '0', STR_PAD_LEFT) ?> -
                      <?= htmlspecialchars($p['nama_anggota']) ?> -
                      <?= htmlspecialchars($p['daftar_buku'] ?? '-') ?>
                      (pinjam <?= date('d M Y', strtotime($p['tanggal_pinjam'])) ?>)
                    </option>
                  <?php endwhile; ?>
                </select>
              </div>
              <div class="form-group">
                <label>Tanggal Kembali</label>
                <input type="date" name="tanggal_kembali" required value="<?= date('Y-m-d') ?>">
              </div>
              <div class="form-actions">
                <button type="submit" name="proses_kembali" class="btn btn-primary">Proses Pengembalian</button>
              </div>
            </form>
          <?php endif; ?>
        </div>

        <!-- TABEL RIWAYAT PENGEMBALIAN -->
        <div class="card" style="flex:1; min-width:400px;">
          <div class="card-header">
            <span class="card-title">Riwayat Pengembalian</span>
          </div>
          <table>
            <thead>
              <tr><th>ID Pinjam</th><th>Anggota</th><th>Tgl Pinjam</th><th>Tgl Kembali</th><th>Telat</th><th>Denda</th></tr>
            </thead>
            <tbody>
              <?php if ($riwayat->num_rows > 0): ?>
                <?php while ($row = $riwayat->fetch_assoc()): ?>
                  <tr>
                    <td>P<?= str_pad($row['id_peminjaman'], 3, '0', STR_PAD_LEFT) ?></td>
                    <td><?= htmlspecialchars($row['nama_anggota']) ?></td>
                    <td><?= date('d M Y', strtotime($row['tanggal_pinjam'])) ?></td>
                    <td><?= date('d M Y', strtotime($row['tanggal_kembali'])) ?></td>
                    <td>
                      <?php if ($row['hari_terlambat'] > 0): ?>
                        <span class="badge badge-danger"><?= $row['hari_terlambat'] ?> hari</span>
                      <?php else: ?>
                        <span class="badge badge-success">Tepat waktu</span>
                      <?php endif; ?>
                    </td>
                    <td><?= $row['jumlah_denda'] > 0 ? 'Rp' . number_format($row['jumlah_denda'], 0, ',', '.') : '-' ?></td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr><td colspan="6" class="empty-row">Belum ada riwayat pengembalian.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </main>
</div>

</body>
</html>
<?php $koneksi->close(); ?>