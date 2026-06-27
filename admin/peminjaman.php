<?php
require_once 'cek_login.php';
require_once '../config/koneksi.php';

$pesan = "";
$tipe_pesan = "";

// ===== TAMBAH PEMINJAMAN BARU =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_peminjaman'])) {
    $id_anggota = (int)$_POST['id_anggota'];
    $tanggal_pinjam = $_POST['tanggal_pinjam'];
    $kode_buku_list = $_POST['kode_buku'];   // array
    $jumlah_list = $_POST['jumlah'];         // array, sejajar index dengan kode_buku_list
    $id_petugas = $_SESSION['id_petugas'];

    // Gabungkan kode buku yang sama jika dipilih berulang, supaya validasi stok benar
    $rincian = [];
    foreach ($kode_buku_list as $i => $kode) {
        $jml = (int)($jumlah_list[$i] ?? 0);
        if ($kode !== "" && $jml > 0) {
            $rincian[$kode] = ($rincian[$kode] ?? 0) + $jml;
        }
    }

    if (empty($rincian)) {
        $pesan = "Pilih minimal satu buku dengan jumlah valid.";
        $tipe_pesan = "danger";
    } else {
        // Validasi stok cukup untuk semua buku yang dipilih
        $stok_cukup = true;
        $pesan_stok = "";
        foreach ($rincian as $kode => $jml) {
            $cek = $koneksi->prepare("SELECT judul_buku, stok FROM buku WHERE kode_buku = ?");
            $cek->bind_param("s", $kode);
            $cek->execute();
            $bukuRow = $cek->get_result()->fetch_assoc();
            $cek->close();
            if (!$bukuRow || $bukuRow['stok'] < $jml) {
                $stok_cukup = false;
                $pesan_stok = "Stok buku \"" . ($bukuRow['judul_buku'] ?? $kode) . "\" tidak cukup.";
                break;
            }
        }

        if (!$stok_cukup) {
            $pesan = $pesan_stok;
            $tipe_pesan = "danger";
        } else {
            // Gunakan transaksi: insert peminjaman + detail + kurangi stok harus berhasil semua atau dibatalkan semua
            $koneksi->begin_transaction();
            try {
                $stmt = $koneksi->prepare("INSERT INTO peminjaman (id_anggota, id_petugas, tanggal_pinjam) VALUES (?, ?, ?)");
                $stmt->bind_param("iis", $id_anggota, $id_petugas, $tanggal_pinjam);
                $stmt->execute();
                $id_peminjaman_baru = $koneksi->insert_id;
                $stmt->close();

                $stmtDetail = $koneksi->prepare("INSERT INTO detail_peminjaman (id_peminjaman, kode_buku, jumlah) VALUES (?, ?, ?)");
                $stmtStok = $koneksi->prepare("UPDATE buku SET stok = stok - ? WHERE kode_buku = ?");

                foreach ($rincian as $kode => $jml) {
                    $stmtDetail->bind_param("isi", $id_peminjaman_baru, $kode, $jml);
                    $stmtDetail->execute();

                    $stmtStok->bind_param("is", $jml, $kode);
                    $stmtStok->execute();
                }
                $stmtDetail->close();
                $stmtStok->close();

                $koneksi->commit();
                $pesan = "Peminjaman berhasil dicatat.";
                $tipe_pesan = "success";
            } catch (Exception $e) {
                $koneksi->rollback();
                $pesan = "Gagal mencatat peminjaman: " . $e->getMessage();
                $tipe_pesan = "danger";
            }
        }
    }
}

// ===== DATA UNTUK DROPDOWN =====
$daftar_anggota = $koneksi->query("SELECT id_anggota, nama_anggota FROM anggota ORDER BY nama_anggota ASC");
$daftar_buku = $koneksi->query("SELECT kode_buku, judul_buku, stok FROM buku WHERE stok > 0 ORDER BY judul_buku ASC");

const BATAS_HARI_PINJAM = 7; // sama dengan aturan di pengembalian.php

