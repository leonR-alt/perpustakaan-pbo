<?php
require_once 'cek_login.php';
require_once '../config/koneksi.php';

$pesan = "";
$tipe_pesan = "";

// ===== HAPUS BUKU =====
if (isset($_GET['hapus'])) {
    $kode = $_GET['hapus'];
    $stmt = $koneksi->prepare("DELETE FROM buku WHERE kode_buku = ?");
    $stmt->bind_param("s", $kode);
    if ($stmt->execute()) {
        $pesan = "Buku berhasil dihapus.";
        $tipe_pesan = "success";
    } else {
        $pesan = "Gagal menghapus. Pastikan buku ini tidak memiliki riwayat peminjaman.";
        $tipe_pesan = "danger";
    }
    $stmt->close();
}

// ===== TAMBAH BUKU BARU =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_buku'])) {
    $kode_buku = $_POST['kode_buku'];
    $isbn = $_POST['isbn'];
    $judul_buku = $_POST['judul_buku'];
    $id_penerbit = $_POST['id_penerbit'] !== "" ? (int)$_POST['id_penerbit'] : null;
    $tahun_terbit = $_POST['tahun_terbit'];
    $stok = (int)$_POST['stok'];
    $pengarang_list = $_POST['id_pengarang']; // array, bisa lebih dari satu

    $stmt = $koneksi->prepare("INSERT INTO buku (kode_buku, isbn, judul_buku, id_penerbit, tahun_terbit, stok) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssiii", $kode_buku, $isbn, $judul_buku, $id_penerbit, $tahun_terbit, $stok);

    if ($stmt->execute()) {
        // Simpan relasi many-to-many ke pengarang (skip yang kosong/duplikat)
        $stmtRel = $koneksi->prepare("INSERT INTO buku_pengarang (kode_buku, id_pengarang) VALUES (?, ?)");
        $tersimpan = [];
        foreach ($pengarang_list as $idPengarang) {
            $idPengarang = (int)$idPengarang;
            if ($idPengarang > 0 && !in_array($idPengarang, $tersimpan)) {
                $stmtRel->bind_param("si", $kode_buku, $idPengarang);
                $stmtRel->execute();
                $tersimpan[] = $idPengarang;
            }
        }
        $stmtRel->close();

        $pesan = "Buku baru berhasil ditambahkan.";
        $tipe_pesan = "success";
    } else {
        $pesan = "Gagal menyimpan: " . $stmt->error;
        $tipe_pesan = "danger";
    }
    $stmt->close();
}

// ===== DATA UNTUK DROPDOWN =====
$daftar_penerbit = $koneksi->query("SELECT id_penerbit, nama_penerbit FROM penerbit ORDER BY nama_penerbit ASC");
$daftar_pengarang = $koneksi->query("SELECT id_pengarang, nama_pengarang FROM pengarang ORDER BY nama_pengarang ASC");

