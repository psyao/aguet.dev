{{--
    Self-contained error page shell: zero @vite, zero Livewire, zero JS.
    Deliberately duplicates a hand-picked subset of app.css's default-theme
    tokens inline, so 500/503 (and every other code) can render even with
    no build output and a broken DB. See design doc for why: 403/404/419/429
    are not actually guaranteed to fire on a "healthy" app either.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>{{ $title }} — aguet.dev</title>
    <style>
        :root{--mono:'JetBrains Mono',ui-monospace,'SF Mono',monospace;--bg:#07100b;--fg:#e7efe9;--muted:#a6b5ab;--accent:#3ecf8e;--accent-ink:#04130b;--line:#1d2c24}
        *{box-sizing:border-box;margin:0;padding:0}
        html{color-scheme:dark}
        body{background:var(--bg);color:var(--fg);font-family:var(--mono);font-size:14px;line-height:1.65;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px}
        .box{max-width:520px;width:100%}
        .code{color:var(--accent);font-weight:700;font-size:13px;letter-spacing:.04em}
        h1{font-size:20px;font-weight:600;margin:8px 0 12px;color:var(--fg)}
        p{color:var(--muted);margin:0 0 20px}
        a{display:inline-flex;align-items:center;gap:8px;color:var(--accent-ink);background:var(--accent);font-family:var(--mono);font-size:13px;font-weight:600;text-decoration:none;border-radius:6px;padding:9px 14px;border:1px solid var(--line)}
        a:hover{opacity:.9}
    </style>
</head>
<body>
    <div class="box">
        <div class="code">{{ $code }}</div>
        <h1>{{ $title }}</h1>
        <p>{{ $message }}</p>
        <a href="{{ url('/') }}">{{ __('site.errors.home') }}</a>
    </div>
</body>
</html>
