'use strict';

function togglePwd(id) {
  const input = document.getElementById(id);
  if (!input) return;
  input.type = input.type === 'password' ? 'text' : 'password';
}

function toggleDark() {
  document.documentElement.classList.toggle('dark');
  const isDark = document.documentElement.classList.contains('dark');
  const icon = document.getElementById('darkIcon');
  if (icon) icon.textContent = isDark ? '☀️' : '🌙';
  document.cookie = 'ventguide_dark=' + (isDark ? '1' : '0') + ';path=/;max-age=31536000';
}

document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('[data-toggle-dark]').forEach(function (btn) {
    btn.addEventListener('click', toggleDark);
  });

  document.querySelectorAll('[data-toggle-pwd]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      togglePwd(btn.getAttribute('data-toggle-pwd') || '');
    });
  });

  document.querySelectorAll('[data-sidebar-toggle]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      document.querySelector('.admin-sidebar')?.classList.toggle('open');
    });
  });

  document.querySelectorAll('[data-confirm]').forEach(function (el) {
    el.addEventListener('click', function (e) {
      const message = el.getAttribute('data-confirm') || '';
      if (message && !window.confirm(message)) {
        e.preventDefault();
        e.stopPropagation();
      }
    });
  });

  document.querySelectorAll('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      const message = form.getAttribute('data-confirm') || '';
      if (message && !window.confirm(message)) {
        e.preventDefault();
      }
    });
  });
});
