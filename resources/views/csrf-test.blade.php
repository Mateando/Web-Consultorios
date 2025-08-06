<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Test CSRF - Consultorio</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-lg shadow-md w-96">
        <h1 class="text-2xl font-bold mb-6 text-center">Test Login CSRF</h1>
        
        <!-- Formulario de Login Simple -->
        <form method="POST" action="/login" class="mb-6">
            @csrf
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">
                    Email
                </label>
                <input type="email" name="email" value="admin@consultorio.com" required
                       class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">
                    Password
                </label>
                <input type="password" name="password" value="password" required
                       class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
            </div>
            <button type="submit" class="w-full bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                Login con Admin
            </button>
        </form>
        
        <div class="mb-4">
            <button onclick="clearCookies()" class="w-full bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 mb-4">
                Limpiar Cookies y Storage
            </button>
            
            <button onclick="testCsrf()" class="w-full bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 mb-4">
                Test CSRF Token
            </button>
            
            <a href="/login" class="block w-full bg-green-500 text-white text-center px-4 py-2 rounded hover:bg-green-600 mb-2">
                Ir a Login Inertia
            </a>
            
            <a href="/simple-login" class="block w-full bg-purple-500 text-white text-center px-4 py-2 rounded hover:bg-purple-600">
                Ir a Login Simple
            </a>
        </div>
        
        <div id="result" class="mt-4 p-4 rounded"></div>
        
        @if($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    <script>
        function clearCookies() {
            // Limpiar todas las cookies
            document.cookie.split(";").forEach(function(c) { 
                document.cookie = c.replace(/^ +/, "").replace(/=.*/, "=;expires=" + new Date().toUTCString() + ";path=/"); 
            });
            
            // Limpiar localStorage y sessionStorage
            localStorage.clear();
            sessionStorage.clear();
            
            document.getElementById('result').innerHTML = '<div class="bg-yellow-100 text-yellow-800 p-2 rounded">Cookies y storage limpiados. Prueba hacer login nuevamente.</div>';
        }
        
        async function testCsrf() {
            try {
                const response = await fetch('/csrf-test');
                const data = await response.json();
                
                document.getElementById('result').innerHTML = `
                    <div class="bg-green-100 text-green-800 p-2 rounded">
                        <strong>CSRF Token activo:</strong><br>
                        Token: ${data.csrf_token.substring(0, 20)}...<br>
                        Session ID: ${data.session_id.substring(0, 20)}...<br>
                        App URL: ${data.app_url}
                    </div>
                `;
                
                // Actualizar el meta tag
                document.querySelector('meta[name="csrf-token"]').setAttribute('content', data.csrf_token);
            } catch (error) {
                document.getElementById('result').innerHTML = '<div class="bg-red-100 text-red-800 p-2 rounded">Error: ' + error.message + '</div>';
            }
        }
        
        // Actualizar token automáticamente cada 30 segundos
        setInterval(testCsrf, 30000);
    </script>
</body>
</html>
