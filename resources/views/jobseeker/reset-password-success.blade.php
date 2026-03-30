<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Password updated — PESO Connect</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --peso-blue: #2563eb;
            --navy: #0f172a;
            --slate: #64748b;
            --page-bg: #f8fafc;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Poppins', system-ui, sans-serif;
            color: var(--navy);
            background: linear-gradient(165deg, #eff6ff 0%, var(--page-bg) 45%, #f1f5f9 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
        }
        .shell { width: 100%; max-width: 440px; }
        .brand {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 20px;
        }
        .brand-mark {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            background: linear-gradient(135deg, #2563eb, #3b82f6);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 24px rgba(37, 99, 235, 0.35);
        }
        .brand-mark svg { width: 26px; height: 26px; color: #fff; }
        .brand-text .name { font-size: 1.25rem; font-weight: 800; letter-spacing: 0.04em; }
        .brand-text .tag { font-size: 0.75rem; font-weight: 600; color: var(--peso-blue); }
        .card {
            background: #fff;
            border-radius: 20px;
            padding: 36px 28px;
            text-align: center;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06), 0 20px 50px -12px rgba(15, 23, 42, 0.12);
            border: 1px solid rgba(226, 232, 240, 0.9);
        }
        .ok {
            width: 64px; height: 64px; margin: 0 auto 22px;
            border-radius: 50%;
            background: linear-gradient(145deg, #dcfce7, #bbf7d0);
            color: #15803d;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            font-weight: 800;
            box-shadow: 0 8px 24px rgba(22, 163, 74, 0.2);
        }
        h1 { font-size: 1.375rem; font-weight: 800; margin: 0 0 12px; letter-spacing: -0.02em; }
        p { margin: 0; font-size: 0.95rem; font-weight: 500; color: var(--slate); line-height: 1.6; }
        .hint {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            font-size: 0.8125rem;
            color: #94a3b8;
        }
    </style>
</head>
<body>
    <div class="shell">
        <div class="brand">
            <div class="brand-mark" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
            </div>
            <div class="brand-text">
                <div class="name">PESO Connect</div>
                <div class="tag">Jobseeker</div>
            </div>
        </div>
        <div class="card">
            <div class="ok" aria-hidden="true">✓</div>
            <h1>Password updated</h1>
            <p>Your account password is saved. Open <strong>PESO Connect</strong> and sign in with your new password.</p>
            <p class="hint">You can close this browser tab.</p>
        </div>
    </div>
</body>
</html>
