(function () {
    if (typeof window === 'undefined') {
        return;
    }

    if (typeof LPGC === 'undefined') {
        return;
    }

    var delay = parseInt(LPGC.delay_ms, 10) || 0;
    var ttl = parseInt(LPGC.ttl_ms, 10) || 0;
    var cookieVersion = parseInt(LPGC.cookie_version, 10) || 1;
    var startKeyTTL = ttl > 0 ? ttl : 24 * 60 * 60 * 1000;

    function clickKey() {
        return 'popup_redirect_v' + cookieVersion;
    }

    function startKey() {
        return 'popup_gate_started_v' + cookieVersion;
    }

    function setCookie(key, value, ttlMs) {
        try {
            var expires = '';
            if (ttlMs > 0) {
                var date = new Date();
                date.setTime(date.getTime() + ttlMs);
                expires = '; expires=' + date.toUTCString();
            }
            document.cookie = key + '=' + encodeURIComponent(value) + expires + '; path=/';
        } catch (e) {
            // noop
        }
    }

    function getCookie(key) {
        try {
            var name = key + '=';
            var decodedCookie = decodeURIComponent(document.cookie || '');
            var ca = decodedCookie.split(';');
            for (var i = 0; i < ca.length; i++) {
                var c = ca[i].trim();
                if (c.indexOf(name) === 0) {
                    return c.substring(name.length, c.length);
                }
            }
        } catch (e) {
            // noop
        }
        return null;
    }

    function removeCookie(key) {
        try {
            document.cookie = key + '=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/';
        } catch (e) {
            // noop
        }
    }

    function setWithExpiry(key, value, ttlMs) {
        if (ttlMs < 0) {
            ttlMs = 0;
        }
        var now = Date.now();
        var item = {
            value: value,
            expiry: ttlMs > 0 ? now + ttlMs : 0
        };
        try {
            localStorage.setItem(key, JSON.stringify(item));
        } catch (e) {
            // noop
        }
        if (ttlMs > 0) {
            setCookie(key, JSON.stringify(item), ttlMs);
        } else {
            setCookie(key, JSON.stringify(item), 0);
        }
    }

    function getWithExpiry(key) {
        var itemStr = null;
        try {
            itemStr = localStorage.getItem(key);
        } catch (e) {
            // noop
        }
        if (!itemStr) {
            itemStr = getCookie(key);
        }
        if (!itemStr) {
            return null;
        }
        var item;
        try {
            item = JSON.parse(itemStr);
        } catch (e) {
            return null;
        }
        if (!item || typeof item !== 'object') {
            return null;
        }
        if (item.expiry && Date.now() > item.expiry) {
            removeKey(key);
            return null;
        }
        return item.value;
    }

    function removeKey(key) {
        try {
            localStorage.removeItem(key);
        } catch (e) {
            // noop
        }
        removeCookie(key);
    }

    function detectChapterNumber() {
        var match = (window.location.pathname || '').match(/(?:^|\/)chuong[^0-9]*([0-9]{1,6})(?:\/|$)/i);
        if (!match) {
            return null;
        }
        return parseInt(match[1], 10);
    }

    function isChapterPage() {
        return detectChapterNumber() !== null;
    }

    function isEvenChapter() {
        var number = detectChapterNumber();
        if (number === null) {
            return false;
        }
        return number % 2 === 0;
    }

    function ensureNoticeOverlay() {
        var overlay = document.getElementById('lpgc-notice-overlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = 'lpgc-notice-overlay';
            overlay.className = 'lpgc-notice-overlay';
            overlay.style.display = 'none';
            var inner = document.createElement('div');
            inner.className = 'lpgc-notice-inner';
            inner.innerHTML = LPGC.notice_html || '';
            overlay.appendChild(inner);
            document.body.appendChild(overlay);
        }
        return overlay;
    }

    function showNoticeOverlay() {
        var overlay = ensureNoticeOverlay();
        overlay.style.display = 'flex';
        overlay.setAttribute('aria-hidden', 'false');
    }

    function hideNoticeOverlay() {
        var overlay = document.getElementById('lpgc-notice-overlay');
        if (overlay) {
            overlay.style.display = 'none';
            overlay.setAttribute('aria-hidden', 'true');
        }
    }

    function shouldShowNow() {
        if (!isChapterPage()) {
            return false;
        }
        if (window.__lpgc_closed_on_this_page === true) {
            return false;
        }

        if (getWithExpiry(clickKey())) {
            removeKey(startKey());
            return false;
        }

        if (getWithExpiry(startKey())) {
            return true;
        }

        if (isEvenChapter()) {
            setWithExpiry(startKey(), '1', startKeyTTL);
            return true;
        }

        return false;
    }

    function openPopup(popup) {
        popup.style.display = 'flex';
        popup.setAttribute('aria-hidden', 'false');
        ensureNoticeOverlay();
    }

    function closePopup(popup) {
        popup.style.display = 'none';
        popup.setAttribute('aria-hidden', 'true');
    }

    function attachHandlers(popup) {
        if (popup.__lpgcHandlersAttached) {
            return;
        }
        popup.__lpgcHandlersAttached = true;

        var closeBtn = popup.querySelector('.close-btn');
        if (closeBtn) {
            closeBtn.addEventListener('click', function () {
                window.__lpgc_closed_on_this_page = true;
                closePopup(popup);
                showNoticeOverlay();
            });
        }

        var cta = popup.querySelector('.popup_redirect-point');
        if (cta) {
            cta.addEventListener('click', function () {
                setWithExpiry(clickKey(), '1', ttl);
                removeKey(startKey());
                window.__lpgc_closed_on_this_page = true;
                hideNoticeOverlay();
                closePopup(popup);
            });
        }

        var imageLink = popup.querySelector('.lpgc-image-link');
        if (imageLink) {
            imageLink.addEventListener('click', function () {
                setWithExpiry(clickKey(), '1', ttl);
                removeKey(startKey());
                window.__lpgc_closed_on_this_page = true;
                hideNoticeOverlay();
                closePopup(popup);
            });
        }
    }

    function initialize() {
        var popup = document.getElementById('lpgc-popup');
        if (!popup) {
            return false;
        }

        ensureNoticeOverlay();
        attachHandlers(popup);

        if (shouldShowNow()) {
            setTimeout(function () {
                if (shouldShowNow()) {
                    openPopup(popup);
                }
            }, delay);
        } else {
            closePopup(popup);
            hideNoticeOverlay();
        }

        return true;
    }

    function ready(fn) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn);
        } else {
            fn();
        }
    }

    function setupObserver() {
        var target = document.querySelector('.chapter-wrapper') || document.body;
        if (!target || !window.MutationObserver) {
            return;
        }
        var observer = new MutationObserver(function () {
            initialize();
        });
        observer.observe(target, { childList: true, subtree: true });
    }

    ready(function () {
        var attempts = 0;
        var maxAttempts = 10;
        function tryInit() {
            attempts += 1;
            if (initialize()) {
                return;
            }
            if (attempts < maxAttempts) {
                setTimeout(tryInit, 300);
            }
        }
        tryInit();
        setupObserver();
        window.addEventListener('popstate', function () {
            window.__lpgc_closed_on_this_page = false;
            setTimeout(function () {
                initialize();
            }, 100);
        });
    });
})();
