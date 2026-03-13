<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 2rem; }
        .container { max-width: 400px; margin: 0 auto; }
        label { display:block; margin-top: 1rem; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Login</h1>

        @if($errors->any())
            <div style="color:red">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('login.attempt') }}">
            @csrf
            <label>Username
                <input type="text" name="username" value="{{ old('username') }}" required>
            </label>

            <label>Password
                <input type="password" name="password" required>
            </label>

            <div style="margin-top:1rem">
                <button type="submit">Login</button>
            </div>
        </form>
    </div>
</body>
</html>
