<?php
require_once 'cek_login.php';
require_once '../config/koneksi.php';

$pesan = "";
$tipe_pesan = "";

// ===== HAPUS PENGARANG =====
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    $stmt = $koneksi->prepare("DELETE FROM pengarang WHERE id_pengarang = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $pesan = "Pengarang berhasil dihapus.";
        $tipe_pesan = "success";
    } else {
        $pesan = "Gagal menghapus pengarang.";
        $tipe_pesan = "danger";
    }
    $stmt->close();
}

// ===== TAMBAH PENGARANG BARU =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_pengarang'])) {
    $nama = $_POST['nama_pengarang'];

    $stmt = $koneksi->prepare("INSERT INTO pengarang (nama_pengarang) VALUES (?)");
    $stmt->bind_param("s", $nama);

    if ($stmt->execute()) {
        $pesan = "Pengarang baru berhasil ditambahkan.";
        $tipe_pesan = "success";
    } else {
        $pesan = "Gagal menyimpan: " . $stmt->error;
        $tipe_pesan = "danger";
    }
    $stmt->close();
}

// ===== AMBIL DATA UNTUK DITAMPILKAN =====
$result = $koneksi->query("
    SELECT pg.id_pengarang, pg.nama_pengarang,
           (SELECT COUNT(*) FROM buku_pengarang bp WHERE bp.id_pengarang = pg.id_pengarang) AS jml_buku
    FROM pengarang pg
    ORDER BY pg.id_pengarang DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Data Pengarang - SIM Perpustakaan</title>
<?php include 'partials/style.php'; ?>
</head>
<body>

<div class="layout">
  <?php include 'partials/sidebar.php'; ?>

  <main class="main-content">
    <div class="topbar">
      <h1>Data Pengarang</h1>
      <div class="topbar-user">👤 <?= htmlspecialchars($_SESSION['nama_petugas']) ?></div>
    </div>

    <div class="content">
      <?php if ($pesan): ?>
        <div class="alert alert-<?= $tipe_pesan ?>"><?= htmlspecialchars($pesan) ?></div>
      <?php endif; ?>

      <div style="display:flex; gap:20px; align-items:flex-start; flex-wrap:wrap;">

        <!-- FORM TAMBAH PENGARANG -->
        <div class="form-card">
          <div class="card-title" style="margin-bottom:14px;">Tambah Pengarang Baru</div>
          <form method="POST">
            <div class="form-group">
              <label>Nama Pengarang</label>
              <input type="text" name="nama_pengarang" required placeholder="Nama lengkap pengarang">
            </div>
            <div class="form-actions">
              <button type="submit" name="simpan_pengarang" class="btn btn-primary">Simpan Pengarang</button>
            </div>
          </form>
        </div>

        <!-- TABEL PENGARANG -->
        <div class="card" style="flex:1; min-width:400px;">
          <div class="card-header">
            <span class="card-title">Daftar Pengarang</span>
          </div>
          <table>
            <thead>
              <tr><th>ID</th><th>Nama Pengarang</th><th>Jml Buku</th><th>Aksi</th></tr>
            </thead>
            <tbody>
              <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                  <tr>
                    <td>PG<?= str_pad($row['id_pengarang'], 3, '0', STR_PAD_LEFT) ?></td>
                    <td><?= htmlspecialchars($row['nama_pengarang']) ?></td>
                    <td><?= $row['jml_buku'] ?></td>
                    <td>
                      <a href="?hapus=<?= $row['id_pengarang'] ?>" class="btn btn-sm btn-danger"
                         onclick="return confirm('Hapus pengarang ini? Tindakan ini tidak bisa dibatalkan.')">Hapus</a>
                    </td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr><td colspan="4" class="empty-row">Belum ada pengarang terdaftar.</td></tr>
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