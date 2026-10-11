/**
 * NORD Home — frontend JavaScript.
 * Lightweight, vanilla. No dependencies.
 * - Mobile menu open/close with overlay
 * - Newsletter form validation and honest unavailable state
 * - Safe image fallback
 */
(function () {
    'use strict';

    function ready(fn) {
        if (document.readyState !== 'loading') fn();
        else document.addEventListener('DOMContentLoaded', fn);
    }

    ready(function () {
        initMobileMenu();
        initNewsletter();
    });

    function initMobileMenu() {
        var toggle = document.getElementById('mobileMenuToggle');
        var drawer = document.getElementById('mobileMenu');
        var overlay = document.getElementById('mobileMenuOverlay');
        var closeBtn = document.getElementById('mobileMenuClose');
        if (!toggle || !drawer || !overlay) return;

        function open() {
            drawer.classList.add('is-open');
            drawer.setAttribute('aria-hidden', 'false');
            overlay.classList.add('is-open');
            overlay.setAttribute('aria-hidden', 'false');
            toggle.setAttribute('aria-expanded', 'true');
            document.body.classList.add('body--no-scroll');
        }
        function close() {
            drawer.classList.remove('is-open');
            drawer.setAttribute('aria-hidden', 'true');
            overlay.classList.remove('is-open');
            overlay.setAttribute('aria-hidden', 'true');
            toggle.setAttribute('aria-expanded', 'false');
            document.body.classList.remove('body--no-scroll');
        }
        toggle.addEventListener('click', function () {
            if (drawer.classList.contains('is-open')) close();
            else open();
        });
        overlay.addEventListener('click', close);
        if (closeBtn) closeBtn.addEventListener('click', close);
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') close();
        });
        drawer.querySelectorAll('a').forEach(function (a) {
            a.addEventListener('click', close);
        });
    }

    function initNewsletter() {
        var form = document.getElementById('newsletterForm');
        if (!form) return;
        var msg = document.getElementById('newsletterMessage');
        var input = document.getElementById('newsletterEmail');
        if (!input || !msg) return;

        function setMessage(text, kind) {
            msg.textContent = text;
            msg.classList.remove('is-success', 'is-error');
            if (kind) msg.classList.add('is-' + kind);
        }

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var value = input.value.trim();
            if (!value || !input.validity.valid) {
                setMessage(value ? 'Địa chỉ email chưa đúng định dạng.' : 'Vui lòng nhập địa chỉ email.', 'error');
                input.focus();
                return;
            }
            setMessage('Tính năng bản tin chưa được kết nối; email của bạn chưa được lưu hoặc gửi đi.', 'error');
        });
    }
})();