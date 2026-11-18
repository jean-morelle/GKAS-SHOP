<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('code') — @yield('title')</title>
    <style>
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #f8fafc; color: #1e293b; font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif; padding: 1rem; }
        .box { max-width: 28rem; text-align: center; background: #fff; border: 1px solid #e2e8f0; border-radius: 1rem; padding: 2.5rem 2rem; box-shadow: 0 1px 2px rgba(0,0,0,.05); }
        .code { font-size: 3rem; font-weight: 800; color: #2546ea; margin: 0; }
        h1 { font-size: 1.25rem; margin: .5rem 0; }
        p { color: #64748b; line-height: 1.5; }
        a { display: inline-block; margin-top: 1.25rem; background: #2546ea; color: #fff; padding: .65rem 1.25rem; border-radius: .5rem; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
    <div class="box">
        <p class="code">@yield('code')</p>
        <h1>@yield('title')</h1>
        <p>@yield('message')</p>
        <a href="{{ url('/') }}">Retour à l'accueil</a>
    </div>
</body>
</html>
