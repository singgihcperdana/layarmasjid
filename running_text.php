<?php
/**
 * Halaman Pengelolaan Teks Berjalan (Running Text) Layar TV Masjid
 * File: running_text.php (URL: /running_text)
 */
$page_title   = "Teks Berjalan";
$current_page = "running_text";

require_once __DIR__ . '/includes/header.php';
?>

<style>
  .rt-card-header {
    background: #ffffff;
    border-bottom: 1px solid rgba(10, 77, 58, 0.1);
    font-weight: 700;
    color: #0a4d3a;
    padding: 16px 20px;
    border-top-left-radius: 14px !important;
    border-top-right-radius: 14px !important;
  }
  .table-rt th {
    background-color: #0a4d3a !important;
    color: #ffffff !important;
    text-align: center;
    font-weight: 600;
    padding: 12px 10px;
    font-size: 0.88rem;
    vertical-align: middle;
    border-bottom: 2px solid #d4af37 !important;
  }
  .table-rt td {
    vertical-align: middle;
    padding: 12px 10px;
    font-size: 0.9rem;
  }
  .btn-move {
    padding: 3px 8px;
    font-size: 0.8rem;
  }
</style>

<!-- HEADER UTAMA -->
<div class="mb-4">
  <h4 class="fw-bold mb-1" style="color: #0a4d3a;">
    <i class="bi bi-broadcast me-2"></i> Teks Berjalan (Running Text)
  </h4>
  <div class="text-muted small">
    Kelola pesan pengumuman, himbauan tata tertib sholat, dan info rekening infaq yang berjalan di bagian bawah layar TV.
  </div>
</div>

<!-- ========================================================================= -->
<!-- KARTU 1: FORM TAMBAH / EDIT TEKS -->
<!-- ========================================================================= -->
<div class="card mb-4 shadow-sm">
  <div class="rt-card-header d-flex justify-content-between align-items-center">
    <span id="formTitle">
      <i class="bi bi-plus-square-fill text-success me-1"></i> Tambah Pesan Teks Berjalan
    </span>
    <span class="badge bg-light text-muted border small d-none" id="editBadge">Mode Edit</span>
  </div>
  <div class="card-body p-4">
    <form id="formRunningText">
      <input type="hidden" id="textId" name="id" value="0">

      <div class="mb-3">
        <label class="form-label fw-bold text-secondary">Isi Pesan Teks:</label>
        <textarea class="form-control" id="inputText" name="teks" rows="3" placeholder="Ketik pesan himbauan atau pengumuman masjid di sini..." required></textarea>
        <div class="form-text">
          <i class="bi bi-info-circle me-1"></i> Teks yang aktif akan otomatis digabung bergantian dengan tanda pemisah bintang (✦) di layar TV.
        </div>
      </div>

      <div class="d-flex gap-2 justify-content-end">
        <button type="button" id="btnCancelEdit" class="btn btn-outline-secondary px-4 d-none">
          Batal Edit
        </button>
        <button type="submit" id="btnSubmitText" class="btn btn-masjid px-4 shadow">
          <i class="bi bi-floppy-fill me-1"></i> Simpan Teks
        </button>
      </div>
    </form>
  </div>
</div>

