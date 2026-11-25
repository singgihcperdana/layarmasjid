<?php
/**
 * Halaman Pengelolaan Konten Slide Layar TV Masjid
 * File: slides.php (URL: /slides)
 */
$page_title   = "Konten Slide";
$current_page = "slides";

require_once __DIR__ . '/includes/header.php';
?>

<style>
  .slide-card-header {
    background: #ffffff;
    border-bottom: 1px solid rgba(10, 77, 58, 0.1);
    font-weight: 700;
    color: #0a4d3a;
    padding: 16px 20px;
    border-top-left-radius: 14px !important;
    border-top-right-radius: 14px !important;
  }
  .table-slides th {
    background-color: #0a4d3a !important;
    color: #ffffff !important;
    text-align: center;
    font-weight: 600;
    padding: 11px 8px;
    font-size: 0.88rem;
    vertical-align: middle;
    border-bottom: 2px solid #d4af37 !important;
  }
  .table-slides td {
    vertical-align: middle;
    padding: 12px 10px;
    font-size: 0.9rem;
  }
  .thumb-preview {
    width: 80px;
    height: 52px;
    object-fit: cover;
    border-radius: 6px;
    border: 1px solid #ced4da;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  }
  .arabic-textarea {
    font-family: 'Amiri', 'Traditional Arabic', serif;
    font-size: 1.25rem;
    line-height: 1.8;
  }
  .btn-move {
    padding: 3px 8px;
    font-size: 0.8rem;
  }
  .badge-type {
    font-size: 0.78rem;
    padding: 4px 8px;
    border-radius: 6px;
    font-weight: 700;
  }
  #infoEditor {
    min-height: 140px;
    max-height: 280px;
    overflow-y: auto;
    line-height: 1.6;
    background-color: #ffffff;
    border-color: #dee2e6;
  }
  #infoEditor:focus {
    border-color: #0a4d3a;
    box-shadow: 0 0 0 0.25rem rgba(10, 77, 58, 0.15);
    outline: none;
  }
  #infoEditor:empty:before {
    content: attr(placeholder);
    color: #94a3b8;
    pointer-events: none;
    display: block;
  }
  .editor-toolbar {
    background-color: #f8fafc;
    border-color: #dee2e6;
  }
  .editor-btn {
    width: 34px;
    height: 30px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.88rem;
    color: #475569;
  }
  .editor-btn:hover,
  .editor-btn.active {
    background-color: #0a4d3a !important;
    border-color: #0a4d3a !important;
    color: #ffffff !important;
  }
  .editor-btn.active {
    box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.25);
  }
</style>

<!-- HEADER UTAMA & TOMBOL TAMBAH SLIDE -->
<div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4">
  <div>
    <h4 class="fw-bold mb-1" style="color: #0a4d3a;">
      <i class="bi bi-images me-2"></i> Konten Slide Layar TV
    </h4>
    <div class="text-muted small">
      Kelola materi slide gambar, informasi pengumuman masjid, serta kutipan dalil Al-Qur'an dan Hadis.
    </div>
  </div>
  <button type="button" id="btnToggleForm" class="btn btn-masjid shadow-sm px-3 py-2">
    <i class="bi bi-plus-circle-fill me-1"></i> Tambah Slide Baru
  </button>
</div>

