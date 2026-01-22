<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - Khaservice HR</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('assets/css/admin_style.css') }}">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    @yield('styles')
</head>
<body class="{{ session('dark_mode') ? 'dark-mode' : '' }}">
    <div class="wrapper">
        <!-- Sidebar -->
        @include('partials.sidebar')

        <div class="main-content">
            <!-- Topbar -->
            @include('partials.topbar')

            <div class="content-wrapper">
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif

                @yield('content')
            </div>

            <!-- Footer -->
            <footer class="main-footer">
                &copy; {{ date('Y') }} Khaservice IT. Toàn bộ mã nguồn được bảo vệ.
            </footer>
        </div>
    </div>

    <script src="{{ asset('assets/js/main.js') }}"></script>
    @yield('scripts')
</body>
</html>
