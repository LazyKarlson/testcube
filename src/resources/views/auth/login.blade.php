@extends('layouts.app')

@section('title', 'Login')

@section('content')
<div style="max-width: 500px; margin: 3rem auto;">
    <div class="card">
        <h1 style="margin-bottom: 1.5rem; font-size: 2rem; text-align: center;">Login</h1>

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div class="form-group">
                <label for="email">Email Address</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    value="{{ old('email') }}" 
                    required 
                    autofocus
                >
                @error('email')
                    <div class="text-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    required
                >
                @error('password')
                    <div class="text-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <div class="checkbox-group">
                    <input 
                        type="checkbox" 
                        id="remember" 
                        name="remember"
                    >
                    <label for="remember" style="margin-bottom: 0;">Remember Me</label>
                </div>
            </div>

            <div class="form-group">
                <button type="submit" class="btn" style="width: 100%;">Login</button>
            </div>

            <div class="text-center mt-2">
                <p>Don't have an account? <a href="{{ route('register') }}" style="color: #FF2D20;">Register here</a></p>
            </div>
        </form>
    </div>
</div>
@endsection

