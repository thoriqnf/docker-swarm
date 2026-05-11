<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register — TodoSwarm</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            background: #0f172a;
            color: #f1f5f9;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .auth-card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 16px;
            padding: 40px;
            width: 100%;
            max-width: 440px;
            box-shadow: 0 24px 48px rgba(0,0,0,0.4);
        }
        .auth-logo { text-align: center; margin-bottom: 28px; }
        .logo-icon {
            width: 52px; height: 52px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            border-radius: 14px;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 26px; margin-bottom: 12px;
        }
        .auth-logo h1 { font-size: 22px; font-weight: 700; }
        .auth-logo p { font-size: 13px; color: #64748b; margin-top: 4px; }
        .form-group { margin-bottom: 14px; }
        .form-label { display: block; font-size: 12.5px; font-weight: 500; color: #94a3b8; margin-bottom: 6px; }
        .form-control {
            width: 100%;
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: 8px;
            color: #f1f5f9;
            padding: 10px 14px;
            font-size: 14px;
            font-family: inherit;
        }
        .form-control:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.15); }
        .form-error { font-size: 11.5px; color: #f87171; margin-top: 4px; }
        .btn-submit {
            width: 100%; padding: 11px;
            background: #6366f1; color: white;
            border: none; border-radius: 8px;
            font-size: 14px; font-weight: 600;
            cursor: pointer; font-family: inherit; margin-top: 8px;
            transition: background 0.15s;
        }
        .btn-submit:hover { background: #4f46e5; }
        .auth-footer { text-align: center; margin-top: 20px; font-size: 13px; color: #64748b; }
        .auth-footer a { color: #6366f1; text-decoration: none; }
        .auth-footer a:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="auth-card">
    <div class="auth-logo">
        <div class="logo-icon">✓</div>
        <h1>TodoSwarm</h1>
        <p>Create your account</p>
    </div>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <div class="form-group">
            <label class="form-label" for="name">Full Name</label>
            <input id="name" type="text" name="name" class="form-control"
                   value="{{ old('name') }}" placeholder="Your name" required autofocus>
            @error('name') <div class="form-error">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label class="form-label" for="email">Email</label>
            <input id="email" type="email" name="email" class="form-control"
                   value="{{ old('email') }}" placeholder="you@example.com" required>
            @error('email') <div class="form-error">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label class="form-label" for="password">Password</label>
            <input id="password" type="password" name="password" class="form-control"
                   placeholder="Min. 8 characters" required>
            @error('password') <div class="form-error">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
            <label class="form-label" for="password_confirmation">Confirm Password</label>
            <input id="password_confirmation" type="password" name="password_confirmation"
                   class="form-control" placeholder="Repeat password" required>
        </div>

        <button type="submit" class="btn-submit">Create Account →</button>
    </form>

    <div class="auth-footer">
        Already have an account?
        <a href="{{ route('login') }}">Sign in</a>
    </div>
</div>
</body>
</html>
