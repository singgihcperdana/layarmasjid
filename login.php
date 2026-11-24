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
    require_once __DIR__ . '/api/db.php';

    $id_masjid = trim($_POST['id_masjid'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($id_masjid) || empty($password)) {
        $error = 'Silakan masukkan ID Masjid dan Password.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM masjid WHERE id = ? LIMIT 1");
        $stmt->execute([$id_masjid]);
        $masjid = $stmt->fetch();

        if ($masjid && password_verify($password, $masjid['password'])) {
            $_SESSION['masjid_id'] = $masjid['id'];
            $_SESSION['masjid_nama'] = $masjid['nama'];
            header("Location: profile");
            exit;
        } else {
            $error = 'ID Masjid atau Password salah. Silakan coba lagi.';
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
  <style>
    body {
      background: radial-gradient(circle at top right, rgba(240, 201, 106, 0.08), transparent 30%),
                  linear-gradient(135deg, #051f18 0%, #0a3d2e 60%, #0a4d3a 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
      color: #ffffff;
      padding: 16px;
    }
    .login-card {
      background: rgba(8, 48, 36, 0.88);
      backdrop-filter: blur(12px);
      border: 1px solid rgba(240, 201, 106, 0.35);
      border-radius: 20px;
      box-shadow: 0 24px 60px rgba(0, 0, 0, 0.55);
      max-width: 420px;
      width: 100%;
      padding: 36px 32px;
    }
    .brand-icon {
      width: 64px;
      height: 64px;
      background: linear-gradient(135deg, #e0b735, #b88b18);
      border-radius: 16px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 16px;
      font-size: 32px;
      color: #051f18;
      box-shadow: 0 8px 24px rgba(224, 183, 53, 0.35);
    }
    .form-control {
      background: rgba(5, 31, 24, 0.85);
      border: 1px solid rgba(255, 255, 255, 0.16);
      color: #ffffff;
      border-radius: 10px;
      padding: 12px 14px;
    }
    .form-control:focus {
      background: rgba(5, 31, 24, 0.95);
      border-color: #f0c96a;
      color: #ffffff;
      box-shadow: 0 0 0 3px rgba(240, 201, 106, 0.25);
    }
    .form-control::placeholder {
      color: rgba(255, 255, 255, 0.4);
    }
    .btn-gold {
      background: linear-gradient(135deg, #f0c96a 0%, #d4a737 100%);
      color: #051f18;
      font-weight: 700;
      border: none;
      border-radius: 10px;
      padding: 12px;
      width: 100%;
      transition: all 0.2s ease;
    }
    .btn-gold:hover {
      background: linear-gradient(135deg, #fce29d 0%, #e0b735 100%);
      color: #051f18;
      transform: translateY(-1px);
      box-shadow: 0 8px 20px rgba(240, 201, 106, 0.3);
    }
    .hint-box {
      background: rgba(255, 255, 255, 0.05);
      border: 1px dashed rgba(240, 201, 106, 0.25);
      border-radius: 10px;
      padding: 12px;
      font-size: 0.85rem;
      color: #b9e7d1;
      margin-top: 24px;
      text-align: center;
    }
  </style>
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
        <label for="id_masjid" class="form-label text-light small fw-semibold">ID Masjid</label>
        <div class="input-group">
          <span class="input-group-text bg-transparent text-white-50 border-secondary"><i class="bi bi-building"></i></span>
          <input type="text" class="form-control" id="id_masjid" name="id_masjid" placeholder="Contoh: 101" value="<?= htmlspecialchars($_POST['id_masjid'] ?? '') ?>" required autofocus>
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
      <strong>ID Masjid:</strong> <code>101</code> &bull; <strong>Password:</strong> <code>admin123</code>
    </div>
  </div>

</body>
</html>
