<?php
// Tentukan halaman aktif berdasarkan nama file saat ini, untuk highlight menu
$current = basename($_SERVER['PHP_SELF']);
function navClass($file, $current) {
    return $file === $current ? 'sidebar-link active' : 'sidebar-link';
}
?>
<aside class="sidebar">
  <div class="sidebar-brand">📚 SIM Perpustakaan</div>
  <nav class="sidebar-nav">
    <a href="dashboard.php" class="<?= navClass('dashboard.php', $current) ?>">🏠 Dashboard</a>
    <a href="anggota.php" class="<?= navClass('anggota.php', $current) ?>">👥 Anggota</a>
    <a href="buku.php" class="<?= navClass('buku.php', $current) ?>">📖 Buku</a>
    <a href="penerbit.php" class="<?= navClass('penerbit.php', $current) ?>">🏢 Penerbit</a>
    <a href="pengarang.php" class="<?= navClass('pengarang.php', $current) ?>">✍️ Pengarang</a>
    <a href="peminjaman.php" class="<?= navClass('peminjaman.php', $current) ?>">🔄 Peminjaman</a>
    <a href="pengembalian.php" class="<?= navClass('pengembalian.php', $current) ?>">↩️ Pengembalian</a>
    <a href="denda.php" class="<?= navClass('denda.php', $current) ?>">⚠️ Denda</a>
    <a href="laporan.php" class="<?= navClass('laporan.php', $current) ?>">📊 Laporan</a>
  </nav>
  <div class="sidebar-footer">
    <a href="logout.php">🚪 Keluar</a>
  </div>
</aside>