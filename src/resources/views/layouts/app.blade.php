<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }} - @yield('title', 'Home')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    <!-- Styles / Scripts -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            body {
                font-family: 'Instrument Sans', system-ui, -apple-system, sans-serif;
                background-color: #FDFDFC;
                color: #1b1b18;
                line-height: 1.6;
            }
            .container {
                max-width: 1200px;
                margin: 0 auto;
                padding: 2rem;
            }
            .card {
                background: white;
                border-radius: 8px;
                padding: 2rem;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
                margin-bottom: 1.5rem;
            }
            .form-group {
                margin-bottom: 1.5rem;
            }
            label {
                display: block;
                margin-bottom: 0.5rem;
                font-weight: 500;
            }
            input[type="text"],
            input[type="email"],
            input[type="password"] {
                width: 100%;
                padding: 0.75rem;
                border: 1px solid #ddd;
                border-radius: 4px;
                font-size: 1rem;
            }
            input[type="text"]:focus,
            input[type="email"]:focus,
            input[type="password"]:focus {
                outline: none;
                border-color: #FF2D20;
            }
            .btn {
                display: inline-block;
                padding: 0.75rem 1.5rem;
                background-color: #FF2D20;
                color: white;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                font-size: 1rem;
                text-decoration: none;
                transition: background-color 0.2s;
            }
            .btn:hover {
                background-color: #e02818;
            }
            .btn-secondary {
                background-color: #6c757d;
            }
            .btn-secondary:hover {
                background-color: #5a6268;
            }
            .alert {
                padding: 1rem;
                border-radius: 4px;
                margin-bottom: 1.5rem;
            }
            .alert-success {
                background-color: #d4edda;
                color: #155724;
                border: 1px solid #c3e6cb;
            }
            .alert-error {
                background-color: #f8d7da;
                color: #721c24;
                border: 1px solid #f5c6cb;
            }
            .text-error {
                color: #721c24;
                font-size: 0.875rem;
                margin-top: 0.25rem;
            }
            .checkbox-group {
                display: flex;
                align-items: center;
                gap: 0.5rem;
            }
            .checkbox-group input[type="checkbox"] {
                width: auto;
            }
            .nav {
                background-color: white;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
                padding: 1rem 2rem;
                margin-bottom: 2rem;
            }
            .nav-content {
                max-width: 1200px;
                margin: 0 auto;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .nav-links {
                display: flex;
                gap: 1rem;
                align-items: center;
            }
            .nav-links a {
                color: #1b1b18;
                text-decoration: none;
                padding: 0.5rem 1rem;
            }
            .nav-links a:hover {
                color: #FF2D20;
            }
            .text-center {
                text-align: center;
            }
            .mt-2 {
                margin-top: 1rem;
            }
        </style>
    @endif
</head>
<body>
    @if(auth()->check())
    <nav class="nav">
        <div class="nav-content">
            <div>
                <a href="{{ route('dashboard') }}" style="font-weight: 600; font-size: 1.25rem; color: #FF2D20; text-decoration: none;">
                    {{ config('app.name', 'Laravel') }}
                </a>
            </div>
            <div class="nav-links">
                <span>{{ auth()->user()->name }}</span>
                <form action="{{ route('logout') }}" method="POST" style="display: inline;">
                    @csrf
                    <button type="submit" class="btn btn-secondary">Logout</button>
                </form>
            </div>
        </div>
    </nav>
    @endif

    <div class="container">
        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-error">
                {{ session('error') }}
            </div>
        @endif

        @yield('content')
    </div>
</body>
</html>

