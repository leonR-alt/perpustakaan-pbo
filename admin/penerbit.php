<?php
require_once 'cek_login.php';
require_once '../config/koneksi.php';

$pesan = "";
$tipe_pesan = "";

// ===== HAPUS PENERBIT =====
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    $stmt = $koneksi->prepare("DELETE FROM penerbit WHERE id_penerbit = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $pesan = "Penerbit berhasil dihapus.";
        $tipe_pesan = "success";
    } else {
        $pesan = "Gagal menghapus. Pastikan penerbit ini tidak memiliki buku terdaftar.";
        $tipe_pesan = "danger";
    }
    $stmt->close();
}

// ===== TAMBAH PENERBIT BARU =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_penerbit'])) {
    $nama = $_POST['nama_penerbit'];
    $alamat = $_POST['alamat'];
    $nib = $_POST['nib'];

    $stmt = $koneksi->prepare("INSERT INTO penerbit (nama_penerbit, alamat, nib) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $nama, $alamat, $nib);

    if ($stmt->execute()) {
        $pesan = "Penerbit baru berhasil ditambahkan.";
        $tipe_pesan = "success";
    } else {
        $pesan = "Gagal menyimpan: " . $stmt->error;
        $tipe_pesan = "danger";
    }
    $stmt->close();
}

// ===== AMBIL DATA UNTUK DITAMPILKAN =====
$result = $koneksi->query("
    SELECT p.id_penerbit, p.nama_penerbit, p.alamat, p.nib,
           (SELECT COUNT(*) FROM buku b WHERE b.id_penerbit = p.id_penerbit) AS jml_buku
    FROM penerbit p
    ORDER BY p.id_penerbit DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Data Penerbit - SIM Perpustakaan</title>
<?php include 'partials/style.php'; ?>
</head>
<body>

<div class="layout">
  <?php include 'partials/sidebar.php'; ?>

  <main class="main-content">
    <div class="topbar">
      <h1>Data Penerbit</h1>
      <div class="topbar-user">👤 <?= htmlspecialchars($_SESSION['nama_petugas']) ?></div>
    </div>

    <div class="content">
      <?php if ($pesan): ?>
        <div class="alert alert-<?= $tipe_pesan ?>"><?= htmlspecialchars($pesan) ?></div>
      <?php endif; ?>

      <div style="display:flex; gap:20px; align-items:flex-start; flex-wrap:wrap;">

        <!-- FORM TAMBAH PENERBIT -->
        <div class="form-card">
          <div class="card-title" style="margin-bottom:14px;">Tambah Penerbit Baru</div>
          <form method="POST">
            <div class="form-group">
              <label>Nama Penerbit</label>
              <input type="text" name="nama_penerbit" required placeholder="Nama penerbit">
            </div>
            <div class="form-group">
              <label>Alamat</label>
              <textarea name="alamat" rows="2" placeholder="Alamat penerbit"></textarea>
            </div>
            <div class="form-group">
              <label>NIB</label>
              <input type="text" name="nib" placeholder="Nomor Induk Berusaha">
            </div>
            <div class="form-actions">
              <button type="submit" name="simpan_penerbit" class="btn btn-primary">Simpan Penerbit</button>
            </div>
          </form>
        </div>

        <!-- TABEL PENERBIT -->
        <div class="card" style="flex:1; min-width:400px;">
          <div class="card-header">
            <span class="card-title">Daftar Penerbit</span>
          </div>
          <table>
            <thead>
              <tr><th>ID</th><th>Nama</th><th>Alamat</th><th>NIB</th><th>Jml Buku</th><th>Aksi</th></tr>
            </thead>
            <tbody>
              <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                  <tr>
                    <td>PB<?= str_pad($row['id_penerbit'], 3, '0', STR_PAD_LEFT) ?></td>
                    <td><?= htmlspecialchars($row['nama_penerbit']) ?></td>
                    <td><?= htmlspecialchars($row['alamat'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($row['nib'] ?? '-') ?></td>
                    <td><?= $row['jml_buku'] ?></td>
                    <td>
                      <a href="?hapus=<?= $row['id_penerbit'] ?>" class="btn btn-sm btn-danger"
                         onclick="return confirm('Hapus penerbit ini? Tindakan ini tidak bisa dibatalkan.')">Hapus</a>
                    </td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr><td colspan="6" class="empty-row">Belum ada penerbit terdaftar.</td></tr>
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