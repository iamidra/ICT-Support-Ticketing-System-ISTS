/**
 * ISTS - Client-Side Interactive Controller
 * Enhanced with Password Visibility Toggle, Mobile Nav & Table Search
 */

document.addEventListener('DOMContentLoaded', () => {
  // 1. Password Visibility Eye Toggle Handler
  document.addEventListener('click', (e) => {
    const toggleBtn = e.target.closest('.password-toggle-btn');
    if (!toggleBtn) return;

    e.preventDefault();
    const wrapper = toggleBtn.closest('.password-wrapper');
    if (!wrapper) return;

    const input = wrapper.querySelector('input');
    if (!input) return;

    const isPassword = input.type === 'password';
    input.type = isPassword ? 'text' : 'password';

    const eyeOpen = toggleBtn.querySelector('.eye-open-icon');
    const eyeClosed = toggleBtn.querySelector('.eye-closed-icon');

    if (eyeOpen && eyeClosed) {
      if (isPassword) {
        eyeOpen.style.display = 'none';
        eyeClosed.style.display = 'inline-block';
        toggleBtn.setAttribute('aria-label', 'Hide password');
      } else {
        eyeOpen.style.display = 'inline-block';
        eyeClosed.style.display = 'none';
        toggleBtn.setAttribute('aria-label', 'Show password');
      }
    }
  });

  // 2. Mobile Navigation Toggle
  const mobileNavToggle = document.getElementById('mobileNavToggle');
  const navContainer = document.getElementById('navContainer');
  if (mobileNavToggle && navContainer) {
    mobileNavToggle.addEventListener('click', () => {
      navContainer.classList.toggle('open');
    });
  }

  // 3. Live Table Search Filter
  const searchInputs = document.querySelectorAll('[data-table-search]');
  searchInputs.forEach(input => {
    const targetId = input.getAttribute('data-table-search');
    const table = document.getElementById(targetId);
    if (!table) return;

    input.addEventListener('input', () => {
      const query = input.value.toLowerCase().trim();
      const rows = table.querySelectorAll('tbody tr');

      rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        if (text.includes(query)) {
          row.style.display = '';
        } else {
          row.style.display = 'none';
        }
      });
    });
  });

  // 4. Status Filter Buttons
  const filterBtns = document.querySelectorAll('[data-status-filter]');
  filterBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      const status = btn.getAttribute('data-status-filter');
      const targetTableId = btn.getAttribute('data-target-table') || 'ticketsTable';
      const table = document.getElementById(targetTableId);
      if (!table) return;

      // Toggle active button style
      filterBtns.forEach(b => b.classList.remove('btn-primary'));
      filterBtns.forEach(b => b.classList.add('btn-secondary'));
      btn.classList.remove('btn-secondary');
      btn.classList.add('btn-primary');

      const rows = table.querySelectorAll('tbody tr');
      rows.forEach(row => {
        const rowStatus = row.getAttribute('data-status');
        if (status === 'all' || rowStatus === status) {
          row.style.display = '';
        } else {
          row.style.display = 'none';
        }
      });
    });
  });

  // 5. Copy Ticket Code to Clipboard
  const copyBtns = document.querySelectorAll('[data-copy-target]');
  copyBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      const targetSelector = btn.getAttribute('data-copy-target');
      const targetEl = document.querySelector(targetSelector);
      if (!targetEl) return;

      const text = targetEl.textContent.trim();
      navigator.clipboard.writeText(text).then(() => {
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<span style="color:#10b981; font-weight:700;">Copied!</span>';
        btn.classList.add('btn-copy-success');

        setTimeout(() => {
          btn.innerHTML = originalHtml;
          btn.classList.remove('btn-copy-success');
        }, 2000);
      }).catch(err => {
        console.error('Failed to copy code: ', err);
      });
    });
  });

  // 6. Auto-dismiss Alert Banners after 5 seconds
  const alerts = document.querySelectorAll('.alert-dismissible');
  alerts.forEach(alert => {
    setTimeout(() => {
      alert.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
      alert.style.opacity = '0';
      alert.style.transform = 'translateY(-6px)';
      setTimeout(() => alert.remove(), 400);
    }, 5000);
  });

  // 7. Action Confirmation Prompts
  const confirmElements = document.querySelectorAll('[data-confirm]');
  confirmElements.forEach(el => {
    el.addEventListener('click', (e) => {
      const msg = el.getAttribute('data-confirm') || 'Are you sure you want to perform this action?';
      if (!confirm(msg)) {
        e.preventDefault();
      }
    });
  });
});
