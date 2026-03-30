<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset password — PESO Connect</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --peso-blue: #2563eb;
            --peso-blue-dark: #1d4ed8;
            --navy: #0f172a;
            --slate: #64748b;
            --slate-light: #94a3b8;
            --border: #e2e8f0;
            --surface: #ffffff;
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
        .shell {
            width: 100%;
            max-width: 440px;
        }
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
            flex-shrink: 0;
            box-shadow: 0 8px 24px rgba(37, 99, 235, 0.35);
        }
        .brand-mark svg { width: 26px; height: 26px; color: #fff; }
        .brand-text .name {
            font-size: 1.25rem;
            font-weight: 800;
            letter-spacing: 0.04em;
            color: var(--navy);
            line-height: 1.2;
        }
        .brand-text .tag {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--peso-blue);
            opacity: 0.9;
        }
        .card {
            background: var(--surface);
            border-radius: 20px;
            padding: 28px 26px 30px;
            box-shadow:
                0 1px 3px rgba(15, 23, 42, 0.06),
                0 20px 50px -12px rgba(15, 23, 42, 0.12);
            border: 1px solid rgba(226, 232, 240, 0.9);
        }
        h1 {
            font-size: 1.375rem;
            font-weight: 800;
            margin: 0 0 8px;
            letter-spacing: -0.02em;
        }
        p.sub {
            margin: 0 0 22px;
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--slate);
            line-height: 1.55;
        }
        .email-pill {
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--slate);
            background: #f1f5f9;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 10px 14px;
            margin-bottom: 20px;
            word-break: break-all;
        }
        .email-pill span { color: var(--slate-light); font-weight: 500; margin-right: 6px; }
        label {
            display: block;
            font-size: 0.8125rem;
            font-weight: 600;
            color: #475569;
            margin-bottom: 8px;
        }
        .input-shell {
            position: relative;
        }
        .input-shell input {
            width: 100%;
            padding: 14px 48px 14px 16px;
            border-radius: 14px;
            border: 1.5px solid var(--border);
            font-size: 1rem;
            font-family: inherit;
            background: #fff;
            transition: border-color 0.15s, box-shadow 0.15s;
        }
        .input-shell input::placeholder { color: #cbd5e1; }
        .input-shell input:hover { border-color: #cbd5e1; }
        .input-shell input:focus {
            outline: none;
            border-color: var(--peso-blue);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
        }
        .pw-toggle {
            position: absolute;
            right: 4px;
            top: 50%;
            transform: translateY(-50%);
            width: 40px;
            height: 40px;
            border: none;
            background: transparent;
            border-radius: 10px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--slate);
        }
        .pw-toggle:hover { background: #f1f5f9; color: var(--navy); }
        .pw-toggle svg { width: 22px; height: 22px; }
        .pw-toggle .ic-hide { display: none; }
        .pw-toggle.is-visible .ic-show { display: none; }
        .pw-toggle.is-visible .ic-hide { display: block; }
        .field { margin-bottom: 18px; }
        .btn {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 14px;
            background: var(--peso-blue);
            color: #fff;
            font-size: 1rem;
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
            margin-top: 6px;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.35);
            transition: background 0.15s, transform 0.1s;
        }
        .btn:hover { background: var(--peso-blue-dark); }
        .btn:active { transform: scale(0.99); }
        .foot-note {
            margin-top: 22px;
            text-align: center;
            font-size: 0.75rem;
            color: var(--slate-light);
            font-weight: 500;
        }
        .alert {
            padding: 12px 14px;
            border-radius: 12px;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 16px;
            line-height: 1.45;
        }
        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
        }
        .req-block {
            margin-top: 10px;
            margin-bottom: 6px;
            font-size: 0.8125rem;
        }
        .req-title {
            font-weight: 700;
            color: #64748b;
            margin-bottom: 8px;
        }
        .req-row {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            margin-bottom: 5px;
            color: #64748b;
            font-weight: 600;
        }
        .req-row svg {
            width: 16px;
            height: 16px;
            flex-shrink: 0;
            margin-top: 1px;
        }
        .req-row.ok { color: #166534; }
        .req-row.ok svg { color: #16a34a; }
        .live-err {
            margin-top: 8px;
            font-size: 0.8125rem;
            font-weight: 600;
            color: #b91c1c;
            min-height: 1.25rem;
        }
        .live-err:empty { display: none; }
        .input-shell input.input-bad {
            border-color: #f87171;
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
            <h1>Reset your password</h1>
            <p class="sub">Same account as PESO Connect — choose a strong password you haven’t used elsewhere.</p>

            @if (!empty($linkError))
                <div class="alert alert-error">{{ $linkError }}</div>
            @else
                <div class="email-pill"><span>Signing in as</span>{{ $email }}</div>

                @if ($errors->any())
                    <div class="alert alert-error">
                        @foreach ($errors->all() as $err)
                            <div>{{ $err }}</div>
                        @endforeach
                    </div>
                @endif

                <form id="reset-form" method="post" action="{{ route('jobseeker.password.reset.submit') }}" novalidate>
                    @csrf
                    <input type="hidden" name="token" value="{{ e($token) }}">
                    <input type="hidden" name="email" value="{{ e($email) }}">

                    <div class="field">
                        <label for="password">New password</label>
                        <div class="input-shell">
                            <input id="password" name="password" type="password" required autocomplete="new-password" placeholder="Strong password (see rules below)">
                            <button type="button" class="pw-toggle" data-for="password" aria-label="Show password">
                                <svg class="ic-show" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                <svg class="ic-hide" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
                            </button>
                        </div>
                        <div class="req-block" id="pw-req" aria-live="polite">
                            <div class="req-title">Password must include:</div>
                            <div class="req-row" data-rule="len"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg><span>At least 8 characters</span></div>
                            <div class="req-row" data-rule="mixed"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg><span>Uppercase &amp; lowercase letters</span></div>
                            <div class="req-row" data-rule="num"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg><span>At least one number</span></div>
                            <div class="req-row" data-rule="sym"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg><span>At least one special character</span></div>
                        </div>
                        <div class="live-err" id="pw-str-err"></div>
                    </div>
                    <div class="field">
                        <label for="password_confirmation">Confirm new password</label>
                        <div class="input-shell">
                            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" placeholder="Re-enter password">
                            <button type="button" class="pw-toggle" data-for="password_confirmation" aria-label="Show password">
                                <svg class="ic-show" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                <svg class="ic-hide" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
                            </button>
                        </div>
                        <div class="live-err" id="pw-match-err"></div>
                    </div>
                    <button type="submit" class="btn">Update password</button>
                </form>
                <p class="foot-note">Opened from your email — you can close this tab after updating.</p>
            @endif
        </div>
    </div>
    <script>
        (function () {
            var checkIcon = '<path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />';
            var circleIcon = '<circle cx="12" cy="12" r="10"/>';
            function pwOk(p) {
                return {
                    len: p.length >= 8,
                    mixed: /[A-Z]/.test(p) && /[a-z]/.test(p),
                    num: /[0-9]/.test(p),
                    sym: /[^A-Za-z0-9]/.test(p)
                };
            }
            function updateReq() {
                var pw = document.getElementById('password');
                var p = pw ? pw.value : '';
                var o = pwOk(p);
                document.querySelectorAll('#pw-req .req-row').forEach(function (row) {
                    var k = row.getAttribute('data-rule');
                    var ok = o[k];
                    row.classList.toggle('ok', ok);
                    var svg = row.querySelector('svg');
                    if (svg) svg.innerHTML = ok ? checkIcon : circleIcon;
                });
                var err = document.getElementById('pw-str-err');
                var allOk = o.len && o.mixed && o.num && o.sym;
                if (err) {
                    if (p.length === 0) err.textContent = '';
                    else err.textContent = allOk ? '' : 'Complete all password rules above.';
                }
                if (pw) pw.classList.toggle('input-bad', p.length > 0 && !allOk);
            }
            function updateMatch() {
                var a = document.getElementById('password');
                var b = document.getElementById('password_confirmation');
                var el = document.getElementById('pw-match-err');
                if (!a || !b || !el) return;
                if (b.value.length === 0) {
                    el.textContent = '';
                    b.classList.remove('input-bad');
                    return;
                }
                if (a.value !== b.value) {
                    el.textContent = 'Passwords do not match';
                    b.classList.add('input-bad');
                } else {
                    el.textContent = '';
                    b.classList.remove('input-bad');
                }
            }
            function validateForm(e) {
                var a = document.getElementById('password');
                var b = document.getElementById('password_confirmation');
                var o = pwOk(a.value);
                if (!o.len || !o.mixed || !o.num || !o.sym) {
                    e.preventDefault();
                    document.getElementById('pw-str-err').textContent = 'Please meet all password requirements.';
                    a.focus();
                    return false;
                }
                if (a.value !== b.value) {
                    e.preventDefault();
                    document.getElementById('pw-match-err').textContent = 'Passwords do not match';
                    b.focus();
                    return false;
                }
            }
            document.querySelectorAll('.pw-toggle').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var id = btn.getAttribute('data-for');
                    var input = document.getElementById(id);
                    if (!input) return;
                    var show = input.type === 'password';
                    input.type = show ? 'text' : 'password';
                    btn.classList.toggle('is-visible', show);
                    btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
                });
            });
            var pEl = document.getElementById('password');
            var cEl = document.getElementById('password_confirmation');
            if (pEl) pEl.addEventListener('input', function () { updateReq(); updateMatch(); });
            if (cEl) cEl.addEventListener('input', updateMatch);
            updateReq();
            var form = document.getElementById('reset-form');
            if (form) form.addEventListener('submit', validateForm);
        })();
    </script>
</body>
</html>
