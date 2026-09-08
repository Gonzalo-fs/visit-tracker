<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Page - Visit Tracker Demo</title>
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- 
        REAL CLIENT INTEGRATION TAG
        This is the script tag embedded by external client websites to track visits.
    -->
    <script src="http://localhost/visit-tracker/tracker.js" data-token="demo_token_visit_tracker" defer></script>
    
</head>
<body class="bg-slate-100 min-h-screen flex items-center justify-center p-4">

    <div class="max-w-xl w-full bg-white rounded-2xl shadow-xl border border-slate-200 p-8 space-y-6">
        <div class="flex items-center space-x-3 border-b border-slate-100 pb-4">
            <span class="text-3xl">🚀</span>
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Demo Client Website</h1>
                <p class="text-sm text-slate-500">This page simulates a client site running <code class="bg-slate-100 px-1.5 py-0.5 rounded text-blue-600 font-mono">tracker.js</code></p>
            </div>
        </div>

        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 p-4 rounded-xl text-sm flex items-start gap-3">
            <svg class="w-5 h-5 text-emerald-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            <div>
                <span class="font-bold">Visit logged automatically!</span>
                <p class="mt-0.5 text-xs text-emerald-700">Upon loading or refreshing this page, <code class="font-mono">tracker.js</code> detected your device, current path, and sent an anonymous (GDPR) record to <code class="font-mono">track.php</code>.</p>
            </div>
        </div>

        <div class="space-y-3">
            <h3 class="text-sm font-semibold text-slate-700">Simulate visits to other routes:</h3>
            <div class="grid grid-cols-2 gap-2 text-xs">
                <button onclick="simulateVisit('/home', 'Desktop')" class="p-2.5 bg-slate-50 hover:bg-blue-50 hover:border-blue-300 border border-slate-200 rounded-lg text-left transition font-mono">
                    ➕ Visit <b>/home</b> (Desktop)
                </button>
                <button onclick="simulateVisit('/products', 'Mobile')" class="p-2.5 bg-slate-50 hover:bg-blue-50 hover:border-blue-300 border border-slate-200 rounded-lg text-left transition font-mono">
                    ➕ Visit <b>/products</b> (Mobile)
                </button>
                <button onclick="simulateVisit('/pricing', 'Desktop')" class="p-2.5 bg-slate-50 hover:bg-blue-50 hover:border-blue-300 border border-slate-200 rounded-lg text-left transition font-mono">
                    ➕ Visit <b>/pricing</b> (Desktop)
                </button>
                <button onclick="simulateVisit('/contact', 'Mobile')" class="p-2.5 bg-slate-50 hover:bg-blue-50 hover:border-blue-300 border border-slate-200 rounded-lg text-left transition font-mono">
                    ➕ Visit <b>/contact</b> (Mobile)
                </button>
            </div>
            <div id="sim-status" class="text-xs text-slate-500 italic h-4"></div>
        </div>

        <div class="border-t border-slate-100 pt-4 flex items-center justify-between">
            <a href="./dashboard.php" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-xl transition shadow-md shadow-blue-500/20">
                <span>View Real-time Dashboard</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </a>
            <span class="text-xs text-slate-400 font-mono">Token: demo_token_...</span>
        </div>
    </div>

    <script>
        function simulateVisit(url, device) {
            const statusEl = document.getElementById('sim-status');
            statusEl.textContent = `Logging visit to ${url} as ${device}...`;
            
            fetch('./track.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    site_token: 'demo_token_visit_tracker',
                    url: url,
                    device: device
                })
            })
            .then(res => res.json())
            .then(data => {
                statusEl.textContent = `✅ Visit logged successfully to ${url} (ID: ${data.data.visit_id})`;
                setTimeout(() => { statusEl.textContent = ''; }, 3000);
            })
            .catch(err => {
                statusEl.textContent = `❌ Error: ${err.message}`;
            });
        }
    </script>
</body>
</html>
