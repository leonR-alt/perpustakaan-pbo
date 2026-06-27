<?php
require_once 'cek_login.php';
require_once '../config/koneksi.php';

$pesan = "";
$tipe_pesan = "";

// ===== HAPUS ANGGOTA =====
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    try {
        $stmt = $koneksi->prepare("DELETE FROM anggota WHERE id_anggota = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $pesan = "Anggota berhasil dihapus.";
        $tipe_pesan = "success";
    } catch (mysqli_sql_exception $e) {
        if ($koneksi->errno === 1451) {
            $pesan = "Gagal menghapus. Anggota ini masih memiliki riwayat peminjaman.";
        } else {
            $pesan = "Gagal menghapus: " . $e->getMessage();
        }
        $tipe_pesan = "danger";
    } finally {
        if (isset($stmt)) $stmt->close();
    }
}

// ===== TAMBAH ANGGOTA BARU =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_anggota'])) {
    $nama = $_POST['nama_anggota'];
    $alamat = $_POST['alamat'];
    $email = $_POST['email'];
    $tanggal_daftar = $_POST['tanggal_daftar'];
    $nomor_telepon_list = $_POST['nomor_telepon']; // array, bisa lebih dari satu

    try {
        $stmt = $koneksi->prepare("INSERT INTO anggota (nama_anggota, alamat, email, tanggal_daftar) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $nama, $alamat, $email, $tanggal_daftar);
        $stmt->execute();

        $id_anggota_baru = $koneksi->insert_id;

        // Simpan semua nomor telepon yang diisi (skip yang kosong)
        $stmtTelp = $koneksi->prepare("INSERT INTO nomor_telepon (id_anggota, nomor_telepon) VALUES (?, ?)");
        foreach ($nomor_telepon_list as $no) {
            $no = trim($no);
            if ($no !== "") {
                $stmtTelp->bind_param("is", $id_anggota_baru, $no);
                $stmtTelp->execute();
            }
        }
        $stmtTelp->close();

        $pesan = "Anggota baru berhasil ditambahkan.";
        $tipe_pesan = "success";

    } catch (mysqli_sql_exception $e) {
        if ($koneksi->errno === 1062) {
            $pesan = "Gagal: Email tersebut sudah terdaftar.";
        } else {
            $pesan = "Gagal menyimpan: " . $e->getMessage();
        }
        $tipe_pesan = "danger";
    } finally {
        if (isset($stmt)) $stmt->close();
    }
}

// ===== AMBIL DATA UNTUK DITAMPILKAN =====
$result = $koneksi->query("
    SELECT a.id_anggota, a.nama_anggota, a.alamat, a.email, a.tanggal_daftar,
           GROUP_CONCAT(nt.nomor_telepon SEPARATOR ', ') AS daftar_telepon
    FROM anggota a
    LEFT JOIN nomor_telepon nt ON a.id_anggota = nt.id_anggota
    GROUP BY a.id_anggota
    ORDER BY a.id_anggota DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Data Anggota - SIM Perpustakaan</title>
<?php include 'partials/style.php'; ?>
</head>
<body>

<div class="layout">
  <?php include 'partials/sidebar.php'; ?>

  <main class="main-content">
    <div class="topbar">
      <h1>Data Anggota</h1>
      <div class="topbar-user">👤 <?= htmlspecialchars($_SESSION['nama_petugas']) ?></div>
    </div>

    <div class="content">
      <?php if ($pesan): ?>
        <div class="alert alert-<?= $tipe_pesan ?>"><?= htmlspecialchars($pesan) ?></div>
      <?php endif; ?>

      <div style="display:flex; gap:20px; align-items:flex-start; flex-wrap:wrap;">

        <!-- FORM TAMBAH ANGGOTA -->
        <div class="form-card">
          <div class="card-title" style="margin-bottom:14px;">Daftarkan Anggota Baru</div>
          <form method="POST" id="form-anggota">
            <div class="form-group">
              <label>Nama Anggota</label>
              <input type="text" name="nama_anggota" required placeholder="Nama lengkap">
            </div>
            <div class="form-group">
              <label>Alamat</label>
              <textarea name="alamat" rows="2" placeholder="Alamat lengkap"></textarea>
            </div>
            <div class="form-group">
              <label>Email</label>
              <input type="email" name="email" required placeholder="email@gmail.com">
            </div>
            <div class="form-group">
              <label>Tanggal Daftar</label>
              <input type="date" name="tanggal_daftar" required value="<?= date('Y-m-d') ?>">
            </div>

            <div class="form-group">
              <label>Nomor Telepon</label>
              <div id="telepon-wrapper">
                <div class="multi-row">
                  <input type="text" name="nomor_telepon[]" placeholder="08xxxxxxxxxx" required>
                </div>
              </div>
              <button type="button" class="btn btn-sm" onclick="tambahTelepon()">+ Tambah nomor lain</button>
            </div>

            <div class="form-actions">
              <button type="submit" name="simpan_anggota" class="btn btn-primary">Simpan Anggota</button>
            </div>
          </form>
        </div>

        <!-- TABEL ANGGOTA -->
        <div class="card" style="flex:1; min-width:400px;">
          <div class="card-header">
            <span class="card-title">Daftar Anggota</span>
          </div>
          <table>
            <thead>
              <tr><th>ID</th><th>Nama</th><th>Email</th><th>No. Telepon</th><th>Tgl Daftar</th><th>Aksi</th></tr>
            </thead>
            <tbody>
              <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                  <tr>
                    <td>A<?= str_pad($row['id_anggota'], 3, '0', STR_PAD_LEFT) ?></td>
                    <td><?= htmlspecialchars($row['nama_anggota']) ?></td>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                    <td><?= htmlspecialchars($row['daftar_telepon'] ?? '-') ?></td>
                    <td><?= date('d M Y', strtotime($row['tanggal_daftar'])) ?></td>
                    <td>
                      <a href="?hapus=<?= $row['id_anggota'] ?>" class="btn btn-sm btn-danger"
                         onclick="return confirm('Hapus anggota ini? Tindakan ini tidak bisa dibatalkan.')">Hapus</a>
                    </td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr><td colspan="6" class="empty-row">Belum ada anggota terdaftar.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </main>
</div>

<script>
function tambahTelepon() {
  const wrapper = document.getElementById('telepon-wrapper');
  const row = document.createElement('div');
  row.className = 'multi-row';
  row.innerHTML = '<input type="text" name="nomor_telepon[]" placeholder="08xxxxxxxxxx">' +
                   '<button type="button" class="btn btn-sm" onclick="this.parentElement.remove()">✕</button>';
  wrapper.appendChild(row);
}
</script>

</body>
</html>
<?php $koneksi->close(); ?>