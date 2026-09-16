@props([
    'title' => 'Dashboard',
    'breadcrumbs' => [],
])

@php
    $user = auth()->user();
    $navItems = \App\Support\Navigation::items($user);
    $homeRoute = \App\Support\Navigation::homeRoute($user);
    $appName = app_name();
    $isCoach = $user && ! $user->isAdmin();
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} · {{ $appName }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-surface">
    <div id="sidebar-overlay" class="fixed inset-0 z-40 hidden bg-ink/50 lg:hidden"></div>

    <aside id="app-sidebar" class="fixed inset-y-0 left-0 z-50 flex w-64 -translate-x-full flex-col bg-ink text-white transition-transform duration-200 lg:translate-x-0">
        <div class="flex items-center justify-between border-b border-white/10 px-4 py-4">
            <a href="{{ $homeRoute }}" class="flex min-w-0 items-center" data-close-sidebar>
                <x-brand theme="dark" size="sm" />
            </a>
            <button type="button" id="sidebar-close" class="rounded-lg p-2 text-white/70 hover:bg-white/10 lg:hidden" aria-label="Close navigation">
                <x-icon name="close" class="h-5 w-5" />
            </button>
        </div>

        <nav class="flex-1 overflow-y-auto px-3 py-4" aria-label="Main">
            <p class="px-3 pb-3 text-[10px] font-semibold uppercase tracking-[0.2em] text-white/35">
                {{ $user->isAdmin() ? 'Academy' : 'Coach' }}
            </p>
            <ul class="space-y-0.5">
                @foreach ($navItems as $item)
                    @php $active = request()->routeIs(...(array) $item['match']); @endphp
                    <li>
                        <a
                            href="{{ route($item['route']) }}"
                            data-close-sidebar
                            @class(['nav-link', 'nav-link-active' => $active])
                        >
                            <x-icon :name="$item['icon']" class="h-5 w-5 {{ $active ? 'text-white' : 'text-white/45' }}" />
                            {{ $item['label'] }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>

        <div class="border-t border-white/10 p-3">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="nav-link w-full">
                    <x-icon name="logout" class="h-5 w-5 text-white/45" />
                    Logout
                </button>
            </form>
        </div>
    </aside>

    <div class="lg:pl-64">
        <header class="sticky top-0 z-30 border-b border-line bg-white/90 backdrop-blur">
            <div class="flex h-14 items-center justify-between gap-3 px-4 sm:px-6">
                <div class="flex min-w-0 items-center gap-2.5">
                    <button
                        type="button"
                        id="sidebar-open"
                        class="rounded-lg p-2 text-zinc-700 hover:bg-zinc-100 lg:hidden"
                        aria-label="Open navigation"
                        aria-expanded="false"
                    >
                        <x-icon name="menu" class="h-5 w-5" />
                    </button>
                    <div class="min-w-0">
                        @if (count($breadcrumbs) && ! $isCoach)
                            <nav class="mb-0.5 hidden text-[11px] text-muted sm:block" aria-label="Breadcrumb">
                                <ol class="flex flex-wrap items-center gap-1">
                                    @foreach ($breadcrumbs as $crumb)
                                        <li class="flex items-center gap-1">
                                            @if (! empty($crumb['url']) && ! $loop->last)
                                                <a href="{{ $crumb['url'] }}" class="hover:text-brand">{{ $crumb['label'] }}</a>
                                            @else
                                                <span class="text-zinc-700">{{ $crumb['label'] }}</span>
                                            @endif
                                            @if (! $loop->last)
                                                <span class="text-zinc-300">/</span>
                                            @endif
                                        </li>
                                    @endforeach
                                </ol>
                            </nav>
                        @endif
                        <h1 class="ui-page-title">{{ $title }}</h1>
                    </div>
                </div>

                <div class="relative">
                    <button
                        type="button"
                        id="user-menu-button"
                        class="flex items-center gap-2 rounded-[10px] border border-line px-1.5 py-1 hover:bg-zinc-50 sm:px-2"
                        aria-haspopup="true"
                        aria-expanded="false"
                    >
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-ink font-display text-xs text-white">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </span>
                        <span class="hidden text-left sm:block">
                            <span class="block text-[13px] font-semibold leading-tight">{{ $user->name }}</span>
                            <span class="block text-[11px] text-muted">{{ $user->role->label() }}</span>
                        </span>
                        <x-icon name="chevron-down" class="hidden h-4 w-4 text-zinc-400 sm:block" />
                    </button>
                    <div id="user-menu" class="menu-panel mt-2 hidden w-56">
                        <div class="border-b border-line px-3 py-2.5">
                            <p class="text-sm font-semibold">{{ $user->name }}</p>
                            <p class="text-xs text-muted">{{ $user->username }}</p>
                        </div>
                        @if ($user->isAdmin())
                            <a href="{{ route('admin.settings.index') }}" class="menu-item">
                                <x-icon name="settings" class="h-4 w-4" />
                                Settings
                            </a>
                        @else
                            <a href="{{ route('coach.profile.show') }}" class="menu-item">
                                <x-icon name="user" class="h-4 w-4" />
                                My Profile
                            </a>
                        @endif
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="menu-item">
                                <x-icon name="logout" class="h-4 w-4" />
                                Logout
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <main class="px-4 py-5 sm:px-6 sm:py-6">
            <x-flash />
            {{ $slot }}
        </main>
    </div>
    @stack('scripts')
</body>
</html>
