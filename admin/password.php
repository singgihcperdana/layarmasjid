<?php
/**
 * Halaman Ganti Password Akun DKM
 * File: admin/password.php (URL: /admin/password atau /password)
 */
$page_title   = "Ganti Password";
$current_page = "password";

require_once __DIR__ . '/includes/header.php';
?>

<div class="row">
  
  <!-- Form Ganti Password -->
  <div class="col-lg-6 mb-4">
    <div class="card shadow-sm">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-key-fill text-success me-1"></i> Ganti Password Akun DKM</span>
        <span class="badge bg-success-subtle text-success border border-success-subtle">ID: <?= htmlspecialchars($current_masjid_id) ?></span>
      </div>
      <div class="card-body">
        <p class="small text-muted mb-4">
          Perbarui password akun Anda untuk menjaga keamanan akses dan pengelolaan informasi masjid.
        </p>

        <form id="formChangePassword">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Password Saat Ini <span class="text-danger">*</span></label>
            <div class="input-group">
              <input type="password" class="form-control" name="old_password" id="oldPassword" placeholder="Masukkan password lama" required autocomplete="current-password">
              <button class="btn btn-outline-secondary btn-toggle-pw" type="button" data-target="oldPassword" title="Tampilkan / Sembunyikan Password">
                <i class="bi bi-eye"></i>
              </button>
            </div>
          </div>

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
            <i class="bi bi-shield-check me-1"></i> Perbarui Password
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
        // Kembalikan semua tipe input ke password
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
