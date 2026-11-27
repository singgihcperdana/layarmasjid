<?php
/**
 * Halaman Pengaturan Profil Masjid & Ganti Password DKM
 * File: admin/profile.php (URL: /admin/profile)
 */
$page_title   = "Profil";
$current_page = "profile";

require_once __DIR__ . '/includes/header.php';
?>

<div class="row">
  
  <!-- Card 1: Identitas Masjid -->
  <div class="col-lg-7 mb-4">
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-building me-1"></i> Profil Identitas Masjid</span>
        <span class="badge bg-success-subtle text-success border border-success-subtle">ID: <?= htmlspecialchars($current_masjid_id) ?></span>
      </div>
      <div class="card-body">
        <form id="formSaveProfile" enctype="multipart/form-data">
          
          <div class="mb-3">
            <label class="form-label small fw-semibold">Nama Masjid <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="nama" value="<?= htmlspecialchars($masjid['nama']) ?>" placeholder="Contoh: Masjid Al Ikhlas" required>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Alamat Lengkap Masjid <span class="text-danger">*</span></label>
            <textarea class="form-control" name="lokasi" rows="2" placeholder="Contoh: Jl. Contoh No. 12, Kebayoran Baru • Jakarta Selatan" required><?= htmlspecialchars($masjid['lokasi'] ?? '') ?></textarea>
            <div class="form-text">Teks ini tampil di bagian atas layar TV di bawah nama masjid.</div>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Informasi Kontak & Rekening Infaq</label>
            <textarea class="form-control" name="kontak_info" rows="2" placeholder="Contoh: BSI 7123 456 789 a.n. Masjid Al Ikhlas • WA: 0812-3456-7890"><?= htmlspecialchars($masjid['kontak_info'] ?? '') ?></textarea>
            <div class="form-text">Tampil di header layar TV tepat di bawah alamat masjid. Kosongkan jika tidak ingin ditampilkan.</div>
          </div>

          <!-- Kotak Logo Masjid (Bisa Kosong) -->
          <div class="mb-4 p-3 rounded-3 border bg-light">
            <label class="form-label small fw-semibold d-block mb-2">Logo Masjid (Opsional)</label>
            <div class="d-flex align-items-center gap-3">
              <div class="logo-preview-box text-center p-2 rounded border bg-white" style="width: 84px; height: 84px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <?php if (!empty($masjid['logo'])): ?>
                  <img id="logoPreviewImg" src="<?= htmlspecialchars($masjid['logo']) ?>" alt="Logo" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                  <span id="noLogoPlaceholder" class="text-muted small d-none" style="font-size: 0.7rem;"><i class="bi bi-image fs-4 d-block text-secondary"></i>Tanpa Logo</span>
                <?php else: ?>
                  <span id="noLogoPlaceholder" class="text-muted small" style="font-size: 0.7rem;"><i class="bi bi-image fs-4 d-block text-secondary"></i>Tanpa Logo</span>
                  <img id="logoPreviewImg" src="" alt="Logo" class="d-none" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                <?php endif; ?>
              </div>
              
              <div class="flex-grow-1">
                <input type="file" class="form-control form-control-sm mb-2" id="logoInput" name="logo" accept="image/png,image/jpeg,image/webp,image/svg+xml">
                
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="remove_logo" value="1" id="checkRemoveLogo" <?= empty($masjid['logo']) ? 'disabled' : '' ?>>
                  <label class="form-check-label small text-danger" for="checkRemoveLogo">
                    Hapus logo / Gunakan teks nama masjid saja
                  </label>
                </div>
                <div class="form-text mt-1" style="font-size: 0.75rem;">Format disarankan: PNG transparan (persegi 1:1). Maks 4 MB.</div>
              </div>
            </div>
          </div>

          <button type="submit" class="btn btn-masjid px-4 shadow-sm">
            <i class="bi bi-floppy-fill me-1"></i> Simpan Profil Masjid
          </button>
        </form>
      </div>
    </div>
  </div>

  <!-- Card 2: Keamanan Akun DKM (Ganti Password) -->
  <div class="col-lg-5 mb-4">
    <div class="card h-100">
      <div class="card-header">
        <i class="bi bi-shield-lock-fill me-1"></i> Ganti Password Akun DKM
      </div>
      <div class="card-body">
        <p class="small text-muted mb-3">Ganti password bawaan untuk menjaga keamanan data masjid Anda.</p>
        <form id="formChangePassword">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Password Saat Ini</label>
            <input type="password" class="form-control" name="old_password" placeholder="Masukkan password lama" required>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Password Baru</label>
            <input type="password" class="form-control" name="new_password" placeholder="Minimal 6 karakter" minlength="6" required>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Ulangi Password Baru</label>
            <input type="password" class="form-control" name="confirm_password" placeholder="Ketik ulang password baru" minlength="6" required>
          </div>
          <button type="submit" class="btn btn-outline-success w-100 fw-semibold">
            <i class="bi bi-key-fill me-1"></i> Perbarui Password
          </button>
        </form>
      </div>
    </div>
  </div>

