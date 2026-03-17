<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard</title>
    <style>body{font-family:Arial, sans-serif;padding:2rem}</style>
</head>
<body>
    <h1>Dashboard</h1>
    <p>Welcome, {{ $user->name }}.</p>
    <p>Role: {{ $user->role_name }}</p>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit">Logout</button>
    </form>
</body>
</html>
