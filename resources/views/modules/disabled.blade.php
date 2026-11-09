{{--
    Página de bloqueo de un módulo (interruptor de módulos).

    Variables que entrega App\Http\Middleware\EnsureModuleEnabled:
      $module     clave del módulo (ej. "credits")
      $label      nombre visible traducido
      $message    aviso para el usuario, desde lang/{locale}/modules.php
      $state      enabled | preview | off
      $canBypass  true solo para el rol de modules.bypass_role

    Autocontenida a propósito: sin Vite, sin Tailwind, sin componentes de
    Filament. Tiene que seguir saliendo aunque el panel esté roto.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="color-scheme" content="light dark">
    <title>{{ $label }} — {{ __('modules.blocked.title') }}</title>
    <style>
        :root {
            /* verdes */
            --g-900: #05372a;
            --g-800: #065f46;
            --g-700: #047857;
            --g-600: #059669;
            --g-500: #10b981;
            --g-400: #34d399;
            --g-200: #a7f3d0;
            --g-100: #d1fae5;
            --g-50:  #ecfdf5;

            /* apoyos análogos: teal y lima */
            --teal-600: #0d9488;
            --teal-100: #ccfbf1;

            /* neutros con tinte verde */
            --ink:     #0b2018;
            --muted:   #5c736a;
            --soft:    #8aa39a;
            --line:    #d9ebe2;
            --surface: #ffffff;
            --bg:      #f2f8f4;
            --shadow:  0 1px 2px rgba(6,95,70,.05), 0 22px 45px -22px rgba(6,95,70,.28);
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --ink:     #e6f4ec;
                --muted:   #93a89f;
                --soft:    #6d857c;
                --line:    #1d3128;
                --surface: #0e1b16;
                --bg:      #07120e;
                --g-50:    #0c2620;
                --g-100:   #10352b;
                --g-200:   #155e48;
                --teal-100:#082f2b;
                --shadow:  0 1px 2px rgba(0,0,0,.4), 0 22px 45px -22px rgba(0,0,0,.7);
            }
        }

        *, *::before, *::after { box-sizing: border-box; }

        html, body { min-height: 100%; }

        body {
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 28px 20px;
            background:
                radial-gradient(900px 420px at 50% -8%, var(--g-100) 0%, transparent 62%),
                var(--bg);
            color: var(--ink);
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, Inter, Arial, sans-serif;
            font-size: 15px;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }

        .card {
            position: relative;
            width: 100%;
            max-width: 620px;
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 16px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        /* hairline superior con la paleta */
        .card::before {
            content: "";
            position: absolute;
            inset: 0 0 auto 0;
            height: 4px;
            background: linear-gradient(90deg, var(--g-500), var(--teal-600) 55%, #84cc16);
        }

        .head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            padding: 18px 32px;
            border-bottom: 1px solid var(--line);
            background: linear-gradient(180deg, var(--g-50), transparent);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
            font-weight: 600;
            color: var(--g-800);
        }

        @media (prefers-color-scheme: dark) { .brand { color: var(--g-200); } }

        .brand-mark {
            width: 26px; height: 26px;
            display: grid; place-items: center;
            border-radius: 8px;
            background: linear-gradient(150deg, var(--g-500), var(--teal-600));
            color: #fff;
            box-shadow: 0 2px 6px rgba(5,150,105,.35);
        }

        .brand span.sub { font-weight: 500; color: var(--muted); }

        .pill {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 5px 11px;
            border-radius: 999px;
            background: var(--g-50);
            border: 1px solid var(--g-200);
            color: var(--g-800);
            font-size: 11.5px;
            font-weight: 650;
            letter-spacing: .06em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        @media (prefers-color-scheme: dark) { .pill { color: var(--g-200); } }

        .pill .dot {
            width: 6px; height: 6px;
            border-radius: 50%;
            background: var(--g-500);
            box-shadow: 0 0 0 3px rgba(16,185,129,.22);
            animation: pulse 2.4s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50%      { opacity: .55; transform: scale(.85); }
        }

        @media (prefers-reduced-motion: reduce) { .pill .dot { animation: none; } }

        .body { padding: 30px 32px 28px; }

        .hero { display: flex; gap: 16px; align-items: flex-start; }

        .icon {
            flex: 0 0 auto;
            width: 52px; height: 52px;
            display: grid; place-items: center;
            border-radius: 14px;
            background: var(--g-50);
            border: 1px solid var(--g-200);
            color: var(--g-700);
        }

        @media (prefers-color-scheme: dark) { .icon { color: var(--g-400); } }

        h1 {
            margin: 4px 0 0;
            font-size: 23px;
            line-height: 1.28;
            font-weight: 650;
            letter-spacing: -.015em;
            color: var(--ink);
        }

        p.msg {
            margin: 16px 0 0;
            color: var(--muted);
            font-size: 15px;
            line-height: 1.7;
        }

        .checks {
            margin: 22px 0 0;
            padding: 16px 18px;
            list-style: none;
            display: grid;
            gap: 10px;
            background: var(--g-50);
            border: 1px solid var(--line);
            border-radius: 12px;
        }

        .checks li {
            display: grid;
            grid-template-columns: 18px 1fr;
            gap: 10px;
            align-items: start;
            font-size: 13.5px;
            color: var(--muted);
        }

        .checks svg { color: var(--g-600); margin-top: 2px; }
        .checks strong { color: var(--ink); font-weight: 600; }

        .meta { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 22px; }

        .chip {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 6px 10px;
            border-radius: 8px;
            border: 1px solid var(--line);
            background: var(--surface);
            font-size: 12px;
            color: var(--muted);
        }

        .chip b { font-weight: 600; color: var(--ink); }

        .chip code {
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            font-size: 11.5px;
            color: var(--g-700);
        }

        @media (prefers-color-scheme: dark) { .chip code { color: var(--g-400); } }

        .actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 26px; }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            font-family: inherit;
            text-decoration: none;
            cursor: pointer;
            transition: background .16s ease, border-color .16s ease, transform .06s ease;
        }

        .btn:active { transform: translateY(1px); }

        .btn-primary {
            background: var(--g-600);
            border: 1px solid var(--g-700);
            color: #fff;
            box-shadow: 0 1px 2px rgba(4,120,87,.25), inset 0 1px 0 rgba(255,255,255,.12);
        }

        .btn-primary:hover { background: var(--g-700); }

        .btn-ghost { background: transparent; border: 1px solid var(--line); color: var(--muted); }
        .btn-ghost:hover { background: var(--g-50); border-color: var(--g-200); color: var(--g-800); }

        @media (prefers-color-scheme: dark) { .btn-ghost:hover { color: var(--g-200); } }

        .btn:focus-visible { outline: 2px solid var(--g-500); outline-offset: 2px; }

        .admin-note {
            margin-top: 22px;
            padding: 14px 16px 15px;
            border-radius: 12px;
            border: 1px solid var(--g-200);
            border-left: 3px solid var(--teal-600);
            background: var(--teal-100);
            color: var(--g-900);
            font-size: 13px;
            line-height: 1.65;
        }

        @media (prefers-color-scheme: dark) { .admin-note { color: var(--ink); } }

        .admin-note strong { font-weight: 650; }

        .admin-note code {
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            font-size: 11.5px;
            padding: 2px 5px;
            border-radius: 5px;
            background: rgba(5,150,105,.14);
            color: var(--g-800);
        }

        .admin-note code.cmd {
            display: block;
            margin-top: 9px;
            padding: 10px 12px;
            border-radius: 8px;
            border: 1px solid var(--line);
            background: var(--surface);
            color: var(--g-700);
            white-space: nowrap;
            overflow-x: auto;
        }

        @media (prefers-color-scheme: dark) {
            .admin-note code { color: var(--g-400); background: rgba(16,185,129,.12); }
            .admin-note code.cmd { color: var(--g-400); }
        }

        footer {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 14px 32px 16px;
            border-top: 1px solid var(--line);
            background: linear-gradient(180deg, transparent, var(--g-50));
            font-size: 12px;
            color: var(--soft);
        }

        footer .dot-live { width: 6px; height: 6px; border-radius: 50%; background: var(--g-500); }

        @media (max-width: 560px) {
            .head { padding: 16px 20px; }
            .body { padding: 24px 20px; }
            footer { padding: 14px 20px; }
            h1 { font-size: 20px; }
            .hero { gap: 13px; }
            .icon { width: 46px; height: 46px; }
            .brand span.sub { display: none; }
        }
    </style>
</head>
<body>
    <main class="card" role="main" aria-labelledby="titulo">
        <div class="head">
            <div class="brand">
                <span class="brand-mark" aria-hidden="true">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M13 2 4.5 13.5H11l-1 8.5 8.5-11.5H12l1-8.5Z"></path>
                    </svg>
                </span>
                <span>{{ config('app.name') }} <span class="sub">· {{ __('modules.blocked.panel') }}</span></span>
            </div>

            <span class="pill"><span class="dot"></span>{{ __('modules.states.' . $state) }}</span>
        </div>

        <div class="body">
            <div class="hero">
                <div class="icon" aria-hidden="true">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 3h6"></path>
                        <path d="M10 3v5.5L5.2 17.2A2.2 2.2 0 0 0 7.1 20.5h9.8a2.2 2.2 0 0 0 1.9-3.3L14 8.5V3"></path>
                        <path d="M7.5 15h9"></path>
                    </svg>
                </div>

                <div>
                    <h1 id="titulo">{{ $label }}</h1>
                </div>
            </div>

            <p class="msg">{{ $message }}</p>

            <ul class="checks">
                @foreach (['lost_nothing', 'rest_works', 'reopens'] as $fact)
                    <li>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M20 6 9 17l-5-5"></path>
                        </svg>
                        <span>
                            <strong>{{ __('modules.blocked.' . $fact . '.title') }}</strong>
                            {{ __('modules.blocked.' . $fact . '.text') }}
                        </span>
                    </li>
                @endforeach
            </ul>

            <div class="meta">
                <span class="chip">{{ __('modules.blocked.status') }} <b>{{ __('modules.states.' . $state) }}</b></span>
                <span class="chip">{{ __('modules.blocked.module') }} <code>{{ $module }}</code></span>
                <span class="chip">{{ __('modules.blocked.code') }} <b>{{ __('modules.blocked.code_value') }}</b></span>
            </div>

            <div class="actions">
                <a class="btn btn-primary" href="{{ url(config('modules.panel_path')) }}">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M19 12H5"></path><path d="M12 19l-7-7 7-7"></path>
                    </svg>
                    {{ __('modules.blocked.home') }}
                </a>
                <button class="btn btn-ghost" type="button" onclick="location.reload()">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M21 12a9 9 0 1 1-3-6.7"></path><path d="M21 3v6h-6"></path>
                    </svg>
                    {{ __('modules.blocked.retry') }}
                </button>
            </div>

            @if ($canBypass)
                <div class="admin-note">
                    <strong>{{ __('modules.admin.badge') }}</strong>
                    {{ __('modules.admin.explain', ['module' => $module, 'state' => __('modules.states.' . $state)]) }}
                    <code class="cmd">{{ __('modules.admin.command', ['module' => $module]) }}</code>
                    {{ __('modules.admin.hint') }}
                </div>
            @endif
        </div>

        <footer>
            <span class="dot-live" aria-hidden="true"></span>
            {{ __('modules.blocked.footer') }}
        </footer>
    </main>
</body>
</html>