</div>

<script>
  // Preview Logo saat file dipilih
  const logoInput = document.getElementById('logoInput');
  const logoPreviewImg = document.getElementById('logoPreviewImg');
  const noLogoPlaceholder = document.getElementById('noLogoPlaceholder');
  const checkRemoveLogo = document.getElementById('checkRemoveLogo');

  if (logoInput) {
    logoInput.addEventListener('change', function() {
      if (this.files && this.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
          logoPreviewImg.src = e.target.result;
          logoPreviewImg.classList.remove('d-none');
          if (noLogoPlaceholder) noLogoPlaceholder.classList.add('d-none');
          if (checkRemoveLogo) checkRemoveLogo.checked = false;
        };
        reader.readAsDataURL(this.files[0]);
      }
    });
  }

  if (checkRemoveLogo) {
    checkRemoveLogo.addEventListener('change', function() {
      if (this.checked) {
        logoPreviewImg.classList.add('d-none');
        if (noLogoPlaceholder) noLogoPlaceholder.classList.remove('d-none');
        logoInput.value = '';
      } else {
        if (logoPreviewImg.getAttribute('src')) {
          logoPreviewImg.classList.remove('d-none');
          if (noLogoPlaceholder) noLogoPlaceholder.classList.add('d-none');
        }
      }
    });
  }

  // 1. Simpan Profil Masjid
  document.getElementById('formSaveProfile').addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    try {
      const res = await fetch('api/save_profile.php', { method: 'POST', body: formData });
      const json = await res.json();
      if (json.status === 'success') {
        showAlert('success', json.message);
        const newName = formData.get('nama');
        if (newName) {
          document.getElementById('sidebarMasjidName').textContent = newName;
        }
        if (json.logo_url) {
          logoPreviewImg.src = json.logo_url;
          logoPreviewImg.classList.remove('d-none');
          if (noLogoPlaceholder) noLogoPlaceholder.classList.add('d-none');
          checkRemoveLogo.disabled = false;
          checkRemoveLogo.checked = false;
        } else {
          logoPreviewImg.classList.add('d-none');
          if (noLogoPlaceholder) noLogoPlaceholder.classList.remove('d-none');
          checkRemoveLogo.disabled = true;
          checkRemoveLogo.checked = false;
        }
      } else {
        showAlert('danger', json.message);
      }
    } catch (err) {
      showAlert('danger', 'Gagal menyimpan profil masjid.');
    }
  });

  // 2. Ganti Password Akun DKM
  document.getElementById('formChangePassword').addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    try {
      const res = await fetch('api/change_password.php', { method: 'POST', body: formData });
      const json = await res.json();
      if (json.status === 'success') {
        showAlert('success', json.message);
        this.reset();
      } else {
        showAlert('danger', json.message);
      }
    } catch (err) {
      showAlert('danger', 'Gagal memperbarui password.');
    }
  });
</script>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
