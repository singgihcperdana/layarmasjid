<?php
/**
 * Halaman Login Pengurus Masjid (DKM)
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Jika sudah login, langsung lempar ke profil
if (isset($_SESSION['masjid_id'])) {
    header("Location: profile");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../api/db.php';

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
