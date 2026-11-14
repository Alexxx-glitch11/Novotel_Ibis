<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk Admin · RRMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>
<body class="admin-app admin-login-page">
    <main class="admin-login-card">
        <div class="admin-login-brand"><img src="{{ asset('images/NovoLogo.png') }}" alt="Novotel"><img src="{{ asset('images/IbisLogo.png') }}" alt="ibis"></div>
        <p class="admin-eyebrow">RESERVATION MANAGEMENT</p>
        <h1>Masuk Admin</h1>
        <p class="admin-login-sub">Masuk dengan akun internal ber-role Admin.</p>
        @if ($errors->any())<div class="admin-alert is-error" role="alert">{{ $errors->first() }}</div>@endif
        <form method="post" action="{{ route('admin.login.store') }}">
            @csrf
            <label>Email<input type="email" name="email" value="{{ old('email') }}" autocomplete="username" required autofocus></label>
            <label>Kata sandi<input type="password" name="password" autocomplete="current-password" required></label>
            <label class="admin-checkbox"><input type="checkbox" name="remember" value="1"> Ingat saya</label>
            <button class="admin-button admin-primary admin-full-button">Masuk</button>
        </form>
    </main>
</body>
</html>
