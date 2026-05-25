/**
 * ED VentGuide Pro — Toast Notification System
 * ─────────────────────────────────────────────
 * Premium slide-in notifications with auto-dismiss,
 * progress bars, stacking, sound feedback, and swipe-to-dismiss.
 *
 * Usage:
 *   Toast.success('Case saved successfully.');
 *   Toast.danger('Security token expired.');
 *   Toast.warning('Please check your input.');
 *   Toast.info('Tip: You can swipe to dismiss.');
 *
 * Advanced:
 *   Toast.show({ type: 'success', message: 'Done!', duration: 8000, title: 'Custom Title' });
 */
(function () {
  'use strict';

  const DEFAULTS = {
    duration: 5000,       // ms — auto-dismiss after this time (0 = no auto-dismiss)
    maxToasts: 5,         // max visible toasts at once
    pauseOnHover: true,   // pause auto-dismiss on hover
    swipeToDismiss: true, // enable swipe gesture on mobile
    sound: false,         // play a subtle sound (disabled by default)
  };

  // ── Title & icon map per type ───────────────────────
  const TYPE_META = {
    success: { title: 'Success',   icon: '✓', emoji: '✅' },
    danger:  { title: 'Error',     icon: '✕', emoji: '❌' },
    warning: { title: 'Warning',   icon: '!', emoji: '⚠️' },
    info:    { title: 'Info',      icon: 'i', emoji: 'ℹ️' },
  };

  let container = null;

  /**
   * Ensure the toast container exists in the DOM.
   */
  function getContainer() {
    if (container && document.body.contains(container)) return container;
    container = document.createElement('div');
    container.className = 'toast-container';
    container.setAttribute('role', 'alert');
    container.setAttribute('aria-live', 'assertive');
    container.setAttribute('aria-atomic', 'true');
    document.body.appendChild(container);
    return container;
  }

  /**
   * Dismiss a toast with exit animation.
   */
  function dismiss(el) {
    if (!el || el.dataset.dismissing) return;
    el.dataset.dismissing = '1';
    el.classList.add('toast-exiting');

    // Clean up after animation
    const onEnd = () => {
      el.removeEventListener('animationend', onEnd);
      el.remove();
    };
    el.addEventListener('animationend', onEnd);

    // Fallback if animation doesn't fire
    setTimeout(() => { if (el.parentNode) el.remove(); }, 500);
  }

  /**
   * Create and show a toast notification.
   *
   * @param {Object} options
   * @param {string} options.type       - 'success' | 'danger' | 'warning' | 'info'
   * @param {string} options.message    - The notification text
   * @param {string} [options.title]    - Override the default title
   * @param {number} [options.duration] - Auto-dismiss time in ms (0 = persistent)
   */
  function show(options) {
    const type     = options.type || 'info';
    const meta     = TYPE_META[type] || TYPE_META.info;
    const message  = options.message || '';
    const title    = options.title || meta.title;
    const duration = typeof options.duration === 'number' ? options.duration : DEFAULTS.duration;

    const wrap = getContainer();

    // Enforce max toasts
    while (wrap.children.length >= DEFAULTS.maxToasts) {
      dismiss(wrap.firstElementChild);
    }

    // Build toast element
    const el = document.createElement('div');
    el.className = `toast toast-${type}`;
    el.setAttribute('role', 'status');
    el.setAttribute('tabindex', '0');

    el.innerHTML = `
      <div class="toast-icon">${meta.icon}</div>
      <div class="toast-body">
        <div class="toast-title">${escapeHtml(title)}</div>
        <div class="toast-message">${escapeHtml(message)}</div>
      </div>
      <button class="toast-close" aria-label="Dismiss notification">&times;</button>
      ${duration > 0 ? `
      <div class="toast-progress">
        <div class="toast-progress-bar" style="animation: toastProgress ${duration}ms linear forwards;"></div>
      </div>` : ''}
    `;

    // Close button
    el.querySelector('.toast-close').addEventListener('click', (e) => {
      e.stopPropagation();
      dismiss(el);
    });

    // Click to dismiss
    el.addEventListener('click', () => dismiss(el));

    // Keyboard: Enter/Space to dismiss
    el.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        dismiss(el);
      }
    });

    // Pause on hover
    if (DEFAULTS.pauseOnHover && duration > 0) {
      let timeoutId = null;
      let remaining = duration;
      let startTime = null;

      const startTimer = () => {
        startTime = Date.now();
        timeoutId = setTimeout(() => dismiss(el), remaining);
      };

      el.addEventListener('mouseenter', () => {
        if (timeoutId) {
          clearTimeout(timeoutId);
          remaining -= (Date.now() - startTime);
          if (remaining < 0) remaining = 0;
        }
      });

      el.addEventListener('mouseleave', () => {
        if (!el.dataset.dismissing && remaining > 0) {
          startTimer();
        }
      });

      startTimer();
    } else if (duration > 0) {
      setTimeout(() => dismiss(el), duration);
    }

    // Swipe to dismiss (mobile)
    if (DEFAULTS.swipeToDismiss) {
      let startX = 0;
      let startY = 0;
      let currentX = 0;
      let swiping = false;

      el.addEventListener('touchstart', (e) => {
        startX = e.touches[0].clientX;
        startY = e.touches[0].clientY;
        currentX = 0;
        swiping = false;
      }, { passive: true });

      el.addEventListener('touchmove', (e) => {
        const dx = e.touches[0].clientX - startX;
        const dy = e.touches[0].clientY - startY;

        // Only swipe horizontally
        if (!swiping && Math.abs(dx) > Math.abs(dy) && Math.abs(dx) > 10) {
          swiping = true;
        }

        if (swiping) {
          currentX = dx;
          el.style.transform = `translateX(${dx}px)`;
          el.style.opacity = Math.max(0, 1 - Math.abs(dx) / 200);
          el.style.transition = 'none';
        }
      }, { passive: true });

      el.addEventListener('touchend', () => {
        if (swiping && Math.abs(currentX) > 80) {
          dismiss(el);
        } else if (swiping) {
          el.style.transform = '';
          el.style.opacity = '';
          el.style.transition = '';
        }
        swiping = false;
      }, { passive: true });
    }

    // Add to container
    wrap.appendChild(el);

    // Sound feedback (optional)
    if (DEFAULTS.sound && type === 'danger') {
      try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.type = 'sine';
        osc.frequency.value = 440;
        gain.gain.value = 0.05;
        osc.start();
        osc.stop(ctx.currentTime + 0.08);
      } catch (_) { /* ignore audio errors */ }
    }

    return el;
  }

  /**
   * Escape HTML to prevent XSS in toast messages.
   */
  function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
  }

  // ── Public API ──────────────────────────────────────
  const Toast = {
    show,
    success: (message, opts) => show({ type: 'success', message, ...opts }),
    danger:  (message, opts) => show({ type: 'danger',  message, ...opts }),
    warning: (message, opts) => show({ type: 'warning', message, ...opts }),
    info:    (message, opts) => show({ type: 'info',    message, ...opts }),
    dismiss,
    dismissAll: () => {
      const wrap = getContainer();
      [...wrap.children].forEach(dismiss);
    },
  };

  // Expose globally
  window.Toast = Toast;

  // ── Auto-fire toasts from PHP flash messages ────────
  // render_flashes() now outputs a hidden JSON script tag
  // that we parse here to show toasts automatically.
  document.addEventListener('DOMContentLoaded', () => {
    const dataEl = document.getElementById('toast-flash-data');
    if (!dataEl) return;

    try {
      const flashes = JSON.parse(dataEl.textContent);
      if (!Array.isArray(flashes)) return;

      flashes.forEach((f, i) => {
        // Stagger toast appearance for visual delight
        setTimeout(() => {
          Toast.show({
            type: f.type || 'info',
            message: f.message || '',
          });
        }, i * 150);
      });
    } catch (_) { /* ignore parse errors */ }
  });
})();
