<!DOCTYPE html>
<html>
<head>
    <title>Test Login Simple</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body { font-family: Arial; padding: 50px; }
        .form-group { margin: 20px 0; }
        input, button { padding: 10px; width: 300px; }
        button { background: #007cba; color: white; border: none; cursor: pointer; }
        .success { color: green; }
        .error { color: red; }
    </style>
</head>
<body>
    <h1>Test Login Simple (Sin Inertia)</h1>
    
    @if(session('success'))
        <div class="success">{{ session('success') }}</div>
    @endif
    
    @if($errors->any())
        <div class="error">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    
    <form method="POST" action="{{ route('login') }}">
        @csrf
        <div class="form-group">
            <label>Email:</label><br>
            <input type="email" name="email" value="admin@consultorio.com" required>
        </div>
        
        <div class="form-group">
            <label>Password:</label><br>
            <input type="password" name="password" value="password" required>
        </div>
        
        <div class="form-group">
            <button type="submit">Login</button>
        </div>
    </form>
    
    <hr>
    <h3>Info Debug:</h3>
    <p>CSRF Token: {{ csrf_token() }}</p>
    <p>Session ID: {{ session()->getId() }}</p>
    <p>APP_URL: {{ config('app.url') }}</p>
    
    @if(Auth::check())
        <p><strong>Usuario autenticado:</strong> {{ Auth::user()->name }} ({{ Auth::user()->email }})</p>
        <a href="{{ route('dashboard') }}">Ir al Dashboard</a> | 
        <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">Logout</a>
        
        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
            @csrf
        </form>
    @endif
</body>
</html>
