<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        
        @if(session('theme_updated'))
        <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
        <meta http-equiv="Pragma" content="no-cache">
        <meta http-equiv="Expires" content="0">
        <meta name="theme-version" content="{{ session('cache_buster', time()) }}">
        <!-- Add theme refresh flag to URL -->
        <script>
            if (!window.location.search.includes('theme_refresh')) {
                const separator = window.location.search ? '&' : '?';
                const refreshUrl = window.location.href + separator + 'theme_refresh=1';
                window.history.replaceState(null, '', refreshUrl);
            }
        </script>
        @endif

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Immediate theme styles to prevent flashing -->
        @php
            $tenant = tenant();
            $theme = null;
            if ($tenant) {
                $theme = $tenant->getTheme();
            }
        @endphp
        
        @if($theme == 'dark')
        <style>
            /* Force dark theme immediately before other styles load */
            html, body {
                background-color: #343a40 !important;
                color: #f8f9fa !important;
            }
            .card, .card-header, .card-body, .min-h-screen {
                background-color: #2c3034 !important;
                border-color: #495057 !important;
                color: #f8f9fa !important;
            }
            .bg-white, nav, header, .border-b, .border-gray-100 {
                background-color: #212529 !important;
                border-color: #495057 !important;
            }
            table, th, td {
                background-color: #343a40 !important;
                color: #f8f9fa !important;
            }
        </style>
        @endif

        <!-- Bootstrap CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        
        <!-- Global Theme Dropdown Fix -->
        <style id="dropdown-fix">
            /* Ensure dropdowns are visible in all themes */
            .form-select, select, .dropdown-menu {
                appearance: auto !important;
                background-image: none !important; /* Remove Bootstrap arrow image */
            }
            
            /* Fix for dark theme */
            select option, .dropdown-item {
                padding: 8px 12px !important;
            }
            
            /* Fix dark theme specifically since it's the most problematic */
            body[data-theme="dark"] select option,
            select.theme-dark option,
            select option.dark-theme {
                background-color: #2c3034 !important;
                color: #f8f9fa !important;
            }
            
            /* Emergency fix for dark theme dropdown buttons */
            .form-select {
                padding-right: 2rem !important;
                /* Force high contrast for dark theme dropdown buttons */
                border: 2px solid #495057 !important; 
                color: inherit !important;
            }
            
            /* Dark theme dropdown button visibility fix */
            [data-theme="dark"] .form-select, 
            body[data-theme="dark"] select,
            .theme-dark select,
            [data-bs-theme="dark"] select {
                background-color: #343a40 !important;
                color: white !important;
                border-color: #6c757d !important;
            }
            
            /* Make the dropdown arrow more visible */
            .form-select::after {
                content: "▼";
                position: absolute;
                right: 10px;
                top: 50%;
                transform: translateY(-50%);
                pointer-events: none;
                color: inherit;
            }
            
            /* Prevent white text on white background */
            [data-theme="dark"] select option {
                background-color: #212529 !important;
                color: white !important;
            }
            
            /* General form element contrast enhancement */
            .form-control:focus, .form-select:focus {
                box-shadow: 0 0 0 0.25rem rgba(255, 255, 255, 0.25) !important;
            }
        </style>
        
        <!-- Direct Theme Stylesheet - Force apply theme based on URL parameter or stored theme -->
        @php
            $forceTheme = null;
            // Check URL for theme parameter
            if (request()->has('force_theme')) {
                $forceTheme = request()->input('force_theme');
            } 
            // Or use stored tenant theme
            elseif ($theme) {
                $forceTheme = $theme;
            }
        @endphp
        
        @if($forceTheme == 'red')
        <style id="force-theme">
        /* Force RED theme */
        html, body, .min-h-screen, main { background-color: #fff5f5 !important; color: #e02d44 !important; }
        .bg-gray-100 { background-color: #fff5f5 !important; }
        .card, .card-header, .card-body, .card-footer { background-color: #fff1f2 !important; border-color: #fcc !important; color: #e02d44 !important; }
        .btn-primary { background-color: #dc3545 !important; border-color: #dc3545 !important; }
        header, nav, .bg-white { background-color: #f8d7da !important; }
        /* Apply to all possible wrapper elements */
        .container, .container-fluid, .py-12, .py-4, .dashboard-container, div[class*="container"] { 
            background-color: #fff5f5 !important; 
            color: #e02d44 !important; 
        }
        /* Tables */
        .table, th, td { background-color: #fff1f2 !important; color: #e02d44 !important; border-color: #fcc !important; }
        </style>
        <script>console.log("Forced RED theme applied directly");</script>
        @elseif($forceTheme == 'dark')
        <style id="force-theme">
        /* Force DARK theme */
        html, body, .min-h-screen, main { background-color: #343a40 !important; color: #f8f9fa !important; }
        .bg-gray-100 { background-color: #343a40 !important; }
        .card, .card-header, .card-body, .card-footer { background-color: #2c3034 !important; border-color: #495057 !important; color: #f8f9fa !important; }
        .btn-primary { background-color: #6c757d !important; border-color: #6c757d !important; }
        header, nav, .bg-white { background-color: #212529 !important; }
        .table, th, td { background-color: #2c3034 !important; color: #f8f9fa !important; border-color: #495057 !important; }
        /* Apply to all possible wrapper elements */
        .container, .container-fluid, .py-12, .py-4, .dashboard-container, div[class*="container"] { 
            background-color: #343a40 !important; 
            color: #f8f9fa !important; 
        }
        /* Apply to common element classes */
        h1, h2, h3, h4, h5, h6, p, span, div, a, button, input, select, textarea {
            color: #f8f9fa !important;
        }
        /* Dropdown menu specific styling */
        .dropdown-menu {
            background-color: #2c3034 !important;
            border-color: #495057 !important;
        }
        .dropdown-item {
            color: #f8f9fa !important;
        }
        .dropdown-item:hover, .dropdown-item:focus {
            background-color: #495057 !important;
            color: white !important;
        }
        select option {
            background-color: #2c3034 !important;
            color: #f8f9fa !important;
        }
        
        /* Critical form elements fix */
        .form-select, select { 
            background-color: #343a40 !important; 
            color: white !important; 
            border: 2px solid #6c757d !important;
        }
        select:focus, .form-select:focus {
            border-color: #0d6efd !important;
            outline: 0 !important;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25) !important;
        }
        
        /* Force color scheme */
        @media (prefers-color-scheme: light) {
            :root {
                color-scheme: dark !important;
            }
        }
        </style>
        <script>
        // Force dropdown styles for dark theme
        document.addEventListener('DOMContentLoaded', function() {
            // Mark the document as dark themed
            document.documentElement.setAttribute('data-theme', 'dark');
            document.body.setAttribute('data-theme', 'dark');
            document.documentElement.setAttribute('data-bs-theme', 'dark');
            
            // Force select elements to be visible in dark theme
            const selects = document.querySelectorAll('select, .form-select');
            selects.forEach(select => {
                select.style.backgroundColor = '#343a40';
                select.style.color = 'white';
                select.style.borderColor = '#6c757d';
            });
        });
        </script>
        <script>console.log("Forced DARK theme applied directly");</script>
        @elseif($forceTheme == 'blue')
        <style id="force-theme">
        /* Force BLUE theme */
        html, body, .min-h-screen, main { background-color: #e7f5ff !important; color: #0c63e4 !important; }
        .bg-gray-100 { background-color: #e7f5ff !important; }
        .card, .card-header, .card-body, .card-footer { background-color: #f0f8ff !important; border-color: #aed9ff !important; color: #0c63e4 !important; }
        .btn-primary { background-color: #0c63e4 !important; border-color: #0c63e4 !important; }
        header, nav, .bg-white { background-color: #cfe4ff !important; }
        /* Apply to all possible wrapper elements */
        .container, .container-fluid, .py-12, .py-4, .dashboard-container, div[class*="container"] { 
            background-color: #e7f5ff !important; 
            color: #0c63e4 !important; 
        }
        /* Tables */
        .table, th, td { background-color: #f0f8ff !important; color: #0c63e4 !important; border-color: #aed9ff !important; }
        </style>
        <script>console.log("Forced BLUE theme applied directly");</script>
        @elseif($forceTheme == 'green')
        <style id="force-theme">
        /* Force GREEN theme */
        html, body, .min-h-screen, main { background-color: #ebfbee !important; color: #187741 !important; }
        .bg-gray-100 { background-color: #ebfbee !important; }
        .card, .card-header, .card-body, .card-footer { background-color: #f0fdf4 !important; border-color: #b7e4c7 !important; color: #187741 !important; }
        .btn-primary { background-color: #198754 !important; border-color: #198754 !important; }
        header, nav, .bg-white { background-color: #d1e7dd !important; }
        /* Apply to all possible wrapper elements */
        .container, .container-fluid, .py-12, .py-4, .dashboard-container, div[class*="container"] { 
            background-color: #ebfbee !important; 
            color: #187741 !important; 
        }
        /* Tables */
        .table, th, td { background-color: #f0fdf4 !important; color: #187741 !important; border-color: #b7e4c7 !important; }
        </style>
        <script>console.log("Forced GREEN theme applied directly");</script>
        @elseif($forceTheme == 'purple')
        <style id="force-theme">
        /* Force PURPLE theme */
        html, body, .min-h-screen, main { background-color: #f5f3ff !important; color: #6d28d9 !important; }
        .bg-gray-100 { background-color: #f5f3ff !important; }
        .card, .card-header, .card-body, .card-footer { background-color: #faf5ff !important; border-color: #c7d2fe !important; color: #6d28d9 !important; }
        .btn-primary { background-color: #6f42c1 !important; border-color: #6f42c1 !important; }
        header, nav, .bg-white { background-color: #e9d5ff !important; }
        /* Apply to all possible wrapper elements */
        .container, .container-fluid, .py-12, .py-4, .dashboard-container, div[class*="container"] { 
            background-color: #f5f3ff !important; 
            color: #6d28d9 !important; 
        }
        /* Tables */
        .table, th, td { background-color: #faf5ff !important; color: #6d28d9 !important; border-color: #c7d2fe !important; }
        </style>
        <script>console.log("Forced PURPLE theme applied directly");</script>
        @elseif($forceTheme == 'orange')
        <style id="force-theme">
        /* Force ORANGE theme */
        html, body, .min-h-screen, main { background-color: #fff7ed !important; color: #c2410c !important; }
        .bg-gray-100 { background-color: #fff7ed !important; }
        .card, .card-header, .card-body, .card-footer { background-color: #fff7ed !important; border-color: #fed7aa !important; color: #c2410c !important; }
        .btn-primary { background-color: #fd7e14 !important; border-color: #fd7e14 !important; }
        header, nav, .bg-white { background-color: #ffedd5 !important; }
        /* Apply to all possible wrapper elements */
        .container, .container-fluid, .py-12, .py-4, .dashboard-container, div[class*="container"] { 
            background-color: #fff7ed !important; 
            color: #c2410c !important; 
        }
        /* Tables */
        .table, th, td { background-color: #fff7ed !important; color: #c2410c !important; border-color: #fed7aa !important; }
        </style>
        <script>console.log("Forced ORANGE theme applied directly");</script>
        @elseif($forceTheme == 'light')
        <style id="force-theme">
        /* Force LIGHT theme */
        html, body, .min-h-screen, main { background-color: #ffffff !important; color: #212529 !important; }
        .bg-gray-100 { background-color: #f8f9fa !important; }
        .card, .card-header, .card-body, .card-footer { background-color: #ffffff !important; border-color: #e9ecef !important; color: #212529 !important; }
        .btn-primary { background-color: #0d6efd !important; border-color: #0d6efd !important; }
        header, nav, .bg-white { background-color: #f8f9fa !important; }
        /* Apply to all possible wrapper elements */
        .container, .container-fluid, .py-12, .py-4, .dashboard-container, div[class*="container"] { 
            background-color: #f8f9fa !important; 
            color: #212529 !important; 
        }
        /* Tables */
        .table, th, td { background-color: #ffffff !important; color: #212529 !important; border-color: #e9ecef !important; }
        </style>
        <script>console.log("Forced LIGHT theme applied directly");</script>
        @elseif($forceTheme == 'default')
        <style id="force-theme">
        /* Force DEFAULT theme (Light with Bootstrap defaults) */
        html, body, .min-h-screen, main { background-color: #f8f9fa !important; color: #212529 !important; }
        .bg-gray-100 { background-color: #f8f9fa !important; }
        .card, .card-header, .card-body, .card-footer { background-color: #ffffff !important; border-color: #dee2e6 !important; color: #212529 !important; }
        .btn-primary { background-color: #0d6efd !important; border-color: #0d6efd !important; }
        header, nav, .bg-white { background-color: #ffffff !important; }
        /* Apply to all possible wrapper elements */
        .container, .container-fluid, .py-12, .py-4, .dashboard-container, div[class*="container"] { 
            background-color: #f8f9fa !important; 
            color: #212529 !important; 
        }
        /* Tables */
        .table, th, td { background-color: #ffffff !important; color: #212529 !important; border-color: #dee2e6 !important; }
        </style>
        <script>console.log("Forced DEFAULT theme applied directly");</script>
        @endif
        
        <!-- Dark Theme Override (Highest Priority) -->
        @if($theme == 'dark')
        <style id="dark-theme-override">
            :root {
                --bg-color: #343a40;
                --text-color: #f8f9fa;
                --primary-color: #6c757d;
                --secondary-color: #adb5bd;
                --accent-color: #0dcaf0;
                --border-color: #495057;
                --header-bg: #212529;
                --card-bg: #2c3034;
            }
            
            /* Global dark theme */
            html, body, .min-h-screen, main, .container, .container-fluid, .py-12, .py-4, 
            .max-w-7xl, .px-4, .sm\:px-6, .lg\:px-8, .mx-auto, .sm\:rounded-lg, .shadow-sm {
                background-color: var(--bg-color) !important;
                color: var(--text-color) !important;
            }
            
            /* Cards, modals, and containers */
            .card, .card-header, .card-body, .card-footer, .modal-content, .modal-header, 
            .modal-body, .modal-footer, .p-6, .bg-white, .overflow-hidden {
                background-color: var(--card-bg) !important;
                border-color: var(--border-color) !important;
                color: var(--text-color) !important;
            }
            
            /* Form elements */
            .btn-primary, .btn-success, .btn-info {
                background-color: var(--primary-color) !important;
                border-color: var(--primary-color) !important;
                color: white !important;
            }
            
            .form-control, .form-select, .input-group-text, input, select, textarea {
                background-color: var(--card-bg) !important;
                color: var(--text-color) !important;
                border-color: var(--border-color) !important;
            }
            
            /* Text elements */
            .text-gray-900, h1, h2, h3, h4, h5, h6, .card-title, label, 
            .form-label, .text-gray-800, .text-base {
                color: var(--text-color) !important;
            }
            
            /* Table elements */
            .table, .table td, .table th, .table-hover tr {
                color: var(--text-color) !important;
                border-color: var(--border-color) !important;
                background-color: var(--card-bg) !important;
            }
            
            .table th, .table-bordered {
                background-color: var(--header-bg) !important;
                border-color: var(--border-color) !important;
            }
            
            /* Navigation elements */
            nav, .bg-white, header, .border-b, .border-gray-100, .shadow {
                background-color: var(--header-bg) !important;
                border-color: var(--border-color) !important;
                color: var(--text-color) !important;
            }
            
            /* Links and buttons */
            a:not(.btn), .text-gray-700, .text-gray-500, .font-medium {
                color: var(--accent-color) !important;
            }
            
            /* Alerts */
            .alert-success {
                background-color: #0f5132 !important;
                color: #d1e7dd !important;
                border-color: #115c37 !important;
            }
            
            /* Make dark theme override everything */
            * {
                border-color: var(--border-color);
            }
            
            .badge {
                background-color: var(--primary-color) !important;
                color: var(--text-color) !important;
            }
            
            .progress {
                background-color: var(--header-bg) !important;
            }
        </style>
        @endif
        
        <!-- Theme CSS -->
        <style>
            :root {
                /* Default Theme Variables */
                --bg-color: #f8f9fa;
                --text-color: #212529;
                --primary-color: #0d6efd;
                --secondary-color: #6c757d;
                --accent-color: #0dcaf0;
                --border-color: #dee2e6;
                --header-bg: #ffffff;
                --card-bg: #ffffff;
            }
            
            @if($theme == 'light')
                :root {
                    --bg-color: #ffffff;
                    --text-color: #212529;
                    --primary-color: #0d6efd;
                    --secondary-color: #6c757d;
                    --accent-color: #0dcaf0;
                    --border-color: #e9ecef;
                    --header-bg: #f8f9fa;
                    --card-bg: #ffffff;
                }
            @elseif($theme == 'blue')
                :root {
                    --bg-color: #e7f5ff;
                    --text-color: #0c63e4;
                    --primary-color: #0c63e4;
                    --secondary-color: #6c757d;
                    --accent-color: #90cdf4;
                    --border-color: #aed9ff;
                    --header-bg: #cfe4ff;
                    --card-bg: #f0f8ff;
                }
                
                body, .min-h-screen, main, .container, .py-12 {
                    background-color: var(--bg-color) !important;
                    color: var(--text-color) !important;
                }
                
                .card, .card-header, .card-body, .card-footer {
                    background-color: var(--card-bg) !important;
                    border-color: var(--border-color) !important;
                }
                
                .btn-primary {
                    background-color: var(--primary-color) !important;
                    border-color: var(--primary-color) !important;
                }
            @elseif($theme == 'green')
                :root {
                    --bg-color: #ebfbee;
                    --text-color: #187741;
                    --primary-color: #198754;
                    --secondary-color: #6c757d;
                    --accent-color: #a3e635;
                    --border-color: #b7e4c7;
                    --header-bg: #d1e7dd;
                    --card-bg: #f0fdf4;
                }
                
                body, .min-h-screen, main, .container, .py-12 {
                    background-color: var(--bg-color) !important;
                    color: var(--text-color) !important;
                }
                
                .card, .card-header, .card-body, .card-footer {
                    background-color: var(--card-bg) !important;
                    border-color: var(--border-color) !important;
                }
                
                .btn-primary {
                    background-color: var(--primary-color) !important;
                    border-color: var(--primary-color) !important;
                }
            @elseif($theme == 'purple')
                :root {
                    --bg-color: #f5f3ff;
                    --text-color: #6d28d9;
                    --primary-color: #6f42c1;
                    --secondary-color: #6c757d;
                    --accent-color: #c084fc;
                    --border-color: #c7d2fe;
                    --header-bg: #e9d5ff;
                    --card-bg: #faf5ff;
                }
                
                body, .min-h-screen, main, .container, .py-12 {
                    background-color: var(--bg-color) !important;
                    color: var(--text-color) !important;
                }
                
                .card, .card-header, .card-body, .card-footer {
                    background-color: var(--card-bg) !important;
                    border-color: var(--border-color) !important;
                }
                
                .btn-primary {
                    background-color: var(--primary-color) !important;
                    border-color: var(--primary-color) !important;
                }
            @elseif($theme == 'red')
                :root {
                    --bg-color: #fff5f5;
                    --text-color: #e02d44;
                    --primary-color: #dc3545;
                    --secondary-color: #6c757d;
                    --accent-color: #f87171;
                    --border-color: #fcc;
                    --header-bg: #f8d7da;
                    --card-bg: #fff1f2;
                }
                
                body, .min-h-screen, main, .container, .py-12 {
                    background-color: var(--bg-color) !important;
                    color: var(--text-color) !important;
                }
                
                .card, .card-header, .card-body, .card-footer {
                    background-color: var(--card-bg) !important;
                    border-color: var(--border-color) !important;
                }
                
                .btn-primary {
                    background-color: var(--primary-color) !important;
                    border-color: var(--primary-color) !important;
                }
            @elseif($theme == 'orange')
                :root {
                    --bg-color: #fff7ed;
                    --text-color: #c2410c;
                    --primary-color: #fd7e14;
                    --secondary-color: #6c757d;
                    --accent-color: #fdba74;
                    --border-color: #fed7aa;
                    --header-bg: #ffedd5;
                    --card-bg: #fff7ed;
                }
                
                body, .min-h-screen, main, .container, .py-12 {
                    background-color: var(--bg-color) !important;
                    color: var(--text-color) !important;
                }
                
                .card, .card-header, .card-body, .card-footer {
                    background-color: var(--card-bg) !important;
                    border-color: var(--border-color) !important;
                }
                
                .btn-primary {
                    background-color: var(--primary-color) !important;
                    border-color: var(--primary-color) !important;
                }
            @elseif($theme == 'custom')
                :root {
                    --bg-color: #fdf2f8;
                    --text-color: #be185d;
                    --primary-color: #d63384;
                    --secondary-color: #6c757d;
                    --accent-color: #f472b6;
                    --border-color: #fbcfe8;
                    --header-bg: #fce7f3;
                    --card-bg: #fdf2f8;
                }
                
                body, .min-h-screen, main, .container, .py-12 {
                    background-color: var(--bg-color) !important;
                    color: var(--text-color) !important;
                }
                
                .card, .card-header, .card-body, .card-footer {
                    background-color: var(--card-bg) !important;
                    border-color: var(--border-color) !important;
                }
                
                .btn-primary {
                    background-color: var(--primary-color) !important;
                    border-color: var(--primary-color) !important;
                }
            @endif
        </style>
        
        <!-- Styles -->
        @stack('styles')
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @if (isset($header))
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endif

            <!-- Page Content -->
            <main>
                @yield('content')
            </main>
        </div>

        <!-- Bootstrap Bundle with Popper -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        
        <!-- Theme support script -->
        <script src="{{ asset('theme-support.js') }}?v={{ time() }}"></script>
        
        @if(session('theme_updated'))
        <script>
            // Ensure that theme changes apply by forcing a full page reload with cache clearing
            (function() {
                // Store that we've seen this cache buster to prevent infinite reload
                const cacheBuster = "{{ session('cache_buster', '') }}";
                
                if (!sessionStorage.getItem('theme_reloaded_' + cacheBuster)) {
                    console.log('Applying theme changes...');
                    
                    // Set the flag before reload to prevent loops
                    sessionStorage.setItem('theme_reloaded_' + cacheBuster, 'true');
                    
                    // Clear browser cache for this page
                    if ('caches' in window) {
                        caches.keys().then(function(names) {
                            names.forEach(function(name) {
                                caches.delete(name);
                            });
                        });
                    }
                    
                    // Force a clean reload with cache bypass
                    setTimeout(function() {
                        window.location.reload(true);
                    }, 50);
                }
            })();
        </script>
        @endif
        
        @stack('scripts')
    </body>
</html>