// ===== AMBIL DATA PEMINJAMAN UNTUK DITAMPILKAN =====
// hari_dipinjam = sudah berapa hari sejak tanggal_pinjam sampai HARI INI (real-time, bukan tanggal_kembali)
$result = $koneksi->query("
    SELECT pm.id_peminjaman, a.nama_anggota, pt.nama_petugas, pm.tanggal_pinjam,
           DATEDIFF(CURDATE(), pm.tanggal_pinjam) AS hari_dipinjam,
           GROUP_CONCAT(CONCAT(b.judul_buku, ' (', dp.jumlah, ')') SEPARATOR ', ') AS daftar_buku,
           EXISTS(SELECT 1 FROM pengembalian pk WHERE pk.id_peminjaman = pm.id_peminjaman) AS sudah_kembali
    FROM peminjaman pm
    JOIN anggota a ON pm.id_anggota = a.id_anggota
    JOIN petugas pt ON pm.id_petugas = pt.id_petugas
    LEFT JOIN detail_peminjaman dp ON pm.id_peminjaman = dp.id_peminjaman
    LEFT JOIN buku b ON dp.kode_buku = b.kode_buku
    GROUP BY pm.id_peminjaman
    ORDER BY pm.id_peminjaman DESC
");

// ===== PEMINJAMAN YANG SEDANG TELAT (belum dikembalikan, sudah lewat batas hari) =====
// Dipakai untuk kasih tahu petugas siapa yang perlu dihubungi (nomor telepon ditampilkan)
$sedang_telat = $koneksi->query("
    SELECT pm.id_peminjaman, a.nama_anggota, pm.tanggal_pinjam,
           DATEDIFF(CURDATE(), pm.tanggal_pinjam) AS hari_dipinjam,
           GROUP_CONCAT(DISTINCT b.judul_buku SEPARATOR ', ') AS daftar_buku,
           GROUP_CONCAT(DISTINCT nt.nomor_telepon SEPARATOR ', ') AS daftar_telepon
    FROM peminjaman pm
    JOIN anggota a ON pm.id_anggota = a.id_anggota
    LEFT JOIN detail_peminjaman dp ON pm.id_peminjaman = dp.id_peminjaman
    LEFT JOIN buku b ON dp.kode_buku = b.kode_buku
    LEFT JOIN nomor_telepon nt ON nt.id_anggota = a.id_anggota
    WHERE NOT EXISTS (SELECT 1 FROM pengembalian pk WHERE pk.id_peminjaman = pm.id_peminjaman)
      AND DATEDIFF(CURDATE(), pm.tanggal_pinjam) > " . BATAS_HARI_PINJAM . "
    GROUP BY pm.id_peminjaman
    ORDER BY hari_dipinjam DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Peminjaman - SIM Perpustakaan</title>
<?php include 'partials/style.php'; ?>
</head>
<body>

<div class="layout">
  <?php include 'partials/sidebar.php'; ?>

  <main class="main-content">
    <div class="topbar">
      <h1>Peminjaman</h1>
      <div class="topbar-user">👤 <?= htmlspecialchars($_SESSION['nama_petugas']) ?></div>
    </div>

    <div class="content">
      <?php if ($pesan): ?>
        <div class="alert alert-<?= $tipe_pesan ?>"><?= htmlspecialchars($pesan) ?></div>
      <?php endif; ?>

      <div style="display:flex; gap:20px; align-items:flex-start; flex-wrap:wrap;">

        <!-- FORM TAMBAH PEMINJAMAN -->
        <div class="form-card">
          <div class="card-title" style="margin-bottom:14px;">Catat Peminjaman Baru</div>

          <?php if ($daftar_anggota->num_rows === 0): ?>
            <div class="alert alert-warning">Belum ada anggota terdaftar. Tambahkan dulu di menu Anggota.</div>
          <?php elseif ($daftar_buku->num_rows === 0): ?>
            <div class="alert alert-warning">Tidak ada buku dengan stok tersedia.</div>
          <?php else: ?>
            <form method="POST">
              <div class="form-group">
                <label>Anggota</label>
                <select name="id_anggota" required>
                  <option value="">-- Pilih Anggota --</option>
                  <?php while ($a = $daftar_anggota->fetch_assoc()): ?>
                    <option value="<?= $a['id_anggota'] ?>"><?= htmlspecialchars($a['nama_anggota']) ?></option>
                  <?php endwhile; ?>
                </select>
              </div>
              <div class="form-group">
                <label>Tanggal Pinjam</label>
                <input type="date" name="tanggal_pinjam" required value="<?= date('Y-m-d') ?>">
              </div>

              <div class="form-group">
                <label>Buku yang Dipinjam</label>
                <div id="buku-wrapper">
                  <div class="multi-row">
                    <select name="kode_buku[]" required>
                      <option value="">-- Pilih Buku --</option>
                      <?php
                        $daftar_buku->data_seek(0);
                        while ($b = $daftar_buku->fetch_assoc()):
                      ?>
                        <option value="<?= $b['kode_buku'] ?>">
                          <?= htmlspecialchars($b['judul_buku']) ?> (stok: <?= $b['stok'] ?>)
                        </option>
                      <?php endwhile; ?>
                    </select>
                    <input type="number" name="jumlah[]" min="1" value="1" required style="max-width:70px;">
                  </div>
                </div>
                <button type="button" class="btn btn-sm" onclick="tambahBuku()">+ Tambah buku lain</button>
              </div>

              <div class="form-actions">
                <button type="submit" name="simpan_peminjaman" class="btn btn-primary">Simpan Peminjaman</button>
              </div>
            </form>
          <?php endif; ?>
        </div>

        <!-- PEMINJAMAN TERLAMBAT (belum dikembalikan, sudah lewat batas hari) -->
        <?php if ($sedang_telat->num_rows > 0): ?>
        <div class="card" style="flex-basis:100%; border-left:4px solid #A32D2D;">
          <div class="card-header">
            <span class="card-title">⏰ Peminjaman Terlambat | Perlu Dihubungi (<?= $sedang_telat->num_rows ?>)</span>
          </div>
          <table>
            <thead>
              <tr><th>Anggota</th><th>Buku Dipinjam</th><th>Tgl Pinjam</th><th>Sudah Berapa Hari</th><th>Nomor Telepon</th></tr>
            </thead>
            <tbody>
              <?php while ($t = $sedang_telat->fetch_assoc()): ?>
                <tr>
                  <td><?= htmlspecialchars($t['nama_anggota']) ?></td>
                  <td><?= htmlspecialchars($t['daftar_buku'] ?? '-') ?></td>
                  <td><?= date('d M Y', strtotime($t['tanggal_pinjam'])) ?></td>
                  <td><span class="badge badge-danger"><?= $t['hari_dipinjam'] ?> hari</span></td>
                  <td><?= htmlspecialchars($t['daftar_telepon'] ?? 'Belum ada nomor telepon') ?></td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>

        <!-- TABEL PEMINJAMAN -->
        <div class="card" style="flex:1; min-width:400px;">
          <div class="card-header">
            <span class="card-title">Riwayat Peminjaman</span>
          </div>
          <table>
            <thead>
              <tr><th>ID</th><th>Anggota</th><th>Buku Dipinjam</th><th>Tgl Pinjam</th><th>Petugas</th><th>Status</th></tr>
            </thead>
            <tbody>
              <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                  <tr>
                    <td>P<?= str_pad($row['id_peminjaman'], 3, '0', STR_PAD_LEFT) ?></td>
                    <td><?= htmlspecialchars($row['nama_anggota']) ?></td>
                    <td><?= htmlspecialchars($row['daftar_buku'] ?? '-') ?></td>
                    <td><?= date('d M Y', strtotime($row['tanggal_pinjam'])) ?></td>
                    <td><?= htmlspecialchars($row['nama_petugas']) ?></td>
                    <td>
                      <?php if ($row['sudah_kembali']): ?>
                        <span class="badge badge-success">Selesai</span>
                      <?php elseif ($row['hari_dipinjam'] > BATAS_HARI_PINJAM): ?>
                        <span class="badge badge-danger">Telat <?= $row['hari_dipinjam'] ?> hari</span>
                      <?php else: ?>
                        <span class="badge badge-warning">Dipinjam</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr><td colspan="6" class="empty-row">Belum ada transaksi peminjaman.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </main>
</div>

<script>
function tambahBuku() {
  const wrapper = document.getElementById('buku-wrapper');
  const firstSelect = wrapper.querySelector('select');
  const row = document.createElement('div');
  row.className = 'multi-row';
  row.innerHTML = '<select name="kode_buku[]">' + firstSelect.innerHTML + '</select>' +
                   '<input type="number" name="jumlah[]" min="1" value="1" style="max-width:70px;">' +
                   '<button type="button" class="btn btn-sm" onclick="this.parentElement.remove()">✕</button>';
  wrapper.appendChild(row);
}
</script>

</body>
</html>
<?php $koneksi->close(); ?>