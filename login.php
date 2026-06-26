<?php
session_start();
require_once 'config/koneksi.php';

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Cari petugas berdasarkan email (prepared statement, aman dari SQL Injection)
    $stmt = $koneksi->prepare("SELECT id_petugas, nama_petugas, email, password FROM petugas WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $petugas = $result->fetch_assoc();

        // NOTE: Untuk tugas kuliah ini password dibandingkan langsung (plain text).
        // Di sistem sungguhan, password HARUS disimpan dengan password_hash()
        // dan dicek dengan password_verify(). Bisa ditingkatkan nanti kalau diminta.
        if ($password === $petugas['password']) {
            $_SESSION['id_petugas'] = $petugas['id_petugas'];
            $_SESSION['nama_petugas'] = $petugas['nama_petugas'];
            $_SESSION['email_petugas'] = $petugas['email'];

            header("Location: admin/dashboard.php");
            exit;
        } else {
            $error = "Password salah.";
        }
    } else {
        $error = "Email tidak ditemukan.";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login Petugas - SIM Perpustakaan</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    background: #F5F5F3;
    color: #1a1a1a;
    height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  .login-box {
    background: #fff;
    border: 1px solid #E5E5E0;
    border-radius: 14px;
    width: 380px;
    padding: 32px;
  }
  .logo {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 24px;
  }
  .logo-icon {
    width: 40px; height: 40px;
    background: #EEEDFE;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 20px;
  }
  .logo-text { font-size: 16px; font-weight: 600; }
  .logo-sub { font-size: 12px; color: #888; }
  h1 { font-size: 18px; font-weight: 600; margin-bottom: 4px; }
  p.subtitle { font-size: 13px; color: #888; margin-bottom: 20px; }
  label { font-size: 12px; font-weight: 500; color: #555; display: block; margin-bottom: 5px; }
  input {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #D5D5D0;
    border-radius: 8px;
    font-size: 13px;
    margin-bottom: 16px;
    outline: none;
  }
  input:focus { border-color: #534AB7; box-shadow: 0 0 0 3px rgba(83,74,183,0.12); }
  button {
    width: 100%;
    background: #534AB7;
    color: #fff;
    border: none;
    padding: 11px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
  }
  button:hover { background: #3C3489; }
  .error {
    background: #FCEBEB;
    color: #791F1F;
    border: 1px solid #F7C1C1;
    padding: 10px 14px;
    border-radius: 8px;
    font-size: 13px;
    margin-bottom: 16px;
  }
  .hint {
    margin-top: 16px;
    font-size: 12px;
    color: #aaa;
    text-align: center;
  }
</style>
</head>
<body>

<div class="login-box">
  <div class="logo">
    <div class="logo-icon">📚</div>
    <div>
      <div class="logo-text">SIM Perpustakaan</div>
      <div class="logo-sub">Panel Petugas</div>
    </div>
  </div>

  <h1>Login Petugas</h1>
  <p class="subtitle">Masuk untuk mengelola data perpustakaan</p>

  <?php if ($error): ?>
    <div class="error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST">
    <label>Email</label>
    <input type="email" name="email" required placeholder="admin@perpustakaan.com">

    <label>Password</label>
    <input type="password" name="password" required placeholder="••••••••">

    <button type="submit">Masuk</button>
  </form>

  <div class="hint">Default: admin@perpustakaan.com / admin123</div>
</div>

</body>
</html>
<?php $koneksi->close(); ?>