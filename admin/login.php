<?php
/**
 * Halaman Login Pengurus Masjid (DKM)
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../api/db.php';

// Jika sudah login, langsung lempar ke profil
if (isset($_SESSION['masjid_id'])) {
    header("Location: profile");
    exit;
}

$error = $_GET['error'] ?? '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $login_input = trim($_POST['username'] ?? $_POST['id_masjid'] ?? '');
    $password    = $_POST['password'] ?? '';

    if (empty($login_input) || empty($password)) {
        $error = 'Silakan masukkan Username / ID Masjid dan Password.';
    } else {
        // Cari user berdasarkan username atau masjid_id
        $stmt = $pdo->prepare("
            SELECT u.*, m.nama AS masjid_nama 
            FROM users u 
            JOIN masjid m ON u.masjid_id = m.id 
            WHERE u.username = ? OR u.masjid_id = ? 
            LIMIT 1
        ");
        $stmt->execute([$login_input, $login_input]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']     = (int)$user['id'];
            $_SESSION['username']    = $user['username'];
            $_SESSION['user_nama']   = $user['nama'];
            $_SESSION['user_role']   = $user['role'];
            $_SESSION['masjid_id']   = $user['masjid_id'];
            $_SESSION['masjid_nama'] = $user['masjid_nama'];
            header("Location: profile");
            exit;
        } else {
            $error = 'Username atau Password salah. Silakan coba lagi.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login Pengurus - Layar Masjid</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <!-- Auth Page CSS -->
  <link rel="stylesheet" href="<?= asset_v('assets/css/auth.css') ?>">
</head>
<body>

  <div class="login-card">
    <div class="brand-icon">
      <i class="bi bi-moon-stars-fill"></i>
    </div>
    <h3 class="text-center fw-bold mb-1" style="color: #f0c96a;">Layar Masjid</h3>
    <p class="text-center text-white-50 mb-4" style="font-size: 0.9rem;">Portal Pengurus DKM</p>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger py-2 px-3 rounded-3" role="alert" style="font-size: 0.9rem;">
        <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <!-- Tombol Masuk dengan Google -->
    <div class="mb-3">
      <a href="auth/google_login.php" class="btn btn-google w-100 py-2 d-flex align-items-center justify-content-center gap-2 shadow-sm">
        <svg width="18" height="18" viewBox="0 0 24 24">
          <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
          <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
          <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
          <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
        </svg>
        <span>Masuk dengan Google</span>
      </a>
    </div>

    <div class="d-flex align-items-center my-3">
      <hr class="flex-grow-1 border-secondary my-0">
      <span class="px-3 text-white-50 small">atau</span>
      <hr class="flex-grow-1 border-secondary my-0">
    </div>

    <form method="POST" action="">
      <div class="mb-3">
        <label for="username" class="form-label text-light small fw-semibold">Username atau ID Masjid</label>
        <div class="input-group">
          <span class="input-group-text bg-transparent text-white-50 border-secondary"><i class="bi bi-person-fill"></i></span>
          <input type="text" class="form-control" id="username" name="username" placeholder="Masukkan username atau ID masjid" value="<?= htmlspecialchars($_POST['username'] ?? $_POST['id_masjid'] ?? '') ?>" required autofocus>
        </div>
      </div>

      <div class="mb-4">
        <label for="password" class="form-label text-light small fw-semibold">Password</label>
        <div class="input-group">
          <span class="input-group-text bg-transparent text-white-50 border-secondary"><i class="bi bi-key-fill"></i></span>
          <input type="password" class="form-control" id="password" name="password" placeholder="Masukkan password" required>
        </div>
      </div>

      <button type="submit" class="btn btn-gold">
        <i class="bi bi-box-arrow-in-right me-1"></i> Masuk ke Panel
      </button>
    </form>

    <div class="hint-box">
      <div>Akun Uji Coba:</div>
      <strong>Username:</strong> <code>admin</code> (atau ID: <code>101</code>) &bull; <strong>Password:</strong> <code>admin123</code>
    </div>
  </div>

</body>
</html>
