/**
 * Simpera - Navigasi & Transisi Halaman Halus
 * Ringan, performa tinggi, tanpa dependensi eksternal, menghormati prefers-reduced-motion.
 */
(function () {
    'use strict';

    // Periksa preferensi aksesibilitas pengguna
    const prefersReducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // Pastikan elemen progress bar tersedia di DOM
    let progressBar = document.getElementById('simpera-nav-progress');
    if (!progressBar && !prefersReducedMotion) {
        progressBar = document.createElement('div');
        progressBar.id = 'simpera-nav-progress';
        progressBar.setAttribute('aria-hidden', 'true');
        document.body.appendChild(progressBar);
    }

    let progressTimer = null;
    let progressStepTimers = [];

    function clearProgressTimers() {
        if (progressTimer) {
            clearTimeout(progressTimer);
            progressTimer = null;
        }
        progressStepTimers.forEach(function (timerId) {
            clearTimeout(timerId);
        });
        progressStepTimers = [];
    }

    function resetProgress() {
        clearProgressTimers();
        if (!progressBar) return;
        progressBar.className = '';
        progressBar.style.width = '0%';
    }

    function startProgress() {
        if (prefersReducedMotion || !progressBar) return;
        clearProgressTimers();

        // Tunda 120 ms: jika navigasi sangat cepat, progress bar tidak perlu muncul (mencegah flicker)
        progressTimer = setTimeout(function () {
            progressBar.className = 'is-active';
            progressBar.style.width = '25%';

            progressStepTimers.push(setTimeout(function () {
                if (progressBar.classList.contains('is-active')) {
                    progressBar.style.width = '65%';
                }
            }, 200));

            progressStepTimers.push(setTimeout(function () {
                if (progressBar.classList.contains('is-active')) {
                    progressBar.style.width = '85%';
                }
            }, 600));

            // Safety timeout: batalkan status jika navigasi terhenti atau respons server melebihi 8 detik
            progressStepTimers.push(setTimeout(function () {
                resetProgress();
            }, 8000));
        }, 120);
    }

    function completeProgress() {
        if (!progressBar) return;
        clearProgressTimers();
        progressBar.style.width = '100%';
        progressBar.classList.add('is-done');
        setTimeout(function () {
            resetProgress();
        }, 200);
    }

    // Periksa apakah tautan yang diklik merupakan navigasi internal aplikasi
    function isNavigableLink(link) {
        if (!link || !link.href) return false;
        if (link.target && link.target !== '_self') return false;
        if (link.hasAttribute('download')) return false;
        if (link.getAttribute('href').startsWith('#')) return false;
        if (link.getAttribute('href').startsWith('javascript:')) return false;

        try {
            const url = new URL(link.href, window.location.href);
            // Hanya izinkan origin yang sama
            if (url.origin !== window.location.origin) return false;
            // Abaikan jika hanya lompat jangkar (#hash) pada halaman yang sama
            if (url.pathname === window.location.pathname && url.search === window.location.search && url.hash) {
                return false;
            }
            return true;
        } catch (e) {
            return false;
        }
    }

    // Listener klik navigasi global
    document.addEventListener('click', function (e) {
        // Abaikan klik jika tombol modifikasi ditekan (Ctrl, Shift, Meta untuk buka tab baru)
        if (e.defaultPrevented || e.button !== 0 || e.ctrlKey || e.metaKey || e.shiftKey || e.altKey) {
            return;
        }

        const link = e.target.closest('a');
        if (link && isNavigableLink(link)) {
            startProgress();
        }
    });

    // Listener pengiriman formulir (pencarian, filter, atau simpan data)
    document.addEventListener('submit', function (e) {
        if (e.defaultPrevented) return;
        const form = e.target;
        // Jangan tampilkan progress bar jika formulir belum valid secara HTML5
        if (form && typeof form.checkValidity === 'function' && !form.checkValidity()) {
            return;
        }
        startProgress();
    });

    // Saat halaman selesai dimuat atau dipulihkan dari cache peramban (bfcache)
    window.addEventListener('pageshow', function () {
        completeProgress();
    });

    // Saat proses unload dimulai
    window.addEventListener('beforeunload', function () {
        if (progressBar && progressBar.classList.contains('is-active')) {
            progressBar.style.width = '98%';
        }
    });

    // Reset status saat tab tidak aktif atau berpindah
    window.addEventListener('pagehide', resetProgress);
    window.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible') {
            resetProgress();
        }
    });
})();