<!-- ========================================================================= -->
<!-- FORM INPUT / EDIT SLIDE (COLLAPSIBLE) -->
<!-- ========================================================================= -->
<div id="slideFormContainer" class="card mb-4 shadow-sm d-none">
  <div class="slide-card-header d-flex justify-content-between align-items-center">
    <span id="formTitle">
      <i class="bi bi-plus-square-fill text-success me-1"></i> Form Tambah Slide Baru
    </span>
    <button type="button" class="btn-close" id="btnCloseForm" aria-label="Tutup"></button>
  </div>
  <div class="card-body p-4">
    <form id="formSlide" enctype="multipart/form-data">
      <input type="hidden" id="slideId" name="id" value="0">

      <!-- DROPDOWN PILIHAN TIPE SLIDE -->
      <div class="row mb-3">
        <div class="col-md-6">
          <label class="form-label fw-bold" style="color: #0a4d3a;">
            Pilihan Tipe Slide:
          </label>
          <select id="slideTipe" name="tipe" class="form-select form-select-lg fw-semibold" style="border-color: #0a4d3a;">
            <option value="image">Image</option>
            <option value="info">Informasi</option>
            <option value="quranHadist">Ayat Alquran/Hadis</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label fw-bold text-secondary">Durasi Tampil (Detik):</label>
          <div class="input-group">
            <input type="number" id="slideDurasi" name="durasi" class="form-control text-center fw-bold" min="5" max="30" value="15" required>
            <span class="input-group-text">Detik</span>
          </div>
          <div class="form-text text-center text-muted" style="font-size: 0.78rem;">Maksimal 30 detik</div>
        </div>
        <div class="col-md-3 d-flex flex-column justify-content-center">
          <label class="form-label fw-bold text-secondary mb-1">Status Penayangan:</label>
          <div class="form-check form-switch mt-1">
            <input class="form-check-input" type="checkbox" id="slideIsActive" name="is_active" value="1" checked style="width: 2.5em; height: 1.3em;">
            <label class="form-check-label fw-semibold ms-2" for="slideIsActive" id="labelIsActive">Aktif di Layar TV</label>
          </div>
        </div>
      </div>

      <hr class="my-4">

      <!-- ========================================== -->
      <!-- SECTION KHUSUS: GAMBAR -->
      <!-- ========================================== -->
      <div id="sectionImage" class="type-section">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label fw-semibold">Pilih File Gambar:</label>
            <input type="file" class="form-control" id="inputImageFile" name="image_file" accept="image/jpeg,image/png,image/webp">
            <div class="form-text">Format: JPG, PNG, atau WEBP. Rekomendasi rasio lanskap 16:9 (1920x1080 px).</div>
            
            <div class="mt-3">
              <label class="form-label fw-semibold">Judul / Keterangan Singkat (Opsional):</label>
              <input type="text" class="form-control" id="imageJudul" name="judul" placeholder="Contoh: Kajian Rutin Ahad Subuh">
            </div>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Preview Gambar:</label>
            <div class="p-2 border rounded bg-light text-center" style="min-height: 140px;">
              <img id="imagePreview" src="" alt="Preview Gambar" class="img-fluid rounded d-none" style="max-height: 180px; object-fit: contain;">
              <div id="imagePlaceholder" class="text-muted p-4">
                <i class="bi bi-image fs-1 d-block mb-1 text-secondary opacity-50"></i>
                Belum ada gambar dipilih
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ========================================== -->
      <!-- SECTION KHUSUS: INFORMASI / PENGUMUMAN -->
      <!-- ========================================== -->
      <div id="sectionInfo" class="type-section d-none">
        <div class="mb-3">
          <label class="form-label fw-semibold">Judul Pengumuman:</label>
          <input type="text" class="form-control" id="infoJudul" name="judul_info" placeholder="Contoh: Kerja Bakti & Pembersihan Karpet Masjid">
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">Isi Pesan / Pengumuman:</label>
          
          <!-- Mini Rich Text Toolbar -->
          <div class="editor-toolbar d-flex flex-wrap align-items-center gap-1 p-2 border border-bottom-0 rounded-top">
            <!-- Format Teks -->
            <button type="button" class="btn btn-sm btn-outline-secondary editor-btn" data-cmd="bold" title="Tebal (Bold)"><i class="bi bi-type-bold"></i></button>
            <button type="button" class="btn btn-sm btn-outline-secondary editor-btn" data-cmd="italic" title="Miring (Italic)"><i class="bi bi-type-italic"></i></button>
            <button type="button" class="btn btn-sm btn-outline-secondary editor-btn" data-cmd="underline" title="Garis Bawah (Underline)"><i class="bi bi-type-underline"></i></button>
            <button type="button" class="btn btn-sm btn-outline-secondary editor-btn" data-cmd="strikeThrough" title="Coret (Strikethrough)"><i class="bi bi-type-strikethrough"></i></button>
            
            <span class="vr mx-1 my-auto" style="height: 18px;"></span>

            <!-- Perataan / Alignment -->
            <button type="button" class="btn btn-sm btn-outline-secondary editor-btn" data-cmd="justifyLeft" title="Rata Kiri"><i class="bi bi-text-left"></i></button>
            <button type="button" class="btn btn-sm btn-outline-secondary editor-btn" data-cmd="justifyCenter" title="Rata Tengah"><i class="bi bi-text-center"></i></button>
            <button type="button" class="btn btn-sm btn-outline-secondary editor-btn" data-cmd="justifyRight" title="Rata Kanan"><i class="bi bi-text-right"></i></button>

            <span class="vr mx-1 my-auto" style="height: 18px;"></span>

            <!-- List -->
            <button type="button" class="btn btn-sm btn-outline-secondary editor-btn" data-cmd="insertUnorderedList" title="Daftar Poin (Bullet List)"><i class="bi bi-list-ul"></i></button>
          </div>

          <!-- Contenteditable Visual Editor -->
          <div id="infoEditor" class="form-control rounded-bottom rounded-0" contenteditable="true" placeholder="Ketik pesan informasi pengumuman di sini..."></div>
          
          <!-- Hidden textarea for form submission -->
          <textarea id="infoKonten" name="konten" class="d-none"></textarea>

          <div class="form-text mt-1">Gunakan toolbar di atas untuk mempertegas teks (tebal, miring, garis bawah, coret), mengatur rata tulisan, atau membuat daftar poin.</div>
        </div>
      </div>

      <!-- ========================================== -->
      <!-- SECTION KHUSUS: AYAT AL-QUR'AN / HADITS -->
      <!-- ========================================== -->
      <div id="sectionQuran" class="type-section d-none">
        <div class="mb-3">
          <label class="form-label fw-semibold">Teks Kaligrafi / Lafadz Arab:</label>
          <textarea class="form-control arabic-textarea text-end" id="quranAyat" name="ayat" rows="3" dir="rtl" placeholder="يَا أَيُّهَا الَّذِينَ آمَنُوا كُتِبَ عَلَيْكُمُ الصِّيَامُ..."></textarea>
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">Terjemahan / Arti Bahasa Indonesia:</label>
          <textarea class="form-control" id="quranArti" name="arti" rows="3" placeholder="&quot;Wahai orang-orang yang beriman, diwajibkan atas kamu berpuasa sebagaimana diwajibkan atas orang-orang sebelum kamu agar kamu bertakwa.&quot;"></textarea>
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">Sumber / Nama Surah & Hadits:</label>
          <input type="text" class="form-control" id="quranSurah" name="surah" placeholder="Contoh: QS. Al-Baqarah: 183 atau HR. Bukhari No. 12">
        </div>
      </div>

      <!-- TOMBOL SIMPAN & BATAL -->
      <div class="d-flex gap-2 justify-content-end mt-4 pt-3 border-top">
        <button type="button" id="btnCancelForm" class="btn btn-outline-secondary px-4">
          Batal
        </button>
        <button type="submit" id="btnSubmitSlide" class="btn btn-masjid px-4 shadow">
          <i class="bi bi-floppy-fill me-1"></i> Simpan Slide
        </button>
      </div>

    </form>
  </div>
