<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ tenant()->name }} - Welcome</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] flex p-6 lg:p-8 items-center lg:justify-center min-h-screen flex-col">
        <div class="w-full lg:max-w-4xl max-w-[335px] text-sm mb-6">
            <nav class="flex items-center justify-end gap-4">
                @auth
                    <a href="/admin/tenant-dashboard" 
                       class="inline-block px-5 py-1.5 dark:text-[#EDEDEC] border-[#19140035] hover:border-[#1915014a] border text-[#1b1b18] dark:border-[#3E3E3A] dark:hover:border-[#62605b] rounded-sm text-sm leading-normal">
                        Dashboard
                    </a>
                @else
                    <a href="/login"
                       class="inline-block px-5 py-1.5 dark:text-[#EDEDEC] border-[#19140035] hover:border-[#1915014a] border text-[#1b1b18] dark:border-[#3E3E3A] dark:hover:border-[#62605b] rounded-sm text-sm leading-normal">
                        Log in
                    </a>
                @endauth
            </nav>
        </div>
        <div class="flex items-center justify-center w-full">
            <main class="text-center">
                <h1 class="text-4xl font-bold mb-4">Welcome to {{ tenant()->name }}</h1>
                <p class="text-lg mb-8">Your tenant portal for managing products and services.</p>
                @guest
                    <a href="/login" 
                       class="inline-block px-6 py-2 bg-[#1b1b18] text-white dark:bg-[#eeeeec] dark:text-[#1C1C1A] rounded-sm hover:bg-black dark:hover:bg-white transition-colors">
                        Login to Dashboard
                    </a>
                @endguest
            </main>
        </div>
    </body>
</html> 