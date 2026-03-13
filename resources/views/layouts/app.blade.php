<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Attendance')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
</head>
<body class="min-h-screen bg-gradient-to-br from-[#07292d] to-[#0f3b40] text-white antialiased">
    @yield('content')
</body>
</html>
