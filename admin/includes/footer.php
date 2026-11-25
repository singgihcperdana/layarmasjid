      </main>
    </div>
  </div>

  <!-- Container Notifikasi Growl (Pojok Kanan Atas) -->
  <div id="growlContainer" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 2000;"></div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Toggle Sidebar Mobile
    const sidebar = document.getElementById('sidebar');
    const sidebarBackdrop = document.getElementById('sidebarBackdrop');
    const sidebarToggle = document.getElementById('sidebarToggle');

    if (sidebarToggle) {
      sidebarToggle.addEventListener('click', () => {
        sidebar.classList.toggle('show');
        sidebarBackdrop.classList.toggle('show');
      });
    }

    if (sidebarBackdrop) {
      sidebarBackdrop.addEventListener('click', () => {
        sidebar.classList.remove('show');
        sidebarBackdrop.classList.remove('show');
      });
    }

    // Notifikasi Growl / Toast Pojok Kanan Atas (Auto-dismiss 5 detik)
    function showAlert(type, msg) {
      const container = document.getElementById('growlContainer');
      if (!container) return;

      const isSuccess = (type === 'success');
      const borderColor = isSuccess ? '#0a4d3a' : '#dc3545';
      const iconClass = isSuccess ? 'bi-check-circle-fill text-success' : 'bi-exclamation-triangle-fill text-danger';
      const titleText = isSuccess ? 'Berhasil' : 'Perhatian';

      const toastId = 'toast_' + Date.now() + '_' + Math.floor(Math.random() * 1000);
      const toastEl = document.createElement('div');
      toastEl.id = toastId;
      toastEl.className = 'toast align-items-center show shadow-lg bg-white border-0';
      toastEl.setAttribute('role', 'alert');
      toastEl.setAttribute('aria-live', 'assertive');
      toastEl.setAttribute('aria-atomic', 'true');
      toastEl.style.minWidth = '310px';
      toastEl.style.borderRadius = '10px';
      toastEl.style.borderLeft = `5px solid ${borderColor}`;
      toastEl.style.marginBottom = '10px';
      toastEl.style.transition = 'all 0.35s ease';

      toastEl.innerHTML = `
        <div class="toast-header bg-white border-0 pb-1">
          <i class="bi ${iconClass} fs-5 me-2"></i>
          <strong class="me-auto text-dark" style="font-size: 0.92rem;">${titleText}</strong>
          <small class="text-muted" style="font-size: 0.75rem;">Baru saja</small>
          <button type="button" class="btn-close ms-2" onclick="document.getElementById('${toastId}').remove()"></button>
        </div>
        <div class="toast-body pt-0 pb-3 text-secondary" style="font-size: 0.88rem; font-weight: 500;">
          ${msg}
        </div>
      `;

      container.appendChild(toastEl);

      // Auto dismiss dalam 5 detik (5000 ms)
      setTimeout(() => {
        toastEl.style.opacity = '0';
        toastEl.style.transform = 'translateY(-12px)';
        setTimeout(() => {
          if (toastEl.parentNode) {
            toastEl.remove();
          }
        }, 350);
      }, 5000);
    }

    // Salin ID Masjid ke Clipboard dengan Feedback
    const btnCopyMasjidId = document.getElementById('btnCopyMasjidId');
    if (btnCopyMasjidId) {
      btnCopyMasjidId.addEventListener('click', async function() {
        const masjidId = this.dataset.id;
        try {
          if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(masjidId);
          } else {
            const textArea = document.createElement('textarea');
            textArea.value = masjidId;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            textArea.remove();
          }

          const badgeText = document.getElementById('badgeMasjidIdText');
          const copyIcon = document.getElementById('copyIcon');
          if (badgeText) badgeText.textContent = `ID: ${masjidId} (Tersalin)`;
          if (copyIcon) copyIcon.className = 'bi bi-check-lg text-white ms-1';
          showAlert('success', `ID Masjid (${masjidId}) berhasil disalin ke clipboard!`);

          setTimeout(() => {
            if (badgeText) badgeText.textContent = `ID: ${masjidId}`;
            if (copyIcon) copyIcon.className = 'bi bi-copy ms-1';
          }, 1800);
        } catch (err) {
          showAlert('danger', 'Gagal menyalin ID Masjid.');
        }
      });
    }
  </script>
</body>
</html>