// ===== AMBIL DATA BUKU UNTUK DITAMPILKAN =====
$result = $koneksi->query("
    SELECT b.kode_buku, b.isbn, b.judul_buku, b.tahun_terbit, b.stok,
           p.nama_penerbit,
           GROUP_CONCAT(pg.nama_pengarang SEPARATOR ', ') AS daftar_pengarang
    FROM buku b
    LEFT JOIN penerbit p ON b.id_penerbit = p.id_penerbit
    LEFT JOIN buku_pengarang bp ON b.kode_buku = bp.kode_buku
    LEFT JOIN pengarang pg ON bp.id_pengarang = pg.id_pengarang
    GROUP BY b.kode_buku
    ORDER BY b.kode_buku ASC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Data Buku - SIM Perpustakaan</title>
<?php include 'partials/style.php'; ?>
</head>
<body>

<div class="layout">
  <?php include 'partials/sidebar.php'; ?>

  <main class="main-content">
    <div class="topbar">
      <h1>Data Buku</h1>
      <div class="topbar-user">👤 <?= htmlspecialchars($_SESSION['nama_petugas']) ?></div>
    </div>

    <div class="content">
      <?php if ($pesan): ?>
        <div class="alert alert-<?= $tipe_pesan ?>"><?= htmlspecialchars($pesan) ?></div>
      <?php endif; ?>

      <div style="display:flex; gap:20px; align-items:flex-start; flex-wrap:wrap;">

        <!-- FORM TAMBAH BUKU -->
        <div class="form-card">
          <div class="card-title" style="margin-bottom:14px;">Tambah Buku Baru</div>
          <form method="POST">
            <div class="form-group">
              <label>Kode Buku</label>
              <input type="text" name="kode_buku" required placeholder="Contoh: BK001">
            </div>
            <div class="form-group">
              <label>ISBN</label>
              <input type="text" name="isbn" placeholder="Nomor ISBN">
            </div>
            <div class="form-group">
              <label>Judul Buku</label>
              <input type="text" name="judul_buku" required placeholder="Judul buku">
            </div>
            <div class="form-group">
              <label>Penerbit</label>
              <select name="id_penerbit">
                <option value="">-- Pilih Penerbit --</option>
                <?php while ($p = $daftar_penerbit->fetch_assoc()): ?>
                  <option value="<?= $p['id_penerbit'] ?>"><?= htmlspecialchars($p['nama_penerbit']) ?></option>
                <?php endwhile; ?>
              </select>
            </div>
            <div class="form-group">
              <label>Tahun Terbit</label>
              <input type="number" name="tahun_terbit" min="1900" max="2100" placeholder="Contoh: 2023">
            </div>
            <div class="form-group">
              <label>Stok</label>
              <input type="number" name="stok" min="0" value="0" required>
            </div>

            <div class="form-group">
              <label>Pengarang</label>
              <div id="pengarang-wrapper">
                <div class="multi-row">
                  <select name="id_pengarang[]" required>
                    <option value="">-- Pilih Pengarang --</option>
                    <?php
                      $daftar_pengarang->data_seek(0);
                      while ($pg = $daftar_pengarang->fetch_assoc()):
                    ?>
                      <option value="<?= $pg['id_pengarang'] ?>"><?= htmlspecialchars($pg['nama_pengarang']) ?></option>
                    <?php endwhile; ?>
                  </select>
                </div>
              </div>
              <button type="button" class="btn btn-sm" onclick="tambahPengarang()">+ Tambah pengarang lain</button>
              <?php if ($daftar_pengarang->num_rows === 0): ?>
                <div style="font-size:12px; color:#A32D2D; margin-top:6px;">Belum ada data pengarang. Tambahkan dulu di menu Pengarang.</div>
              <?php endif; ?>
            </div>

            <div class="form-actions">
              <button type="submit" name="simpan_buku" class="btn btn-primary">Simpan Buku</button>
            </div>
          </form>
        </div>

        <!-- TABEL BUKU -->
        <div class="card" style="flex:1; min-width:400px;">
          <div class="card-header">
            <span class="card-title">Daftar Buku</span>
          </div>
          <table>
            <thead>
              <tr><th>Kode</th><th>Judul</th><th>Penerbit</th><th>Pengarang</th><th>Tahun</th><th>Stok</th><th>Aksi</th></tr>
            </thead>
            <tbody>
              <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                  <tr>
                    <td><?= htmlspecialchars($row['kode_buku']) ?></td>
                    <td><?= htmlspecialchars($row['judul_buku']) ?></td>
                    <td><?= htmlspecialchars($row['nama_penerbit'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($row['daftar_pengarang'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($row['tahun_terbit'] ?? '-') ?></td>
                    <td>
                      <?php if ($row['stok'] > 0): ?>
                        <span class="badge badge-success"><?= $row['stok'] ?></span>
                      <?php else: ?>
                        <span class="badge badge-danger">0</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <a href="?hapus=<?= urlencode($row['kode_buku']) ?>" class="btn btn-sm btn-danger"
                         onclick="return confirm('Hapus buku ini? Tindakan ini tidak bisa dibatalkan.')">Hapus</a>
                    </td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr><td colspan="7" class="empty-row">Belum ada buku terdaftar.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </main>
</div>

<script>
function tambahPengarang() {
  const wrapper = document.getElementById('pengarang-wrapper');
  const firstSelect = wrapper.querySelector('select');
  const row = document.createElement('div');
  row.className = 'multi-row';
  row.innerHTML = '<select name="id_pengarang[]">' + firstSelect.innerHTML + '</select>' +
                   '<button type="button" class="btn btn-sm" onclick="this.parentElement.remove()">✕</button>';
  wrapper.appendChild(row);
}
</script>

</body>
</html>
<?php $koneksi->close(); ?>