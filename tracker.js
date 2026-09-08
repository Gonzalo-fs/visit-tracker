/**
 * ==========================================================
 * visit-tracker: Client Website Tracking Script (tracker.js)
 * ==========================================================
 * 
 * Embed usage:
 * <script src="http://localhost/visit-tracker/tracker.js" data-token="YOUR_TOKEN" defer></script>
 */
(function () {
    'use strict';

    // 1. Locate current script tag reference
    var scriptEl = document.currentScript || (function () {
        var scripts = document.getElementsByTagName('script');
        for (var i = scripts.length - 1; i >= 0; i--) {
            if (scripts[i].getAttribute('data-token') || (scripts[i].src && scripts[i].src.indexOf('tracker.js') !== -1)) {
                return scripts[i];
            }
        }
        return null;
    })();

    if (!scriptEl) {
        console.warn('[VisitTracker] Could not locate tracker <script> tag.');
        return;
    }

    // 2. Extract site token attribute
    var siteToken = scriptEl.getAttribute('data-token');
    if (!siteToken) {
        console.warn('[VisitTracker] Missing "data-token" attribute on <script> tag.');
        return;
    }

    // 3. Dynamically determine track.php API endpoint based on script origin
    var endpoint = 'http://localhost/visit-tracker/track.php';
    if (scriptEl.src) {
        try {
            var urlObj = new URL(scriptEl.src);
            endpoint = urlObj.origin + urlObj.pathname.replace(/tracker\.js$/, 'track.php');
        } catch (e) {
            // Fallback for legacy environments or relative paths
        }
    }

    // 4. Detect device type (Mobile / Tablet / Desktop)
    function detectDevice() {
        var ua = navigator.userAgent || navigator.vendor || window.opera || '';
        if (/(tablet|ipad|playbook|silk)|(android(?!.*mobi))/i.test(ua)) {
            return 'Tablet';
        }
        if (/Mobile|iP(hone|od)|Android|BlackBerry|IEMobile|Kindle|Silk-Accelerated|(hpw|web)OS|Fennec|Minimo|Opera M(obi|ini)|Blazer/i.test(ua)) {
            return 'Mobile';
        }
        return 'Desktop';
    }

    // 5. Build JSON payload
    var payload = {
        site_token: siteToken,
        url: window.location.pathname || '/',
        device: detectDevice()
    };

    // 6. Send visit data to track.php via fetch API
    function sendTrackingData() {
        var jsonPayload = JSON.stringify(payload);

        if (typeof fetch === 'function') {
            fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: jsonPayload,
                mode: 'cors',
                keepalive: true // Allows fetch request to finish even if user navigates away
            })
            .then(function (response) {
                if (!response.ok) {
                    return response.json().then(function (err) {
                        console.warn('[VisitTracker] Tracking error:', err);
                    });
                }
            })
            .catch(function (error) {
                // Silently log network errors to avoid breaking user experience
                console.debug('[VisitTracker] Request dispatch error:', error);
            });
        } else if (navigator.sendBeacon) {
            // Fallback to Beacon API
            var blob = new Blob([jsonPayload], { type: 'application/json' });
            navigator.sendBeacon(endpoint, blob);
        }
    }

    // Execute upon page load
    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        sendTrackingData();
    } else {
        window.addEventListener('DOMContentLoaded', sendTrackingData);
    }
})();
