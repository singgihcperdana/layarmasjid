<?php
/**
 * Halaman Ganti Password Akun DKM
 * File: admin/password.php (URL: /admin/password atau /password)
 */
$page_title   = "Ganti Password";
$current_page = "password";

require_once __DIR__ . '/includes/header.php';

// Ambil status password user saat ini
$user_id = $_SESSION['user_id'] ?? null;
if ($user_id) {
    $stmtUser = $pdo->prepare("SELECT password, google_id FROM users WHERE id = ? AND masjid_id = ? LIMIT 1");
    $stmtUser->execute([$user_id, $current_masjid_id]);
    $current_user = $stmtUser->fetch();
} else {
    $stmtUser = $pdo->prepare("SELECT password, google_id FROM users WHERE masjid_id = ? LIMIT 1");
    $stmtUser->execute([$current_masjid_id]);
    $current_user = $stmtUser->fetch();
}

$has_password = !empty($current_user['password']);
$is_google_linked = !empty($current_user['google_id']);
?>

<div class="row">
  
  <!-- Form Ganti / Atur Password -->
  <div class="col-lg-6 col-xl-5 mb-4">
    <div class="card shadow-sm">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-key-fill text-success me-1"></i> <?= $has_password ? 'Ganti Password Akun' : 'Atur Password Baru' ?></span>
        <?php if ($is_google_linked): ?>
          <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 0.72rem;">
            <i class="bi bi-google me-1"></i> Google
          </span>
        <?php endif; ?>
      </div>
      <div class="card-body">
        
        <?php if (!$has_password): ?>
          <div class="alert alert-info py-2 px-3 rounded-3 mb-4 small" style="font-size: 0.82rem;">
            <i class="bi bi-info-circle-fill me-1"></i> Akun Anda mendaftar melalui Google Sign-In dan belum memiliki password lokal. Buat password di bawah jika Anda ingin bisa login menggunakan email &amp; password.
          </div>
        <?php else: ?>
          <p class="small text-muted mb-4">
            Perbarui password akun Anda untuk menjaga keamanan akses dan pengelolaan informasi masjid.
          </p>
        <?php endif; ?>

        <form id="formChangePassword">
          <?php if ($has_password): ?>
            <div class="mb-3">
              <label class="form-label small fw-semibold">Password Saat Ini <span class="text-danger">*</span></label>
              <div class="input-group">
                <input type="password" class="form-control" name="old_password" id="oldPassword" placeholder="Masukkan password lama" required autocomplete="current-password">
                <button class="btn btn-outline-secondary btn-toggle-pw" type="button" data-target="oldPassword" title="Tampilkan / Sembunyikan Password">
                  <i class="bi bi-eye"></i>
                </button>
              </div>
            </div>
          <?php endif; ?>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Password Baru <span class="text-danger">*</span></label>
            <div class="input-group">
              <input type="password" class="form-control" name="new_password" id="newPassword" placeholder="Minimal 6 karakter" minlength="6" required autocomplete="new-password">
              <button class="btn btn-outline-secondary btn-toggle-pw" type="button" data-target="newPassword" title="Tampilkan / Sembunyikan Password">
                <i class="bi bi-eye"></i>
              </button>
            </div>
            <div class="form-text" style="font-size: 0.78rem;">Minimal 6 karakter, disarankan kombinasi huruf dan angka.</div>
          </div>

          <div class="mb-4">
            <label class="form-label small fw-semibold">Ulangi Password Baru <span class="text-danger">*</span></label>
            <div class="input-group">
              <input type="password" class="form-control" name="confirm_password" id="confirmPassword" placeholder="Ketik ulang password baru" minlength="6" required autocomplete="new-password">
              <button class="btn btn-outline-secondary btn-toggle-pw" type="button" data-target="confirmPassword" title="Tampilkan / Sembunyikan Password">
                <i class="bi bi-eye"></i>
              </button>
            </div>
          </div>

          <button type="submit" class="btn btn-masjid px-4 py-2 fw-semibold shadow-sm" id="btnSubmitPw">
            <i class="bi bi-shield-check me-1"></i> <?= $has_password ? 'Perbarui Password' : 'Simpan Password Baru' ?>
          </button>
        </form>
      </div>
    </div>
  </div>

</div>

<script>
  // Fitur Toggle Show / Hide Password
  document.querySelectorAll('.btn-toggle-pw').forEach(btn => {
    btn.addEventListener('click', function() {
      const targetId = this.dataset.target;
      const input = document.getElementById(targetId);
      const icon = this.querySelector('i');
      if (!input) return;

      if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
      } else {
        input.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
      }
    });
  });

  // Submit Form Ganti Password via AJAX
  document.getElementById('formChangePassword').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btnSubmit = document.getElementById('btnSubmitPw');
    const oldBtnText = btnSubmit.innerHTML;

    btnSubmit.disabled = true;
    btnSubmit.innerHTML = '<i class="bi bi-arrow-repeat spin me-1"></i> Memproses...';

    const formData = new FormData(this);
    try {
      const res = await fetch('api/change_password.php', { method: 'POST', body: formData });
      const json = await res.json();
      if (json.status === 'success') {
        showAlert('success', json.message);
        this.reset();
        document.querySelectorAll('.btn-toggle-pw').forEach(btn => {
          const targetId = btn.dataset.target;
          const input = document.getElementById(targetId);
          const icon = btn.querySelector('i');
          if (input) input.type = 'password';
          if (icon) {
            icon.classList.remove('bi-eye-slash');
            icon.classList.add('bi-eye');
          }
        });
      } else {
        showAlert('danger', json.message);
      }
    } catch (err) {
      showAlert('danger', 'Gagal memperbarui password. Silakan coba kembali.');
    } finally {
      btnSubmit.disabled = false;
      btnSubmit.innerHTML = oldBtnText;
    }
  });
</script>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