</div>

<!-- ========================================================================= -->
<!-- DAFTAR SLIDE AKTIF DI LAYAR TV -->
<!-- ========================================================================= -->
<div class="card shadow-sm">
  <div class="slide-card-header d-flex justify-content-between align-items-center">
    <span>
      <i class="bi bi-collection-play-fill text-success me-1"></i> Daftar Konten Slide di Layar TV
    </span>
    <span class="badge bg-success-subtle text-success border border-success-subtle fw-semibold px-3 py-2" id="slideCountBadge">
      Memuat slide...
    </span>
  </div>

  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover table-striped table-slides mb-0">
        <thead>
          <tr>
            <th style="width: 110px;">Urutan</th>
            <th style="width: 140px;">Tipe Slide</th>
            <th>Preview / Ringkasan Konten</th>
            <th style="width: 100px;">Durasi</th>
            <th style="width: 110px;">Status</th>
            <th style="width: 160px;">Aksi</th>
          </tr>
        </thead>
        <tbody id="tableSlidesBody">
          <tr>
            <td colspan="6" class="text-center py-4 text-muted">
              <span class="spinner-border spinner-border-sm me-2"></span> Memuat daftar slide...
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
  let slidesList = [];

  // 1. Tampilkan / Sembunyikan Form
  const slideFormContainer = document.getElementById('slideFormContainer');
  const btnToggleForm = document.getElementById('btnToggleForm');
  const btnCloseForm = document.getElementById('btnCloseForm');
  const btnCancelForm = document.getElementById('btnCancelForm');
  const formTitle = document.getElementById('formTitle');

  function openForm(isEdit = false) {
    slideFormContainer.classList.remove('d-none');
    if (!isEdit) {
      resetForm();
      formTitle.innerHTML = '<i class="bi bi-plus-square-fill text-success me-1"></i> Form Tambah Slide Baru';
    }
    slideFormContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  function closeForm() {
    slideFormContainer.classList.add('d-none');
    resetForm();
  }

  btnToggleForm.addEventListener('click', () => openForm(false));
  btnCloseForm.addEventListener('click', closeForm);
  btnCancelForm.addEventListener('click', closeForm);

  // 2. Dropdown Tipe Slide (Switch Section)
  const slideTipe = document.getElementById('slideTipe');
  const sectionImage = document.getElementById('sectionImage');
  const sectionInfo = document.getElementById('sectionInfo');
  const sectionQuran = document.getElementById('sectionQuran');

  function updateTypeSections() {
    const val = slideTipe.value;
    sectionImage.classList.add('d-none');
    sectionInfo.classList.add('d-none');
    sectionQuran.classList.add('d-none');

    if (val === 'image') {
      sectionImage.classList.remove('d-none');
    } else if (val === 'info') {
      sectionInfo.classList.remove('d-none');
    } else if (val === 'quranHadist') {
      sectionQuran.classList.remove('d-none');
    }
  }

  slideTipe.addEventListener('change', updateTypeSections);

  // 3. Preview Gambar Saat Dipilih
  const inputImageFile = document.getElementById('inputImageFile');
  const imagePreview = document.getElementById('imagePreview');
  const imagePlaceholder = document.getElementById('imagePlaceholder');

  inputImageFile.addEventListener('change', function() {
    const file = this.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = function(e) {
        imagePreview.src = e.target.result;
        imagePreview.classList.remove('d-none');
        imagePlaceholder.classList.add('d-none');
      };
      reader.readAsDataURL(file);
    }
  });

  // 4. Reset Form
  function resetForm() {
    document.getElementById('slideId').value = '0';
    document.getElementById('formSlide').reset();
    document.getElementById('slideDurasi').value = '15';
    document.getElementById('slideIsActive').checked = true;
    document.getElementById('infoEditor').innerHTML = '';
    document.getElementById('infoKonten').value = '';
    document.querySelectorAll('.editor-btn').forEach(b => b.classList.remove('active'));
    imagePreview.src = '';
    imagePreview.classList.add('d-none');
    imagePlaceholder.classList.remove('d-none');
    updateTypeSections();
  }

  // 5. Muat Daftar Slide dari API
  async function loadSlides() {
    try {
      const res = await fetch('api/manage_slides.php?action=list');
      const json = await res.json();
      if (json.status === 'success') {
        slidesList = json.data || [];
        renderSlidesTable();
      } else {
        document.getElementById('tableSlidesBody').innerHTML = `
          <tr><td colspan="6" class="text-center py-4 text-danger">${json.message}</td></tr>
        `;
      }
    } catch (err) {
      document.getElementById('tableSlidesBody').innerHTML = `
        <tr><td colspan="6" class="text-center py-4 text-danger">Gagal memuat daftar slide</td></tr>
      `;
    }
  }

  // 6. Render Tabel Slide dengan Tombol Move Up / Move Down
  function renderSlidesTable() {
    const tbody = document.getElementById('tableSlidesBody');
    const badgeCount = document.getElementById('slideCountBadge');
    badgeCount.textContent = `Total: ${slidesList.length} Slide`;

    if (slidesList.length === 0) {
      tbody.innerHTML = `
        <tr>
          <td colspan="6" class="text-center py-4 text-muted">
            <i class="bi bi-inbox fs-3 d-block mb-1"></i>
            Belum ada slide tersimpan. Klik tombol <strong>Tambah Slide Baru</strong> di atas untuk menambahkan.
          </td>
        </tr>
      `;
      return;
    }

    let html = '';
    const total = slidesList.length;

    slidesList.forEach((s, idx) => {
      // Badge Tipe
      let typeBadge = '';
      let previewContent = '';

      if (s.tipe === 'image') {
        typeBadge = '<span class="badge-type bg-primary-subtle text-primary border border-primary-subtle"><i class="bi bi-image me-1"></i> Image</span>';
        const imgUrl = s.konten || '';
        const fileName = s.konten ? s.konten.split('/').pop() : 'image.jpg';
        previewContent = `
          <div class="d-flex align-items-center gap-2">
            <img src="${imgUrl}" class="thumb-preview" onerror="this.src='display/assets/img/contoh_slide/info-1777620184.png'">
            <div>
              ${s.judul ? `<div class="fw-bold text-dark">${escapeHtml(s.judul)}</div>` : ''}
              <div class="text-secondary small text-truncate" style="max-width: 280px;">${escapeHtml(fileName)}</div>
            </div>
          </div>
        `;
      } else if (s.tipe === 'info') {
        typeBadge = '<span class="badge-type bg-warning-subtle text-warning-emphasis border border-warning-subtle"><i class="bi bi-info-circle me-1"></i> Info</span>';
        const cleanText = (s.konten || '').replace(/<[^>]*>?/gm, ' ').replace(/\s+/g, ' ').trim();
        previewContent = `
          <div>
            <div class="fw-bold text-dark">${escapeHtml(s.judul || 'Pengumuman')}</div>
            <div class="small text-muted text-truncate" style="max-width: 380px;">${escapeHtml(cleanText || '')}</div>
          </div>
        `;
      } else { // quranHadist
        typeBadge = '<span class="badge-type bg-success-subtle text-success border border-success-subtle"><i class="bi bi-book me-1"></i> Quran/Hadits</span>';
        previewContent = `
          <div>
            <div class="fw-bold text-dark text-truncate" style="max-width: 380px;" dir="rtl">${escapeHtml(s.ayat || '')}</div>
            <div class="small text-muted text-truncate" style="max-width: 380px;">"${escapeHtml(s.arti || '')}"</div>
            <small class="text-success fw-semibold">✦ ${escapeHtml(s.surah || '')}</small>
          </div>
        `;
      }

      // Urutan + Tombol Move Up / Move Down
      const isFirst = (idx === 0);
      const isLast  = (idx === total - 1);

      html += `
        <tr>
          <!-- Urutan dengan Tombol Move Up / Move Down -->
          <td class="text-center">
            <div class="d-flex align-items-center justify-content-center gap-1">
              <button type="button" class="btn btn-sm btn-outline-success btn-move" data-id="${s.id}" data-dir="up" ${isFirst ? 'disabled' : ''} title="Geser Naik (Move Up)">
                <i class="bi bi-arrow-up"></i>
              </button>
              <button type="button" class="btn btn-sm btn-outline-success btn-move" data-id="${s.id}" data-dir="down" ${isLast ? 'disabled' : ''} title="Geser Turun (Move Down)">
                <i class="bi bi-arrow-down"></i>
              </button>
              <span class="badge bg-light text-dark border ms-1 fw-bold">#${s.urutan || (idx + 1)}</span>
            </div>
          </td>

          <!-- Tipe Slide -->
          <td class="text-center">${typeBadge}</td>

          <!-- Preview Konten -->
          <td>${previewContent}</td>

          <!-- Durasi -->
          <td class="text-center fw-semibold">${s.durasi || 15} dt</td>

          <!-- Status Switch -->
          <td class="text-center">
            <div class="form-check form-switch d-flex justify-content-center">
              <input class="form-check-input btn-toggle-status" type="checkbox" role="switch" data-id="${s.id}" data-urutan="${s.urutan || (idx + 1)}" ${s.is_active == 1 ? 'checked' : ''} title="Aktif / Nonaktifkan">
            </div>
          </td>

          <!-- Tombol Aksi -->
          <td class="text-center">
            <button type="button" class="btn btn-sm btn-outline-primary btn-edit" data-id="${s.id}" title="Edit Slide">
              <i class="bi bi-pencil-square"></i> Edit
            </button>
            <button type="button" class="btn btn-sm btn-outline-danger btn-delete ms-1" data-id="${s.id}" title="Hapus Slide">
              <i class="bi bi-trash"></i> Hapus
            </button>
          </td>
        </tr>
      `;
    });

    tbody.innerHTML = html;
    attachTableEvents();
  }

  // 7. Event Handler Tombol di Tabel
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

          const res = await fetch('api/manage_slides.php', { method: 'POST', body: fd });
          const json = await res.json();
          if (json.status === 'success') {
            loadSlides();
          } else {
            showAlert('danger', json.message);
          }
        } catch (err) {
          showAlert('danger', 'Gagal memindahkan posisi slide');
        }
      });
    });

    // Toggle Status
    document.querySelectorAll('.btn-toggle-status').forEach(chk => {
      chk.addEventListener('change', async function() {
        const id = this.dataset.id;
        const urutan = this.dataset.urutan || '';
        const isChecked = this.checked;
        try {
          const fd = new FormData();
          fd.append('action', 'toggle');
          fd.append('id', id);

          const res = await fetch('api/manage_slides.php', { method: 'POST', body: fd });
          const json = await res.json();
          if (json.status === 'success') {
            const statusText = isChecked ? 'diaktifkan' : 'dinonaktifkan';
            showAlert('success', `Slide ${urutan} ${statusText}`);
          } else {
            showAlert('danger', json.message);
            this.checked = !this.checked;
          }
        } catch (err) {
          showAlert('danger', 'Gagal mengubah status slide');
          this.checked = !this.checked;
        }
      });
    });

    // Tombol Edit
    document.querySelectorAll('.btn-edit').forEach(btn => {
      btn.addEventListener('click', async function() {
        const id = this.dataset.id;
        try {
          const res = await fetch(`api/manage_slides.php?action=get&id=${id}`);
          const json = await res.json();
          if (json.status === 'success') {
            const s = json.data;
            openForm(true);

            formTitle.innerHTML = `<i class="bi bi-pencil-square text-primary me-1"></i> Form Edit Slide #${s.urutan}`;
            document.getElementById('slideId').value = s.id;
            document.getElementById('slideTipe').value = s.tipe;
            document.getElementById('slideDurasi').value = s.durasi || 15;
            document.getElementById('slideIsActive').checked = (s.is_active == 1);

            updateTypeSections();

            if (s.tipe === 'image') {
              document.getElementById('imageJudul').value = s.judul || '';
              if (s.konten) {
                imagePreview.src = s.konten;
                imagePreview.classList.remove('d-none');
                imagePlaceholder.classList.add('d-none');
              }
            } else if (s.tipe === 'info') {
              document.getElementById('infoJudul').value = s.judul || '';
              document.getElementById('infoEditor').innerHTML = s.konten || '';
              document.getElementById('infoKonten').value = s.konten || '';
            } else if (s.tipe === 'quranHadist') {
              document.getElementById('quranAyat').value = s.ayat || '';
              document.getElementById('quranArti').value = s.arti || '';
              document.getElementById('quranSurah').value = s.surah || '';
            }
          }
        } catch (err) {
          showAlert('danger', 'Gagal memuat data slide untuk diedit');
        }
      });
    });

    // Tombol Hapus
    document.querySelectorAll('.btn-delete').forEach(btn => {
      btn.addEventListener('click', async function() {
        if (!confirm('Apakah Anda yakin ingin menghapus slide ini?')) return;
        const id = this.dataset.id;
        try {
          const fd = new FormData();
          fd.append('action', 'delete');
          fd.append('id', id);

          const res = await fetch('api/manage_slides.php', { method: 'POST', body: fd });
          const json = await res.json();
          if (json.status === 'success') {
            showAlert('success', json.message);
            loadSlides();
          } else {
            showAlert('danger', json.message);
          }
        } catch (err) {
          showAlert('danger', 'Gagal menghapus slide');
        }
      });
    });
  }

  // 8. Submit Form Tambah / Edit Slide
  document.getElementById('formSlide').addEventListener('submit', async function(e) {
    e.preventDefault();
    const durasi = parseInt(document.getElementById('slideDurasi').value) || 0;
    if (durasi > 30) {
      showAlert('danger', 'Durasi tampil slide maksimal 30 detik');
      document.getElementById('slideDurasi').focus();
      return;
    }
    if (durasi < 5) {
      showAlert('danger', 'Durasi tampil slide minimal 5 detik');
      document.getElementById('slideDurasi').focus();
      return;
    }

    const btn = document.getElementById('btnSubmitSlide');
    const oldHtml = btn.innerHTML;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...';
    btn.disabled = true;

    try {
      const formData = new FormData(this);
      formData.append('action', 'save');

      // Normalisasi judul & konten sesuai tipe
      const tipe = slideTipe.value;
      if (tipe === 'info') {
        const editorHtml = document.getElementById('infoEditor').innerHTML.trim();
        const cleanHtml = (editorHtml === '<br>' || editorHtml === '<p><br></p>') ? '' : editorHtml;
        document.getElementById('infoKonten').value = cleanHtml;
        formData.set('judul', document.getElementById('infoJudul').value);
        formData.set('konten', cleanHtml);
      } else if (tipe === 'image') {
        formData.set('judul', document.getElementById('imageJudul').value);
      }

      const res = await fetch('api/manage_slides.php', {
        method: 'POST',
        body: formData
      });
      const json = await res.json();

      if (json.status === 'success') {
        showAlert('success', json.message);
        closeForm();
        loadSlides();
      } else {
        showAlert('danger', json.message || 'Gagal menyimpan slide');
      }
    } catch (err) {
      showAlert('danger', 'Terjadi kesalahan saat menyimpan slide');
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

  // 9. Event Listener Mini Rich Text Editor Toolbar (Native ExecCommand + State Detection)
  function updateToolbarState() {
    const editor = document.getElementById('infoEditor');
    const sel = window.getSelection();
    if (!sel || !sel.anchorNode || (!editor.contains(sel.anchorNode) && sel.anchorNode !== editor)) {
      return;
    }

    document.querySelectorAll('.editor-btn').forEach(btn => {
      const cmd = btn.dataset.cmd;
      try {
        if (document.queryCommandState(cmd)) {
          btn.classList.add('active');
        } else {
          btn.classList.remove('active');
        }
      } catch (e) {}
    });
  }

  document.querySelectorAll('.editor-btn').forEach(btn => {
    btn.addEventListener('mousedown', function(e) {
      e.preventDefault(); // Mencegah hilangnya seleksi teks pada contenteditable
      const cmd = this.dataset.cmd;
      document.execCommand(cmd, false, null);
      document.getElementById('infoEditor').focus();
      setTimeout(updateToolbarState, 15);
    });
  });

  const infoEditorEl = document.getElementById('infoEditor');
  ['keyup', 'mouseup', 'input', 'focus'].forEach(evt => {
    infoEditorEl.addEventListener(evt, updateToolbarState);
  });
  document.addEventListener('selectionchange', () => {
    if (document.activeElement === infoEditorEl) {
      updateToolbarState();
    }
  });

  // Muat pertama kali
  window.addEventListener('DOMContentLoaded', () => {
    loadSlides();
    updateTypeSections();
  });
</script>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
