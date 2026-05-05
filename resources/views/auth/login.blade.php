<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - MonC</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'monc-dark': '#0f172a',
                        'monc-accent': '#3b82f6',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-monc-dark min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <!-- Logo -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-monc-accent rounded-2xl mb-4">
                <i class="fas fa-shield-alt text-white text-2xl"></i>
            </div>
            <h1 class="text-3xl font-bold text-white">MonC</h1>
            <p class="text-slate-400 mt-1">Monitoring CCTV System</p>
        </div>

        <!-- Login Card -->
        <div class="bg-white rounded-2xl shadow-2xl p-8">
            <h2 class="text-xl font-semibold text-slate-800 mb-1">Welcome Back</h2>
            <p class="text-sm text-slate-500 mb-6">Sign in to access the monitoring system</p>

            @if(session('error'))
            <div class="bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-lg mb-4 text-sm flex items-center gap-2">
                <i class="fas fa-exclamation-circle"></i>
                {{ session('error') }}
            </div>
            @endif

            @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-lg mb-4 text-sm">
                @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
            @endif

            <form method="POST" action="{{ route('login.attempt') }}">
                @csrf

                <div class="mb-4">
                    <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Email Address</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                            <i class="fas fa-envelope"></i>
                        </span>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                               class="w-full pl-10 pr-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-monc-accent focus:border-monc-accent outline-none transition-colors"
                               placeholder="Enter your email">
                    </div>
                </div>

                <div class="mb-4">
                    <label for="password" class="block text-sm font-medium text-slate-700 mb-1">Password</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" id="password" name="password" required
                               class="w-full pl-10 pr-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-monc-accent focus:border-monc-accent outline-none transition-colors"
                               placeholder="Enter your password">
                    </div>
                </div>

                <div class="flex items-center justify-between mb-6">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="remember" class="w-4 h-4 text-monc-accent border-slate-300 rounded focus:ring-monc-accent">
                        <span class="text-sm text-slate-600">Remember me</span>
                    </label>
                </div>

                <button type="submit" class="w-full bg-monc-accent hover:bg-blue-600 text-white font-medium py-2.5 rounded-lg transition-colors flex items-center justify-center gap-2">
                    <i class="fas fa-sign-in-alt"></i>
                    Sign In
                </button>
            </form>
        </div>

        <p class="text-center text-slate-500 text-xs mt-6">
            MonC - Monitoring CCTV System &copy; {{ date('Y') }}<br>
            Direktorat Jenderal Bea dan Cukai
        </p>
    </div>
</body>
</html>
