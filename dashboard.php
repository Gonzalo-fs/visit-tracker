<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visit Tracker - Analytics Dashboard</title>
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#f0f7ff',
                            100: '#e0effe',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            900: '#1e3a8a',
                        }
                    }
                }
            }
        }
    </script>
    
    <!-- Vue 3 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/vue@3/dist/vue.global.prod.js"></script>
    
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        [v-cloak] { display: none; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen">

<div id="app" v-cloak class="flex flex-col min-h-screen">
    
    <!-- Navigation Header -->
    <header class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-600 flex items-center justify-center text-white font-bold shadow-md shadow-blue-500/20">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl font-bold tracking-tight text-slate-900 leading-none">Visit Tracker</h1>
                    <p class="text-xs text-slate-500 mt-0.5">Lightweight & GDPR Compliant Analytics</p>
                </div>
            </div>

            <!-- Active Website Selector & Actions -->
            <div class="flex items-center space-x-2 sm:space-x-3">
                <label for="site-select" class="text-xs font-semibold text-slate-500 hidden sm:inline-block">Active Site:</label>
                <div class="relative">
                    <select id="site-select" v-model="selectedSiteToken" @change="onSiteChange" 
                            class="bg-slate-100 hover:bg-slate-200 border border-slate-300 text-slate-800 text-sm font-medium rounded-lg px-3 py-1.5 pr-8 focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                        <option v-for="site in sites" :key="site.site_token" :value="site.site_token">
                            {{ site.domain }} ({{ site.site_token.substring(0, 10) }}...)
                        </option>
                    </select>
                </div>

                <!-- Add Site Button -->
                <button @click="openAddSiteModal" 
                        class="text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 px-3 py-1.5 rounded-lg transition inline-flex items-center gap-1.5 shadow-sm shadow-blue-500/20">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>Add Site</span>
                </button>

                <a href="./demo.php" target="_blank" class="text-xs font-semibold text-blue-600 hover:text-blue-800 bg-blue-50 hover:bg-blue-100 border border-blue-200 px-3 py-1.5 rounded-lg transition hidden sm:inline-flex items-center gap-1">
                    <span>Try Demo</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                </a>
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-1 w-full space-y-6">

        <!-- Date Control Bar -->
        <div class="bg-white rounded-2xl p-4 sm:p-5 shadow-sm border border-slate-200 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <!-- Quick Preset Buttons -->
            <div class="flex flex-wrap items-center gap-1.5">
                <button v-for="preset in presets" :key="preset.id"
                        @click="setPreset(preset.id)"
                        :class="activePreset === preset.id ? 'bg-blue-600 text-white font-semibold shadow-sm shadow-blue-500/30' : 'bg-slate-100 hover:bg-slate-200 text-slate-700'"
                        class="px-3.5 py-1.5 rounded-lg text-xs transition font-medium">
                    {{ preset.label }}
                </button>
            </div>

            <!-- Custom Date Inputs -->
            <div class="flex items-center gap-2">
                <div class="flex items-center gap-1 text-sm bg-slate-50 border border-slate-300 rounded-lg px-2.5 py-1.5">
                    <span class="text-slate-400 text-xs font-medium">From:</span>
                    <input type="date" v-model="startDate" @change="onCustomDateChange" 
                           class="bg-transparent text-slate-800 text-xs font-semibold focus:outline-none cursor-pointer">
                </div>
                <div class="flex items-center gap-1 text-sm bg-slate-50 border border-slate-300 rounded-lg px-2.5 py-1.5">
                    <span class="text-slate-400 text-xs font-medium">To:</span>
                    <input type="date" v-model="endDate" @change="onCustomDateChange" 
                           class="bg-transparent text-slate-800 text-xs font-semibold focus:outline-none cursor-pointer">
                </div>
                <button @click="fetchStats" 
                        class="p-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition shadow-sm hover:shadow"
                        title="Refresh data">
                    <svg :class="{'animate-spin': loading}" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Error Notification -->
        <div v-if="errorMessage" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl flex items-center justify-between">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                <span class="text-sm font-medium">{{ errorMessage }}</span>
            </div>
            <button @click="errorMessage = null" class="text-red-400 hover:text-red-700 text-sm font-bold">&times;</button>
        </div>

        <!-- Metric Cards (KPIs) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
            
            <!-- KPI: Unique Visits -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm relative overflow-hidden">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Unique Visits</p>
                        <h3 class="text-2xl sm:text-3xl font-bold text-slate-900 mt-1">
                            {{ formatNumber(totals.unique_visits) }}
                        </h3>
                        <p class="text-xs text-emerald-600 font-medium mt-1 flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block"></span>
                            Distinct users via hash
                        </p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-blue-50 border border-blue-100 flex items-center justify-center text-blue-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- KPI: Total Pageviews -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm relative overflow-hidden">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Pageviews (Hits)</p>
                        <h3 class="text-2xl sm:text-3xl font-bold text-slate-900 mt-1">
                            {{ formatNumber(totals.total_pageviews) }}
                        </h3>
                        <p class="text-xs text-slate-500 mt-1">
                            Avg: {{ averageHitsPerUser }} views / user
                        </p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- KPI: Tracked URLs -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm relative overflow-hidden">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Tracked URLs</p>
                        <h3 class="text-2xl sm:text-3xl font-bold text-slate-900 mt-1">
                            {{ formatNumber(totals.pages_tracked_count) }}
                        </h3>
                        <p class="text-xs text-slate-500 mt-1">
                            Active routes in period
                        </p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-amber-50 border border-amber-100 flex items-center justify-center text-amber-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- KPI: Top Device -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm relative overflow-hidden">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Top Device</p>
                        <h3 class="text-2xl sm:text-3xl font-bold text-slate-900 mt-1">
                            {{ topDevice.name }}
                        </h3>
                        <p class="text-xs text-slate-500 mt-1">
                            {{ topDevice.percentage }}% of recorded traffic
                        </p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-teal-50 border border-teal-100 flex items-center justify-center text-teal-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>
                </div>
            </div>

        </div>

        <!-- Traffic Evolution Chart (Chart.js) -->
        <div class="bg-white rounded-2xl p-5 sm:p-6 border border-slate-200 shadow-sm space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-4">
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-lg font-bold text-slate-900">
                            {{ timeUnit === 'hour' ? "Today's Hourly Traffic" : "Unique Visits Over Time" }}
                        </h2>
                        <span v-if="timeUnit === 'hour'" class="text-[11px] font-semibold bg-blue-50 text-blue-700 px-2 py-0.5 rounded-full border border-blue-100">
                            24-Hour View
                        </span>
                    </div>
                    <p class="text-xs text-slate-500">
                        {{ timeUnit === 'hour' ? "Hourly distribution throughout the day (00:00 - 23:00)" : "Daily traffic by unique visitor (GDPR compliant)" }}
                    </p>
                </div>
                <div class="flex items-center gap-3 text-xs">
                    <span class="inline-flex items-center gap-1.5 font-medium text-blue-600">
                        <span class="w-3 h-3 rounded-full bg-blue-600 inline-block"></span>
                        Unique Visits
                    </span>
                    <span class="inline-flex items-center gap-1.5 font-medium text-slate-400">
                        <span class="w-3 h-3 rounded-full bg-indigo-300 inline-block"></span>
                        Total Pageviews
                    </span>
                </div>
            </div>
            
            <div class="relative w-full h-[320px]">
                <canvas id="trafficChart"></canvas>
            </div>
        </div>

        <!-- Bottom Section: Top Pages Table + Devices & Snippet -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Top Pages Table (2 Columns) -->
            <div class="lg:col-span-2 bg-white rounded-2xl p-5 sm:p-6 border border-slate-200 shadow-sm space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">Most Visited Pages</h2>
                        <p class="text-xs text-slate-500">URL breakdown by unique visitors in date range</p>
                    </div>
                    <span class="text-xs font-semibold px-2.5 py-1 bg-slate-100 rounded-full text-slate-600">
                        {{ pages.length }} pages
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-700">
                        <thead class="text-xs uppercase bg-slate-50 text-slate-500 font-semibold border-b border-slate-200">
                            <tr>
                                <th class="py-3 px-3">Path (URL)</th>
                                <th class="py-3 px-3 text-right">Unique Visits</th>
                                <th class="py-3 px-3 text-right hidden sm:table-cell">Total Views</th>
                                <th class="py-3 px-3 text-right hidden md:table-cell">Traffic Share</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-if="pages.length === 0">
                                <td colspan="4" class="py-8 text-center text-slate-400 text-sm">
                                    No visit data recorded in this date range.
                                </td>
                            </tr>
                            <tr v-for="(page, idx) in pages" :key="page.page_id" class="hover:bg-slate-50/80 transition">
                                <td class="py-3.5 px-3">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-bold text-slate-400 w-4 text-center">{{ idx + 1 }}</span>
                                        <span class="font-mono text-xs text-blue-700 bg-blue-50 px-2 py-0.5 rounded border border-blue-100 max-w-[280px] sm:max-w-md truncate" :title="page.url">
                                            {{ page.url }}
                                        </span>
                                    </div>
                                </td>
                                <td class="py-3.5 px-3 text-right font-bold text-slate-900">
                                    {{ formatNumber(page.unique_visits) }}
                                </td>
                                <td class="py-3.5 px-3 text-right text-slate-500 hidden sm:table-cell">
                                    {{ formatNumber(page.total_pageviews) }}
                                </td>
                                <td class="py-3.5 px-3 text-right hidden md:table-cell">
                                    <div class="flex items-center justify-end gap-2">
                                        <div class="w-16 bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                            <div class="bg-blue-600 h-1.5 rounded-full" :style="{ width: calculatePercentage(page.unique_visits) + '%' }"></div>
                                        </div>
                                        <span class="text-xs text-slate-400 w-9 text-right">{{ calculatePercentage(page.unique_visits) }}%</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Devices Breakdown & Integration Snippet (1 Column) -->
            <div class="space-y-6">
                
                <!-- Card: Device Breakdown -->
                <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm space-y-4">
                    <h3 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3">Devices</h3>
                    <div v-if="devices.length === 0" class="text-center py-6 text-slate-400 text-xs">
                        No device data available
                    </div>
                    <div v-else class="space-y-3">
                        <div v-for="dev in devices" :key="dev.device" class="space-y-1">
                            <div class="flex justify-between text-xs font-semibold">
                                <span class="text-slate-700">{{ dev.device }}</span>
                                <span class="text-slate-900">{{ formatNumber(dev.unique_visits) }} ({{ calculatePercentage(dev.unique_visits) }}%)</span>
                            </div>
                            <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                                <div class="h-2 rounded-full transition-all duration-500"
                                     :class="dev.device === 'Mobile' ? 'bg-indigo-500' : (dev.device === 'Desktop' ? 'bg-blue-600' : 'bg-teal-500')"
                                     :style="{ width: calculatePercentage(dev.unique_visits) + '%' }">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card: Integration Code Snippet -->
                <div class="bg-gradient-to-br from-slate-900 to-slate-800 text-white rounded-2xl p-5 shadow-sm space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-blue-400">Embed Snippet</span>
                        <button @click="copySnippet" class="text-xs bg-slate-700 hover:bg-slate-600 text-slate-200 px-2.5 py-1 rounded-md transition flex items-center gap-1">
                            <span v-if="copied">Copied!</span>
                            <span v-else>Copy</span>
                        </button>
                    </div>
                    <p class="text-xs text-slate-300">Paste this script tag before closing &lt;/body&gt; on client websites:</p>
                    <div class="bg-black/50 p-3 rounded-lg font-mono text-xs text-blue-200 break-all select-all border border-slate-700">
                        &lt;script src="http://localhost/visit-tracker/tracker.js" data-token="{{ selectedSiteToken }}" defer&gt;&lt;/script&gt;
                    </div>
                </div>

            </div>

        </div>

    </main>

    <!-- Modal: Add New Website -->
    <div v-if="showAddSiteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm transition-all">
        <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-lg overflow-hidden transition-all">
            
            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Add New Website</h3>
                        <p class="text-xs text-slate-500">Register a domain to generate its tracking code</p>
                    </div>
                </div>
                <button @click="closeAddSiteModal" class="text-slate-400 hover:text-slate-600 rounded-lg p-1 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Form Step (before creation) -->
            <div v-if="!createdSite" class="p-6 space-y-4">
                <div v-if="addSiteError" class="p-3 bg-red-50 border border-red-200 text-red-700 text-xs rounded-lg flex items-center gap-2">
                    <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                    <span>{{ addSiteError }}</span>
                </div>

                <form @submit.prevent="submitAddSite" class="space-y-4">
                    <div>
                        <label for="new-site-domain" class="block text-xs font-semibold text-slate-700 mb-1">
                            Domain Name or Hostname
                        </label>
                        <input id="new-site-domain" type="text" v-model="newSiteDomain" required
                               placeholder="e.g. my-app.com or blog.mysite.org"
                               class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition" />
                        <p class="text-[11px] text-slate-400 mt-1">
                            Protocols (http/https) and paths will be stripped automatically.
                        </p>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button type="button" @click="closeAddSiteModal" 
                                class="px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-800 bg-slate-100 hover:bg-slate-200 rounded-xl transition">
                            Cancel
                        </button>
                        <button type="submit" :disabled="creatingSite || !newSiteDomain.trim()" 
                                class="px-4 py-2 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50 rounded-xl transition inline-flex items-center gap-1.5 shadow-sm shadow-blue-500/20">
                            <svg v-if="creatingSite" class="animate-spin w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                            <span>{{ creatingSite ? 'Registering...' : 'Register Site' }}</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Success Step (site created, show snippet) -->
            <div v-else class="p-6 space-y-4">
                <div class="p-3.5 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl flex items-center gap-2.5 text-xs">
                    <svg class="w-5 h-5 text-emerald-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    <div>
                        <p class="font-bold text-emerald-900">Website registered successfully!</p>
                        <p class="text-emerald-700">Domain: <span class="font-mono font-bold">{{ createdSite.domain }}</span></p>
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-slate-700">Generated Site Token:</label>
                    <div class="bg-slate-100 p-2.5 rounded-lg font-mono text-xs text-slate-800 select-all border border-slate-200 flex items-center justify-between">
                        <span class="truncate">{{ createdSite.site_token }}</span>
                    </div>
                </div>

                <div class="space-y-1.5">
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-semibold text-slate-700">Client Script Tag:</label>
                        <button @click="copyNewSiteSnippet" class="text-xs text-blue-600 hover:text-blue-800 font-semibold inline-flex items-center gap-1">
                            <span>{{ newSiteCopied ? 'Copied!' : 'Copy Snippet' }}</span>
                        </button>
                    </div>
                    <div class="bg-slate-900 text-blue-200 p-3 rounded-xl font-mono text-xs select-all break-all border border-slate-800">
                        &lt;script src="http://localhost/visit-tracker/tracker.js" data-token="{{ createdSite.site_token }}" defer&gt;&lt;/script&gt;
                    </div>
                    <p class="text-[11px] text-slate-400">
                        Paste this snippet before &lt;/body&gt; in your website HTML to start tracking visits.
                    </p>
                </div>

                <div class="pt-2 flex items-center justify-end gap-2">
                    <button @click="closeAddSiteModal" class="w-full sm:w-auto px-5 py-2.5 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-xl transition shadow-sm shadow-blue-500/20">
                        Done & View Dashboard
                    </button>
                </div>
            </div>

        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-200 mt-auto py-4">
        <div class="max-w-7xl mx-auto px-4 text-center text-xs text-slate-400">
            Visit Tracker &copy; 2026 — Gonzalo Fontana Santibáñez
        </div>
    </footer>

</div>

<!-- Vue 3 Reactive Logic -->
<script>
    const { createApp, ref, computed, onMounted, nextTick } = Vue;

    createApp({
        setup() {
            // Persistence key
            const STORAGE_KEY = 'vt_last_site_token';

            // Initial token from URL (?site=... or ?token=...) or localStorage fallback
            const urlParams = new URLSearchParams(window.location.search);
            const initialToken = urlParams.get('site') || urlParams.get('token') || localStorage.getItem(STORAGE_KEY) || '';

            // Reactive state
            const sites = ref([]);
            const selectedSiteToken = ref(initialToken);
            const startDate = ref('');
            const endDate = ref('');
            const timeUnit = ref('day');
            const activePreset = ref('30d');
            const loading = ref(false);
            const errorMessage = ref(null);
            const copied = ref(false);

            // Add Site Modal state
            const showAddSiteModal = ref(false);
            const newSiteDomain = ref('');
            const creatingSite = ref(false);
            const addSiteError = ref(null);
            const createdSite = ref(null);
            const newSiteCopied = ref(false);

            // Statistical datasets
            const totals = ref({
                unique_visits: 0,
                total_pageviews: 0,
                pages_tracked_count: 0
            });
            const dailySeries = ref([]);
            const pages = ref([]);
            const devices = ref([]);

            // Chart.js instance reference
            let chartInstance = null;

            // Date preset buttons definition
            const presets = [
                { id: 'today', label: 'Today' },
                { id: '7d',    label: 'Last 7 Days' },
                { id: '30d',   label: 'Last 30 Days' },
                { id: 'month', label: 'This Month' }
            ];

            // Helper to format Date objects as YYYY-MM-DD
            function formatDate(d) {
                const year = d.getFullYear();
                const month = String(d.getMonth() + 1).padStart(2, '0');
                const day = String(d.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            }

            // Apply quick date preset
            function setPreset(presetId) {
                activePreset.value = presetId;
                const now = new Date();
                endDate.value = formatDate(now);

                if (presetId === 'today') {
                    startDate.value = formatDate(now);
                } else if (presetId === '7d') {
                    const past = new Date();
                    past.setDate(now.getDate() - 6);
                    startDate.value = formatDate(past);
                } else if (presetId === '30d') {
                    const past = new Date();
                    past.setDate(now.getDate() - 29);
                    startDate.value = formatDate(past);
                } else if (presetId === 'month') {
                    const startOfMonth = new Date(now.getFullYear(), now.getMonth(), 1);
                    startDate.value = formatDate(startOfMonth);
                }

                fetchStats();
            }

            function onCustomDateChange() {
                activePreset.value = 'custom';
                fetchStats();
            }

            // Update browser URL query string without reloading page
            function updateUrlQuery(token) {
                if (!token) return;
                try {
                    const url = new URL(window.location.href);
                    url.searchParams.set('site', token);
                    window.history.replaceState({}, '', url.toString());
                } catch (e) {
                    // Ignore if restricted
                }
            }

            // Called when user switches site from the dropdown
            function onSiteChange() {
                if (selectedSiteToken.value) {
                    localStorage.setItem(STORAGE_KEY, selectedSiteToken.value);
                    updateUrlQuery(selectedSiteToken.value);
                }
                fetchStats();
            }

            // Fetch available sites list and restore last viewed site
            async function fetchSites() {
                try {
                    const res = await fetch('./stats_api.php?action=list_sites');
                    const json = await res.json();
                    if (json.success && json.sites && json.sites.length > 0) {
                        sites.value = json.sites;

                        // 1. Check if the currently selected site exists in the sites array
                        const currentExists = sites.value.some(s => s.site_token === selectedSiteToken.value);
                        if (!currentExists) {
                            // 2. Check if there is a valid site stored in localStorage
                            const savedToken = localStorage.getItem(STORAGE_KEY);
                            const savedExists = sites.value.some(s => s.site_token === savedToken);
                            if (savedExists) {
                                selectedSiteToken.value = savedToken;
                            } else {
                                // 3. Fallback to the first registered site
                                selectedSiteToken.value = sites.value[0].site_token;
                            }
                        }

                        // Persist and synchronize URL
                        localStorage.setItem(STORAGE_KEY, selectedSiteToken.value);
                        updateUrlQuery(selectedSiteToken.value);
                    }
                } catch (e) {
                    console.error('Error fetching sites list:', e);
                }
            }

            // Fetch analytics data from stats_api.php
            async function fetchStats() {
                if (!selectedSiteToken.value) return;
                loading.value = true;
                errorMessage.value = null;

                try {
                    const url = `./stats_api.php?site_token=${encodeURIComponent(selectedSiteToken.value)}&start_date=${startDate.value}&end_date=${endDate.value}`;
                    const res = await fetch(url);
                    const data = await res.json();

                    if (!data.success) {
                        throw new Error(data.error || 'Failed to fetch analytics statistics');
                    }

                    totals.value = data.totals || { unique_visits: 0, total_pageviews: 0, pages_tracked_count: 0 };
                    dailySeries.value = data.daily_series || [];
                    pages.value = data.pages || [];
                    devices.value = data.devices || [];
                    timeUnit.value = data.period?.time_unit || (startDate.value === endDate.value ? 'hour' : 'day');

                    await nextTick();
                    renderChart();
                } catch (err) {
                    console.error('Error in fetchStats:', err);
                    errorMessage.value = err.message || 'Server connection error.';
                } finally {
                    loading.value = false;
                }
            }

            // Render line chart with Chart.js
            function renderChart() {
                const canvas = document.getElementById('trafficChart');
                if (!canvas) return;

                const isHourly = timeUnit.value === 'hour';

                const labels = dailySeries.value.map(item => {
                    if (isHourly || item.time) {
                        return item.time || item.label;
                    }
                    if (item.label) {
                        return item.label;
                    }
                    const parts = item.date.split('-');
                    return `${parts[2]}/${parts[1]}`;
                });

                const uniqueData = dailySeries.value.map(item => item.unique_visits);
                const pageviewData = dailySeries.value.map(item => item.total_pageviews);

                if (chartInstance) {
                    chartInstance.destroy();
                }

                const ctx = canvas.getContext('2d');
                
                // Linear gradient for unique visits fill
                const gradientUnique = ctx.createLinearGradient(0, 0, 0, 300);
                gradientUnique.addColorStop(0, 'rgba(37, 99, 235, 0.35)');
                gradientUnique.addColorStop(1, 'rgba(37, 99, 235, 0.00)');

                chartInstance = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: 'Unique Visits',
                                data: uniqueData,
                                borderColor: '#2563eb',
                                backgroundColor: gradientUnique,
                                borderWidth: 2.5,
                                fill: true,
                                tension: 0.35,
                                pointBackgroundColor: '#2563eb',
                                pointBorderColor: '#ffffff',
                                pointBorderWidth: 2,
                                pointRadius: isHourly ? 3 : 4,
                                pointHoverRadius: 6
                            },
                            {
                                label: 'Pageviews',
                                data: pageviewData,
                                borderColor: '#a5b4fc',
                                backgroundColor: 'transparent',
                                borderWidth: 1.8,
                                borderDash: [4, 4],
                                fill: false,
                                tension: 0.35,
                                pointBackgroundColor: '#a5b4fc',
                                pointBorderColor: '#ffffff',
                                pointRadius: isHourly ? 2 : 3,
                                pointHoverRadius: 5
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            intersect: false,
                            mode: 'index'
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                backgroundColor: '#0f172a',
                                titleFont: { size: 12, weight: 'bold' },
                                bodyFont: { size: 12 },
                                padding: 10,
                                cornerRadius: 8,
                                callbacks: {
                                    title: function(context) {
                                        const index = context[0].dataIndex;
                                        const item = dailySeries.value[index];
                                        if (isHourly && item) {
                                            return `Time: ${item.time || item.label} (${item.date})`;
                                        }
                                        return item ? `Date: ${item.date}` : context[0].label;
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                grid: { display: false },
                                ticks: { 
                                    font: { size: 11 }, 
                                    color: '#64748b',
                                    maxRotation: 0,
                                    autoSkip: true,
                                    maxTicksLimit: isHourly ? 12 : 15
                                }
                            },
                            y: {
                                beginAtZero: true,
                                grid: { color: '#f1f5f9' },
                                ticks: {
                                    precision: 0,
                                    font: { size: 11 },
                                    color: '#64748b'
                                }
                            }
                        }
                    }
                });
            }

            // Computed properties
            const averageHitsPerUser = computed(() => {
                if (!totals.value.unique_visits) return '0.0';
                return (totals.value.total_pageviews / totals.value.unique_visits).toFixed(1);
            });

            const topDevice = computed(() => {
                if (!devices.value || devices.value.length === 0) {
                    return { name: 'N/A', percentage: 0 };
                }
                const first = devices.value[0];
                const total = totals.value.unique_visits || 1;
                const pct = Math.round((first.unique_visits / total) * 100);
                return { name: first.device, percentage: pct };
            });

            function calculatePercentage(val) {
                const total = totals.value.unique_visits || 0;
                if (total === 0) return 0;
                return Math.round((val / total) * 100);
            }

            function formatNumber(num) {
                return (num || 0).toLocaleString();
            }

            function copySnippet() {
                const code = `<script src="http://localhost/visit-tracker/tracker.js" data-token="${selectedSiteToken.value}" defer><\/script>`;
                navigator.clipboard.writeText(code).then(() => {
                    copied.value = true;
                    setTimeout(() => { copied.value = false; }, 2000);
                });
            }

            // Modal methods for adding a new site
            function openAddSiteModal() {
                newSiteDomain.value = '';
                addSiteError.value = null;
                createdSite.value = null;
                newSiteCopied.value = false;
                showAddSiteModal.value = true;
            }

            function closeAddSiteModal() {
                showAddSiteModal.value = false;
                newSiteDomain.value = '';
                addSiteError.value = null;
                createdSite.value = null;
            }

            async function submitAddSite() {
                if (!newSiteDomain.value.trim()) return;
                creatingSite.value = true;
                addSiteError.value = null;

                try {
                    const res = await fetch('./stats_api.php?action=create_site', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ domain: newSiteDomain.value.trim() })
                    });
                    const data = await res.json();

                    if (!res.ok || !data.success) {
                        throw new Error(data.error || 'Failed to register website.');
                    }

                    createdSite.value = data.site;

                    // Refresh sites list in background and select the new site
                    await fetchSites();
                    selectedSiteToken.value = data.site.site_token;
                    localStorage.setItem(STORAGE_KEY, data.site.site_token);
                    updateUrlQuery(data.site.site_token);
                    await fetchStats();
                } catch (err) {
                    addSiteError.value = err.message || 'An unexpected error occurred.';
                } finally {
                    creatingSite.value = false;
                }
            }

            function copyNewSiteSnippet() {
                if (!createdSite.value) return;
                const code = `<script src="http://localhost/visit-tracker/tracker.js" data-token="${createdSite.value.site_token}" defer><\/script>`;
                navigator.clipboard.writeText(code).then(() => {
                    newSiteCopied.value = true;
                    setTimeout(() => { newSiteCopied.value = false; }, 2000);
                });
            }

            onMounted(async () => {
                // Initialize default dates (Last 30 Days)
                const now = new Date();
                endDate.value = formatDate(now);
                const past = new Date();
                past.setDate(now.getDate() - 29);
                startDate.value = formatDate(past);
                activePreset.value = '30d';

                // 1. Load sites and restore last viewed site
                await fetchSites();

                // 2. Fetch analytics for the active site
                await fetchStats();
            });

            return {
                sites,
                selectedSiteToken,
                startDate,
                endDate,
                timeUnit,
                activePreset,
                presets,
                totals,
                dailySeries,
                pages,
                devices,
                loading,
                errorMessage,
                copied,
                showAddSiteModal,
                newSiteDomain,
                creatingSite,
                addSiteError,
                createdSite,
                newSiteCopied,
                averageHitsPerUser,
                topDevice,
                setPreset,
                onCustomDateChange,
                fetchStats,
                onSiteChange,
                calculatePercentage,
                formatNumber,
                copySnippet,
                openAddSiteModal,
                closeAddSiteModal,
                submitAddSite,
                copyNewSiteSnippet
            };
        }
    }).mount('#app');
</script>

</body>
</html>
