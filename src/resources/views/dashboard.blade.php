@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div style="max-width: 800px; margin: 0 auto;">
    <h1 style="margin-bottom: 2rem; font-size: 2.5rem;">Dashboard</h1>

    <div class="card">
        <h2 style="margin-bottom: 1rem; font-size: 1.5rem;">Welcome, {{ $user->name }}!</h2>
        <p style="margin-bottom: 1rem;">You are successfully logged in to your account.</p>
        
        <div style="background-color: #f8f9fa; padding: 1rem; border-radius: 4px; margin-top: 1.5rem;">
            <h3 style="margin-bottom: 0.5rem; font-size: 1.25rem;">Account Information</h3>
            <p><strong>Name:</strong> {{ $user->name }}</p>
            <p><strong>Email:</strong> {{ $user->email }}</p>
            <p><strong>Member since:</strong> {{ $user->created_at->format('F j, Y') }}</p>
        </div>
    </div>

    <div class="card">
        <h2 style="margin-bottom: 1rem; font-size: 1.5rem;">Quick Actions</h2>
        <p>This is your dashboard. You can add more features and functionality here as your application grows.</p>
    </div>
</div>
@endsection

