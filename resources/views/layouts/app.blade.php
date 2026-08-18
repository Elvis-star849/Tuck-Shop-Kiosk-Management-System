<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Dashboard' }} · {{ config('company.name', 'Chindeka Tuck Shop') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="app-body" x-data="{ sidebarOpen: window.innerWidth > 840 }">
    <header class="material-topbar">
        <div class="brand-wrap">
            <button type="button" class="menu-toggle" @click="sidebarOpen = !sidebarOpen" aria-label="Toggle menu">
                <span class="material-symbols-outlined">menu</span>
            </button>
            <a class="brand" href="{{ route('dashboard') }}">Chindeka Shop</a>
        </div>
        <div class="topbar-right">
            <a class="top-link {{ request()->routeIs('pos.*') ? 'active' : '' }}" href="{{ route('pos.index') }}">POS</a>
            <a class="top-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">Dashboard</a>
            @if (auth()->user()->isAdmin())
                <a class="top-link {{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.index') }}">Reports</a>
            @endif
            <a class="icon-btn" href="{{ auth()->user()->isAdmin() ? route('stock.low') : route('products.index', ['stock' => 'low']) }}" title="Low stock">
                <span class="material-symbols-outlined">notifications</span>
                @if (\App\Models\Product::query()->active()->whereColumn('quantity', '<=', 'min_stock')->where('quantity', '>', 0)->exists())
                    <span class="dot"></span>
                @endif
            </a>
            <a class="icon-btn" href="{{ route('profile.edit') }}" title="{{ auth()->user()->name }}">
                <span class="avatar">{{ auth()->user()->initials() }}</span>
            </a>
        </div>
    </header>

    <div class="app-shell">
        <aside class="sidenav" :class="{ open: sidebarOpen }">
            <div
                class="sidenav-scroll"
                @if (auth()->user()->isAdmin())
                    x-data="{
                        groups: {
                            sales: {{ request()->routeIs('pos.*', 'sales.*', 'invoices.*') ? 'true' : 'false' }},
                            inventory: {{ request()->routeIs('products.*', 'categories.*', 'stock.*') ? 'true' : 'false' }},
                            purchasing: {{ request()->routeIs('suppliers.*', 'purchases.*') ? 'true' : 'false' }},
                            finance: {{ request()->routeIs('payments.*', 'expenses.*', 'reports.*') ? 'true' : 'false' }},
                            management: {{ request()->routeIs('customers.*', 'users.*', 'audit-logs.*', 'settings.*') ? 'true' : 'false' }},
                        }
                    }"
                @endif
            >
                @if (auth()->user()->isAdmin())
                    @php
                        $salesOpen = request()->routeIs('pos.*', 'sales.*', 'invoices.*');
                        $inventoryOpen = request()->routeIs('products.*', 'categories.*', 'stock.*');
                        $purchasingOpen = request()->routeIs('suppliers.*', 'purchases.*');
                        $financeOpen = request()->routeIs('payments.*', 'expenses.*', 'reports.*');
                        $managementOpen = request()->routeIs('customers.*', 'users.*', 'audit-logs.*', 'settings.*');
                        $salesHistoryActive = request()->routeIs('sales.*') && request('status') !== 'cancel_requested';
                        $returnsActive = request()->routeIs('sales.index') && request('status') === 'cancel_requested';
                    @endphp

                    <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                        <span class="material-symbols-outlined">dashboard</span> Dashboard
                    </a>
                    <a class="nav-cta" href="{{ route('pos.index') }}">
                        <span class="material-symbols-outlined">add</span> New sale
                    </a>

                    <div class="nav-group">
                        <button type="button" class="nav-group-btn {{ $salesOpen ? 'is-open is-current' : '' }}" :class="{ 'is-open': groups.sales }" @click="groups.sales = !groups.sales">
                            <span class="material-symbols-outlined">point_of_sale</span>
                            Sales
                            <span class="material-symbols-outlined nav-chevron">expand_more</span>
                        </button>
                        <div class="nav-group-items" x-show="groups.sales" @if (! $salesOpen) style="display:none" @endif>
                            <a class="nav-link {{ request()->routeIs('pos.*') ? 'active' : '' }}" href="{{ route('pos.index') }}">New sale</a>
                            <a class="nav-link {{ $salesHistoryActive ? 'active' : '' }}" href="{{ route('sales.index') }}">Sales history</a>
                            <a class="nav-link {{ request()->routeIs('invoices.*') ? 'active' : '' }}" href="{{ route('invoices.index') }}">Invoices</a>
                            <a class="nav-link {{ $returnsActive ? 'active' : '' }}" href="{{ route('sales.index', ['status' => 'cancel_requested']) }}">Returns / refunds</a>
                        </div>
                    </div>

                    <div class="nav-group">
                        <button type="button" class="nav-group-btn {{ $inventoryOpen ? 'is-open is-current' : '' }}" :class="{ 'is-open': groups.inventory }" @click="groups.inventory = !groups.inventory">
                            <span class="material-symbols-outlined">inventory_2</span>
                            Inventory
                            <span class="material-symbols-outlined nav-chevron">expand_more</span>
                        </button>
                        <div class="nav-group-items" x-show="groups.inventory" @if (! $inventoryOpen) style="display:none" @endif>
                            <a class="nav-link {{ request()->routeIs('products.*') ? 'active' : '' }}" href="{{ route('products.index') }}">Products</a>
                            <a class="nav-link {{ request()->routeIs('stock.*') ? 'active' : '' }}" href="{{ route('stock.manage') }}">Stock management</a>
                            <a class="nav-link {{ request()->routeIs('categories.*') ? 'active' : '' }}" href="{{ route('categories.index') }}">Categories</a>
                        </div>
                    </div>

                    <div class="nav-group">
                        <button type="button" class="nav-group-btn {{ $purchasingOpen ? 'is-open is-current' : '' }}" :class="{ 'is-open': groups.purchasing }" @click="groups.purchasing = !groups.purchasing">
                            <span class="material-symbols-outlined">local_shipping</span>
                            Purchasing
                            <span class="material-symbols-outlined nav-chevron">expand_more</span>
                        </button>
                        <div class="nav-group-items" x-show="groups.purchasing" @if (! $purchasingOpen) style="display:none" @endif>
                            <a class="nav-link {{ request()->routeIs('suppliers.*') ? 'active' : '' }}" href="{{ route('suppliers.index') }}">Suppliers</a>
                            <a class="nav-link {{ request()->routeIs('purchases.*') ? 'active' : '' }}" href="{{ route('purchases.index') }}">Purchases</a>
                        </div>
                    </div>

                    <div class="nav-group">
                        <button type="button" class="nav-group-btn {{ $financeOpen ? 'is-open is-current' : '' }}" :class="{ 'is-open': groups.finance }" @click="groups.finance = !groups.finance">
                            <span class="material-symbols-outlined">account_balance</span>
                            Finance
                            <span class="material-symbols-outlined nav-chevron">expand_more</span>
                        </button>
                        <div class="nav-group-items" x-show="groups.finance" @if (! $financeOpen) style="display:none" @endif>
                            <a class="nav-link {{ request()->routeIs('payments.*') ? 'active' : '' }}" href="{{ route('payments.index') }}">Payments</a>
                            <a class="nav-link {{ request()->routeIs('expenses.*') ? 'active' : '' }}" href="{{ route('expenses.index') }}">Expenses</a>
                            <a class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.index') }}">Reports</a>
                        </div>
                    </div>

                    <div class="nav-group">
                        <button type="button" class="nav-group-btn {{ $managementOpen ? 'is-open is-current' : '' }}" :class="{ 'is-open': groups.management }" @click="groups.management = !groups.management">
                            <span class="material-symbols-outlined">admin_panel_settings</span>
                            Management
                            <span class="material-symbols-outlined nav-chevron">expand_more</span>
                        </button>
                        <div class="nav-group-items" x-show="groups.management" @if (! $managementOpen) style="display:none" @endif>
                            <a class="nav-link {{ request()->routeIs('customers.*') ? 'active' : '' }}" href="{{ route('customers.index') }}">Customers</a>
                            <a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}">Users & roles</a>
                            <a class="nav-link {{ request()->routeIs('audit-logs.*') ? 'active' : '' }}" href="{{ route('audit-logs.index') }}">Audit logs</a>
                            <a class="nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}" href="{{ route('settings.edit') }}">Settings</a>
                        </div>
                    </div>
                @else
                    <div class="nav-section">Cashier</div>
                    <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                        <span class="material-symbols-outlined">dashboard</span> Dashboard
                    </a>
                    <a class="nav-link {{ request()->routeIs('pos.*') ? 'active' : '' }}" href="{{ route('pos.index') }}">
                        <span class="material-symbols-outlined">point_of_sale</span> POS / New sale
                    </a>
                    <a class="nav-link {{ request()->routeIs('products.*') ? 'active' : '' }}" href="{{ route('products.index') }}">
                        <span class="material-symbols-outlined">inventory_2</span> Products / Stock
                    </a>
                    <a class="nav-link {{ request()->routeIs('sales.*') ? 'active' : '' }}" href="{{ route('sales.index') }}">
                        <span class="material-symbols-outlined">receipt</span> My sales
                    </a>
                    <a class="nav-link {{ request()->routeIs('invoices.*') ? 'active' : '' }}" href="{{ route('invoices.index') }}">
                        <span class="material-symbols-outlined">receipt_long</span> Invoices / Receipts
                    </a>
                @endif

                <div class="nav-section">Account</div>
                <a class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}" href="{{ route('profile.edit') }}">
                    <span class="material-symbols-outlined">person</span> Profile
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="nav-link" style="width:100%;background:none;border:0;cursor:pointer;text-align:left;">
                        <span class="material-symbols-outlined">logout</span> Log out
                    </button>
                </form>
            </div>
            <div class="sidenav-footer">
                <span class="avatar">{{ auth()->user()->initials() }}</span>
                <div>
                    Logged in as:
                    <strong>{{ auth()->user()->name }}</strong>
                    <div class="muted" style="font-size:11px;">{{ ucfirst(auth()->user()->role) }}</div>
                </div>
            </div>
        </aside>

        <main class="app-main">
            <div class="page-head">
                <div>
                    <h1 class="page-title">{{ $header ?? 'Dashboard' }}</h1>
                    @isset($subtitle)
                        <p class="page-subtitle">{{ $subtitle }}</p>
                    @endisset
                </div>
                <div class="filters">
                    {{ $actions ?? '' }}
                </div>
            </div>

            @if (session('success'))
                <div class="flash flash-success">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="flash flash-error">{{ session('error') }}</div>
            @endif
            @if ($errors->any())
                <div class="flash flash-error">{{ $errors->first() }}</div>
            @endif

            {{ $slot }}
        </main>
    </div>
    @stack('scripts')
</body>
</html>
