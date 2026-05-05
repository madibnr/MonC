<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - MonC</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'monc-dark': '#0f172a',
                        'monc-sidebar': '#1e293b',
                        'monc-accent': '#3b82f6',
                        'monc-hover': '#334155',
                    }
                }
            }
        }
    </script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }

        /* Custom scrollbar for sidebar */
        .sidebar-scroll::-webkit-scrollbar { width: 4px; }
        .sidebar-scroll::-webkit-scrollbar-track { background: transparent; }
        .sidebar-scroll::-webkit-scrollbar-thumb { background: #475569; border-radius: 2px; }

        /* Video grid styles */
        .video-grid { display: grid; gap: 2px; }
        .video-grid.grid-1 { grid-template-columns: 1fr; }
        .video-grid.grid-4 { grid-template-columns: repeat(2, 1fr); }
        .video-grid.grid-9 { grid-template-columns: repeat(3, 1fr); }
        .video-grid.grid-16 { grid-template-columns: repeat(4, 1fr); }
        .video-grid.grid-32 { grid-template-columns: repeat(8, 1fr); }
        .video-grid.grid-64 { grid-template-columns: repeat(8, 1fr); }
    </style>

    @yield('styles')
</head>
<body class="bg-slate-100 min-h-screen" x-data="{ sidebarOpen: true, mobileSidebar: false }">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <!-- Mobile overlay -->
        <div x-show="mobileSidebar" x-cloak @click="mobileSidebar = false" class="fixed inset-0 bg-black/50 z-40 lg:hidden"></div>

        <aside :class="{ 'translate-x-0': mobileSidebar, '-translate-x-full': !mobileSidebar }"
               class="fixed lg:static lg:translate-x-0 z-50 w-64 bg-monc-dark min-h-screen flex flex-col transition-transform duration-300">

            <!-- Brand -->
            <div class="flex items-center gap-3 px-6 py-5 border-b border-slate-700/50">
                <div class="w-10 h-10 bg-monc-accent rounded-lg flex items-center justify-center">
                    <i class="fas fa-shield-alt text-white text-lg"></i>
                </div>
                <div>
                    <h1 class="text-white font-bold text-lg tracking-wide">MonC</h1>
                    <p class="text-slate-400 text-xs">Monitoring CCTV</p>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 px-3 py-4 sidebar-scroll overflow-y-auto">
                <div class="space-y-1">
                    <a href="{{ route('dashboard') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('dashboard') ? 'bg-monc-accent text-white' : 'text-slate-300 hover:bg-monc-hover hover:text-white' }}">
                        <i class="fas fa-tachometer-alt w-5 text-center"></i>
                        <span>Dashboard</span>
                    </a>

                    <a href="{{ route('live.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('live.*') ? 'bg-monc-accent text-white' : 'text-slate-300 hover:bg-monc-hover hover:text-white' }}">
                        <i class="fas fa-video w-5 text-center"></i>
                        <span>Live Monitoring</span>
                    </a>

                    <a href="{{ route('playback.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('playback.*') ? 'bg-monc-accent text-white' : 'text-slate-300 hover:bg-monc-hover hover:text-white' }}">
                        <i class="fas fa-play-circle w-5 text-center"></i>
                        <span>Playback</span>
                    </a>



                    @if(auth()->user()->isSuperadmin() || auth()->user()->isAdminIt())
                    <div class="pt-4 pb-2">
                        <p class="px-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Management</p>
                    </div>

                    <a href="{{ route('cameras.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('cameras.*') ? 'bg-monc-accent text-white' : 'text-slate-300 hover:bg-monc-hover hover:text-white' }}">
                        <i class="fas fa-camera w-5 text-center"></i>
                        <span>Camera Management</span>
                    </a>

                    <a href="{{ route('nvrs.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('nvrs.*') ? 'bg-monc-accent text-white' : 'text-slate-300 hover:bg-monc-hover hover:text-white' }}">
                        <i class="fas fa-server w-5 text-center"></i>
                        <span>NVR Management</span>
                    </a>

                    <a href="{{ route('buildings.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('buildings.*') ? 'bg-monc-accent text-white' : 'text-slate-300 hover:bg-monc-hover hover:text-white' }}">
                        <i class="fas fa-building w-5 text-center"></i>
                        <span>Building Management</span>
                    </a>

                    <a href="{{ route('health.index') }}" 
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('health.*') ? 'bg-monc-accent text-white' : 'text-slate-300 hover:bg-monc-hover hover:text-white' }}">
                        <i class="fas fa-heartbeat w-5 text-center"></i>
                        <span>Health Monitor</span>
                    </a>
                    @endif

                    {{-- AI Analytics Section - DISABLED
                    @if(auth()->user()->isSuperadmin())
                    <div class="pt-4 pb-2">
                        <p class="px-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">AI Analytics</p>
                    </div>

                    <a href="{{ route('ai.cameras.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('ai.cameras.*') ? 'bg-monc-accent text-white' : 'text-slate-300 hover:bg-monc-hover hover:text-white' }}">
                        <i class="fas fa-microchip w-5 text-center"></i>
                        <span>AI Camera Assignment</span>
                    </a>

                    <a href="{{ route('ai.detections.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('ai.detections.*') ? 'bg-monc-accent text-white' : 'text-slate-300 hover:bg-monc-hover hover:text-white' }}">
                        <i class="fas fa-car w-5 text-center"></i>
                        <span>Plate Detection Logs</span>
                    </a>

                    <a href="{{ route('ai.watchlist.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('ai.watchlist.*') ? 'bg-monc-accent text-white' : 'text-slate-300 hover:bg-monc-hover hover:text-white' }}">
                        <i class="fas fa-list-check w-5 text-center"></i>
                        <span>Watchlist</span>
                    </a>

                    <a href="{{ route('ai.incidents.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('ai.incidents.*') ? 'bg-monc-accent text-white' : 'text-slate-300 hover:bg-monc-hover hover:text-white' }}">
                        <i class="fas fa-triangle-exclamation w-5 text-center"></i>
                        <span>Incident Timeline</span>
                    </a>

                    <a href="{{ route('ai.reports.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('ai.reports.*') ? 'bg-monc-accent text-white' : 'text-slate-300 hover:bg-monc-hover hover:text-white' }}">
                        <i class="fas fa-chart-bar w-5 text-center"></i>
                        <span>AI Reports</span>
                    </a>
                    @endif
                    --}}

                    @if(auth()->user()->isSuperadmin())
                    <div class="pt-4 pb-2">
                        <p class="px-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Administration</p>
                    </div>

                    <a href="{{ route('users.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('users.*') ? 'bg-monc-accent text-white' : 'text-slate-300 hover:bg-monc-hover hover:text-white' }}">
                        <i class="fas fa-users w-5 text-center"></i>
                        <span>User Management</span>
                    </a>

                    <a href="{{ route('user-access.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('user-access.*') ? 'bg-monc-accent text-white' : 'text-slate-300 hover:bg-monc-hover hover:text-white' }}">
                        <i class="fas fa-user-shield w-5 text-center"></i>
                        <span>User Access</span>
                    </a>
                    @endif

                    @if(auth()->user()->isSuperadmin() || auth()->user()->isAuditor())
                    <a href="{{ route('audit-logs.index') }}" 
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('audit-logs.*') ? 'bg-monc-accent text-white' : 'text-slate-300 hover:bg-monc-hover hover:text-white' }}">
                        <i class="fas fa-clipboard-list w-5 text-center"></i>
                        <span>Audit Logs</span>
                    </a>
                    @endif

                    <div class="pt-4 pb-2">
                        <p class="px-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">System</p>
                    </div>

                    <a href="{{ route('settings.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('settings.*') ? 'bg-monc-accent text-white' : 'text-slate-300 hover:bg-monc-hover hover:text-white' }}">
                        <i class="fas fa-cog w-5 text-center"></i>
                        <span>Settings</span>
                    </a>
                </div>
            </nav>

            <!-- Sidebar Footer -->
            <div class="px-4 py-3 border-t border-slate-700/50">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-slate-600 rounded-full flex items-center justify-center">
                        <i class="fas fa-user text-slate-300 text-xs"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-white truncate">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-slate-400 capitalize">{{ str_replace('_', ' ', auth()->user()->role) }}</p>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col min-h-screen lg:ml-0">
            <!-- Top Header -->
            <header class="bg-white shadow-sm border-b border-slate-200 sticky top-0 z-30 flex-shrink-0">
                <div class="flex items-center justify-between px-4 lg:px-6 py-3">
                    <div class="flex items-center gap-4">
                        <button @click="mobileSidebar = !mobileSidebar" class="lg:hidden text-slate-600 hover:text-slate-900">
                            <i class="fas fa-bars text-lg"></i>
                        </button>
                        <h2 class="text-lg font-semibold text-slate-800">@yield('page-title', 'Dashboard')</h2>
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="hidden sm:flex items-center gap-2 text-sm text-slate-600">
                            <i class="fas fa-clock"></i>
                            <span x-data x-init="setInterval(() => $el.textContent = new Date().toLocaleTimeString('id-ID'), 1000)"></span>
                        </div>

                        <!-- Alert Bell -->
                        <div class="relative" x-data="alertBell()" x-init="fetchCount()">
                            <button @click="toggleDropdown()" class="relative p-2 text-slate-600 hover:text-slate-800 transition-colors">
                                <i class="fas fa-bell text-lg"></i>
                                <span x-show="count > 0" x-cloak
                                      class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center"
                                      x-text="count > 99 ? '99+' : count"></span>
                            </button>
                            
                            <!-- Dropdown -->
                            <div x-show="open" x-cloak @click.away="open = false"
                                 class="absolute right-0 mt-2 w-80 bg-white rounded-xl shadow-lg border border-slate-200 z-50 overflow-hidden">
                                <div class="px-4 py-3 border-b border-slate-200 flex items-center justify-between">
                                    <h4 class="text-sm font-semibold text-slate-800">Alerts</h4>
                                    <a href="{{ route('alerts.index') }}" class="text-xs text-blue-500 hover:text-blue-700">View All</a>
                                </div>
                                <div class="max-h-64 overflow-y-auto">
                                    <template x-for="alert in alerts" :key="alert.id">
                                        <div class="px-4 py-3 border-b border-slate-100 hover:bg-slate-50 cursor-pointer"
                                             :class="{ 'bg-blue-50/50': !alert.is_read }">
                                            <div class="flex items-start gap-3">
                                                <i class="fas text-sm mt-0.5" :class="'fa-' + alert.icon?.replace('fa-','') + ' text-' + alert.color + '-500'"></i>
                                                <div class="flex-1 min-w-0">
                                                    <p class="text-sm font-medium text-slate-800 truncate" x-text="alert.title"></p>
                                                    <p class="text-xs text-slate-500 mt-0.5" x-text="alert.time"></p>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                    <div x-show="alerts.length === 0" class="px-4 py-6 text-center text-sm text-slate-400">
                                        <i class="fas fa-check-circle text-lg mb-1 block"></i>
                                        No new alerts
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <span class="hidden md:inline-block px-2 py-1 text-xs font-medium rounded-full {{ auth()->user()->isSuperadmin() ? 'bg-red-100 text-red-700' : (auth()->user()->isAdminIt() ? 'bg-blue-100 text-blue-700' : (auth()->user()->isAuditor() ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700')) }}">
                                {{ ucfirst(str_replace('_', ' ', auth()->user()->role)) }}
                            </span>
                        </div>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="flex items-center gap-2 text-sm text-slate-600 hover:text-red-600 transition-colors">
                                <i class="fas fa-sign-out-alt"></i>
                                <span class="hidden sm:inline">Logout</span>
                            </button>
                        </form>
                    </div>
                </div>
            </header>

            <!-- Flash Messages -->
            @if(session('success'))
            <div class="mx-4 lg:mx-6 mt-4" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)">
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-check-circle"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                    <button @click="show = false" class="text-green-500 hover:text-green-700"><i class="fas fa-times"></i></button>
                </div>
            </div>
            @endif

            @if(session('error'))
            <div class="mx-4 lg:mx-6 mt-4" x-data="{ show: true }" x-show="show">
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-exclamation-circle"></i>
                        <span>{{ session('error') }}</span>
                    </div>
                    <button @click="show = false" class="text-red-500 hover:text-red-700"><i class="fas fa-times"></i></button>
                </div>
            </div>
            @endif

            @if($errors->any())
            <div class="mx-4 lg:mx-6 mt-4">
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                    <div class="flex items-center gap-2 mb-2">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span class="font-medium">Please fix the following errors:</span>
                    </div>
                    <ul class="list-disc list-inside text-sm space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
            @endif

            <!-- Page Content -->
            <main class="flex-1 p-4 lg:p-6 overflow-y-auto">
                @yield('content')
            </main>

            <!-- Footer - Sticky at Bottom -->
            <footer class="bg-white border-t border-slate-200 px-4 lg:px-6 py-4 flex-shrink-0 mt-auto">
                <div class="flex flex-col items-center justify-center gap-2 text-center">
                    <!-- Main Footer Text -->
                    <div class="flex flex-wrap items-center justify-center gap-x-3 gap-y-1 text-xs text-slate-600">
                        <span class="font-medium">MonC - Monitoring CCTV System</span>
                        <span class="hidden sm:inline text-slate-400">•</span>
                        <span>&copy; {{ date('Y') }} Direktorat Jenderal Bea dan Cukai</span>
                    </div>
                    
                    <!-- Creator Credit -->
                    <div class="flex items-center gap-1.5 text-xs text-slate-500">
                        <span>Created by</span>
                        <a href="https://www.linkedin.com/in/moh-adib-nur-rachmad" 
                           target="_blank" 
                           rel="noopener noreferrer" 
                           class="inline-flex items-center gap-1 text-blue-600 hover:text-blue-700 font-medium transition-colors group">
                            <i class="fab fa-linkedin text-sm"></i>
                            <span class="group-hover:underline">Adb</span>
                        </a>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <script>
    function alertBell() {
        return {
            count: 0,
            alerts: [],
            open: false,
            
            fetchCount() {
                fetch('/alerts/unread-count', { headers: { 'Accept': 'application/json' } })
                    .then(r => r.json())
                    .then(data => { this.count = data.count; })
                    .catch(() => {});
                
                // Refresh every 30 seconds
                setInterval(() => {
                    fetch('/alerts/unread-count', { headers: { 'Accept': 'application/json' } })
                        .then(r => r.json())
                        .then(data => { this.count = data.count; })
                        .catch(() => {});
                }, 30000);
            },
            
            toggleDropdown() {
                this.open = !this.open;
                if (this.open) {
                    fetch('/alerts/recent', { headers: { 'Accept': 'application/json' } })
                        .then(r => r.json())
                        .then(data => { this.alerts = data.alerts; })
                        .catch(() => {});
                }
            }
        };
    }
    </script>

    @yield('scripts')
</body>
</html>