<!-- ========================================================================= -->
<!-- KARTU 2: DAFTAR PESAN TEKS BERJALAN -->
<!-- ========================================================================= -->
<div class="card shadow-sm">
  <div class="rt-card-header d-flex justify-content-between align-items-center">
    <span>
      <i class="bi bi-card-text text-success me-1"></i> Daftar Pesan Teks Berjalan
    </span>
    <span class="badge bg-success-subtle text-success border border-success-subtle fw-semibold px-3 py-2" id="textCountBadge">
      Memuat daftar teks...
    </span>
  </div>

  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover table-striped table-rt mb-0">
        <thead>
          <tr>
            <th style="width: 120px;">Urutan</th>
            <th>Isi Pesan Teks Berjalan</th>
            <th style="width: 110px;">Status</th>
            <th style="width: 160px;">Aksi</th>
          </tr>
        </thead>
        <tbody id="tableTextBody">
          <tr>
            <td colspan="4" class="text-center py-4 text-muted">
              <span class="spinner-border spinner-border-sm me-2"></span> Memuat pesan teks...
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
  let textsList = [];

  const formRunningText = document.getElementById('formRunningText');
  const textId          = document.getElementById('textId');
  const inputText       = document.getElementById('inputText');
  const formTitle       = document.getElementById('formTitle');
  const editBadge       = document.getElementById('editBadge');
  const btnCancelEdit   = document.getElementById('btnCancelEdit');
  const btnSubmitText   = document.getElementById('btnSubmitText');

  // 1. Muat Daftar Teks dari API
  async function loadTexts() {
    try {
      const res = await fetch('api/manage_running_text.php?action=list');
      const json = await res.json();
      if (json.status === 'success') {
        textsList = json.data || [];
        renderTextTable();
      } else {
        document.getElementById('tableTextBody').innerHTML = `
          <tr><td colspan="4" class="text-center py-4 text-danger">${json.message}</td></tr>
        `;
      }
    } catch (err) {
      document.getElementById('tableTextBody').innerHTML = `
        <tr><td colspan="4" class="text-center py-4 text-danger">Gagal memuat daftar teks</td></tr>
      `;
    }
  }

  // 2. Render Tabel Teks Berjalan
  function renderTextTable() {
    const tbody = document.getElementById('tableTextBody');
    const badgeCount = document.getElementById('textCountBadge');
    badgeCount.textContent = `Total: ${textsList.length} Pesan`;

    if (textsList.length === 0) {
      tbody.innerHTML = `
        <tr>
          <td colspan="4" class="text-center py-4 text-muted">
            <i class="bi bi-chat-left-dots fs-3 d-block mb-1"></i>
            Belum ada pesan teks tersimpan. Gunakan form di atas untuk menambahkan.
          </td>
        </tr>
      `;
      return;
    }

    let html = '';
    const total = textsList.length;

    textsList.forEach((t, idx) => {
      const isFirst = (idx === 0);
      const isLast  = (idx === total - 1);
      const urutanNum = t.urutan || (idx + 1);

      html += `
        <tr>
          <!-- Urutan dengan Tombol Move Up / Move Down -->
          <td class="text-center">
            <div class="d-flex align-items-center justify-content-center gap-1">
              <button type="button" class="btn btn-sm btn-outline-success btn-move" data-id="${t.id}" data-dir="up" ${isFirst ? 'disabled' : ''} title="Geser Naik (Move Up)">
                <i class="bi bi-arrow-up"></i>
              </button>
              <button type="button" class="btn btn-sm btn-outline-success btn-move" data-id="${t.id}" data-dir="down" ${isLast ? 'disabled' : ''} title="Geser Turun (Move Down)">
                <i class="bi bi-arrow-down"></i>
              </button>
              <span class="badge bg-light text-dark border ms-1 fw-bold">#${urutanNum}</span>
            </div>
          </td>

          <!-- Isi Pesan Teks -->
          <td>
            <div class="text-dark fw-medium" style="line-height: 1.5;">${escapeHtml(t.teks)}</div>
          </td>

          <!-- Status Switch -->
          <td class="text-center">
            <div class="form-check form-switch d-flex justify-content-center">
              <input class="form-check-input btn-toggle-status" type="checkbox" role="switch" data-id="${t.id}" data-urutan="${urutanNum}" ${t.is_active == 1 ? 'checked' : ''} title="Aktif / Nonaktifkan">
            </div>
          </td>

          <!-- Tombol Aksi -->
          <td class="text-center">
            <button type="button" class="btn btn-sm btn-outline-primary btn-edit" data-id="${t.id}" title="Edit Teks">
              <i class="bi bi-pencil-square"></i> Edit
            </button>
            <button type="button" class="btn btn-sm btn-outline-danger btn-delete ms-1" data-id="${t.id}" title="Hapus Teks">
              <i class="bi bi-trash"></i> Hapus
            </button>
          </td>
        </tr>
      `;
    });

    tbody.innerHTML = html;
    attachTableEvents();
  }

  // 3. Pasang Event Handler untuk Tombol Tabel
  function attachTableEvents() {
    // Tombol Move Up / Move Down
    document.querySelectorAll('.btn-move').forEach(btn => {
      btn.addEventListener('click', async function() {
        const id = this.dataset.id;
        const dir = this.dataset.dir;
        try {
          const fd = new FormData();
          fd.append('action', 'move');
          fd.append('id', id);
          fd.append('direction', dir);

          const res = await fetch('api/manage_running_text.php', { method: 'POST', body: fd });
          const json = await res.json();
          if (json.status === 'success') {
            loadTexts();
          } else {
            showAlert('danger', json.message);
          }
        } catch (err) {
          showAlert('danger', 'Gagal memindahkan urutan teks');
        }
      });
    });

    // Toggle Status Aktif / Nonaktif
    document.querySelectorAll('.btn-toggle-status').forEach(chk => {
      chk.addEventListener('change', async function() {
        const id = this.dataset.id;
        const urutan = this.dataset.urutan || '';
        const isChecked = this.checked;

        try {
          const fd = new FormData();
          fd.append('action', 'toggle');
          fd.append('id', id);

          const res = await fetch('api/manage_running_text.php', { method: 'POST', body: fd });
          const json = await res.json();
          if (json.status === 'success') {
            const statusText = isChecked ? 'diaktifkan' : 'dinonaktifkan';
            showAlert('success', `Teks #${urutan} ${statusText}`);
          } else {
            showAlert('danger', json.message);
            this.checked = !this.checked;
          }
        } catch (err) {
          showAlert('danger', 'Gagal mengubah status teks');
          this.checked = !this.checked;
        }
      });
    });

    // Tombol Edit
    document.querySelectorAll('.btn-edit').forEach(btn => {
      btn.addEventListener('click', async function() {
        const id = this.dataset.id;
        try {
          const res = await fetch(`api/manage_running_text.php?action=get&id=${id}`);
          const json = await res.json();
          if (json.status === 'success') {
            const t = json.data;
            textId.value = t.id;
            inputText.value = t.teks;

            formTitle.innerHTML = `<i class="bi bi-pencil-square text-primary me-1"></i> Edit Pesan Teks #${t.urutan}`;
            editBadge.classList.remove('d-none');
            btnCancelEdit.classList.remove('d-none');
            btnSubmitText.innerHTML = '<i class="bi bi-floppy-fill me-1"></i> Perbarui Teks';

            inputText.focus();
            window.scrollTo({ top: 0, behavior: 'smooth' });
          } else {
            showAlert('danger', json.message);
          }
        } catch (err) {
          showAlert('danger', 'Gagal memuat pesan teks untuk diedit');
        }
      });
    });

    // Tombol Hapus
    document.querySelectorAll('.btn-delete').forEach(btn => {
      btn.addEventListener('click', async function() {
        if (!confirm('Apakah Anda yakin ingin menghapus pesan teks ini?')) return;
        const id = this.dataset.id;
        try {
          const fd = new FormData();
          fd.append('action', 'delete');
          fd.append('id', id);

          const res = await fetch('api/manage_running_text.php', { method: 'POST', body: fd });
          const json = await res.json();
          if (json.status === 'success') {
            showAlert('success', json.message);
            if (textId.value == id) {
              resetForm();
            }
            loadTexts();
          } else {
            showAlert('danger', json.message);
          }
        } catch (err) {
          showAlert('danger', 'Gagal menghapus pesan teks');
        }
      });
    });
  }

  // 4. Reset Form Tambah / Edit
  function resetForm() {
    textId.value = '0';
    formRunningText.reset();
    formTitle.innerHTML = '<i class="bi bi-plus-square-fill text-success me-1"></i> Tambah Pesan Teks Berjalan';
    editBadge.classList.add('d-none');
    btnCancelEdit.classList.add('d-none');
    btnSubmitText.innerHTML = '<i class="bi bi-floppy-fill me-1"></i> Simpan Teks';
  }

  btnCancelEdit.addEventListener('click', resetForm);

  // 5. Submit Form Tambah / Edit Teks
  formRunningText.addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = btnSubmitText;
    const oldHtml = btn.innerHTML;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...';
    btn.disabled = true;

    try {
      const formData = new FormData(this);
      formData.append('action', 'save');

      const res = await fetch('api/manage_running_text.php', {
        method: 'POST',
        body: formData
      });
      const json = await res.json();

      if (json.status === 'success') {
        showAlert('success', json.message);
        resetForm();
        loadTexts();
      } else {
        showAlert('danger', json.message || 'Gagal menyimpan teks');
      }
    } catch (err) {
      showAlert('danger', 'Terjadi kesalahan jaringan atau server');
    } finally {
      btn.innerHTML = oldHtml;
      btn.disabled = false;
    }
  });

  function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/[&<>"']/g, function(m) {
      return {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
      }[m];
    });
  }

  // Muat data saat halaman dibuka
  window.addEventListener('DOMContentLoaded', () => {
    loadTexts();
  });
</script>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
