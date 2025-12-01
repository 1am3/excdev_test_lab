<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }} - Вход</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0">
        <div>
            <a href="/">
                <h2 class="text-3xl font-bold text-gray-900">
                    Balance System
                </h2>
            </a>
        </div>

        <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
            <form method="POST" action="{{ route('login.authenticate') }}">
                @csrf

                <div>
                    <h2 class="text-2xl font-bold text-gray-900 text-center mb-6">Вход в систему</h2>
                </div>

                <!-- Session Status -->
                @if (session('status'))
                    <div class="mb-4 font-medium text-sm text-green-600">
                        {{ session('status') }}
                    </div>
                @endif

                <!-- Validation Errors -->
                @if ($errors->any())
                    <div class="mb-4">
                        <ul class="text-sm text-red-600 space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">
                        Email
                    </label>

                    <div class="mt-1">
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                               class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    </div>
                </div>

                <div class="mt-4">
                    <label for="password" class="block text-sm font-medium text-gray-700">
                        Пароль
                    </label>

                    <div class="mt-1">
                        <input id="password" type="password" name="password" required
                               class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    </div>
                </div>

                <div class="block mt-4">
                    <label for="remember_me" class="inline-flex items-center cursor-pointer">
                        <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="remember">
                        <span class="ml-2 text-sm text-gray-600">Запомнить меня</span>
                    </label>
                </div>

                <div class="flex items-center justify-end mt-4">
                    <button type="submit"
                            class="w-full inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 justify-center">
                        Войти
                    </button>
                </div>
            </form>

            <!-- Информация о системе -->
            <div class="mt-6 pt-6 border-t border-gray-200">
                <div class="text-center text-sm text-gray-600">
                    <p>Для создания учетной записи используйте команду:</p>
                    <code class="block mt-2 p-2 bg-gray-100 rounded text-xs">
                        php artisan user:create --name="Имя" --email="email@example.com" --with-balance
                    </code>
                </div>
            </div>
        </div>
    </div>

    <!-- Vue.js Example Component -->
    <div id="vue-example" class="fixed bottom-4 right-4 bg-indigo-500 hover:bg-indigo-600 text-white px-4 py-2 rounded-lg shadow-lg cursor-pointer transition-colors">
        Login Page Vue Example
    </div>

    <script src="https://cdn.jsdelivr.net/npm/vue@3/dist/vue.global.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Простая инициализация Vue без компонентов чтобы избежать конфликтов
            const exampleElement = document.getElementById('vue-example');
            if (exampleElement) {
                exampleElement.addEventListener('click', function() {
                    alert('test123');
                });
            }
        });
    </script>
</body>
</html>
