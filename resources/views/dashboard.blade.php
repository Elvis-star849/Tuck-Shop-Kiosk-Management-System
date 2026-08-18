<x-app-layout>
    <x-slot name="header">Dashboard</x-slot>
    <x-slot name="subtitle">{{ $isAdmin ? 'Today’s sales, stock, and profit' : 'Your sales today' }}</x-slot>
    <x-slot name="actions">
        <a class="btn btn-primary" href="{{ route('pos.index') }}">
            <span class="material-symbols-outlined" style="font-size:18px;">point_of_sale</span>
            Open POS
        </a>
        @if ($isAdmin)
            <a class="btn btn-ghost" href="{{ route('reports.index') }}">Reports</a>
        @endif
    </x-slot>

    @if ($isAdmin && $pendingCancels)
        <div class="flash flash-error">
            {{ $pendingCancels }} sale cancellation {{ $pendingCancels === 1 ? 'request needs' : 'requests need' }} approval.
            <a href="{{ route('sales.index', ['status' => 'cancel_requested']) }}" style="color:inherit;font-weight:700;">Review</a>
        </div>
    @endif

    <div class="stat-grid">
        <div class="card stat-card">
            <div>
                <div class="stat-value">{{ money($todayRevenue) }}</div>
                <div class="stat-label">Today’s sales</div>
                <div class="stat-change">{{ $todayCount }} transactions</div>
            </div>
            <div class="icon-circle bg-purple">
                <span class="material-symbols-outlined">payments</span>
            </div>
        </div>
        @if ($isAdmin)
        <div class="card stat-card teal">
            <div>
                <div class="stat-value">{{ money($todayProfit) }}</div>
                <div class="stat-label">Today’s profit</div>
                <div class="stat-change">Gross margin on POS sales</div>
            </div>
            <div class="icon-circle bg-teal">
                <span class="material-symbols-outlined">trending_up</span>
            </div>
        </div>
        <div class="card stat-card yellow">
            <div>
                <div class="stat-value">{{ money($todayExpenses) }}</div>
                <div class="stat-label">Today’s expenses</div>
                <div class="stat-change">Net {{ money($netProfit) }}</div>
            </div>
            <div class="icon-circle bg-yellow">
                <span class="material-symbols-outlined">account_balance_wallet</span>
            </div>
        </div>
        @endif
        <div class="card stat-card magenta">
            <div>
                <div class="stat-value">{{ $todayCount }}</div>
                <div class="stat-label">Transactions</div>
                <div class="stat-change">{{ $isAdmin ? 'Stock value '.money($stockValue) : $productCount.' products on hand' }}</div>
            </div>
            <div class="icon-circle bg-magenta">
                <span class="material-symbols-outlined">receipt</span>
            </div>
        </div>
    </div>

    <div class="chart-grid">
        <div class="card card-pad">
            <div class="card-head">
                <div>
                    <h2 class="card-title">Payments today</h2>
                    <div class="card-kicker">Cash, EcoCash, and card</div>
                </div>
            </div>
            @php
                $cash = (float) ($paymentSplit['cash'] ?? 0);
                $ecocash = (float) ($paymentSplit['ecocash'] ?? $paymentSplit['mobile_money'] ?? 0);
                $card = (float) ($paymentSplit['card'] ?? 0);
            @endphp
            <div class="metric-block">
                <div class="metric-label">Cash</div>
                <div class="metric-value">{{ money($cash) }}</div>
            </div>
            <div class="metric-block">
                <div class="metric-label">EcoCash</div>
                <div class="metric-value">{{ money($ecocash) }}</div>
            </div>
            <div class="metric-block">
                <div class="metric-label">Card</div>
                <div class="metric-value">{{ money($card) }}</div>
            </div>
        </div>

        <div class="card">
            <div class="card-pad">
                <h2 class="card-title">Top sellers today</h2>
                <div class="card-kicker">Units moved at the till</div>
            </div>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Qty</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($topSellers as $row)
                            <tr>
                                <td>{{ $row->description }}</td>
                                <td>{{ rtrim(rtrim(number_format($row->qty, 2), '0'), '.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="empty">No sales yet today.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if ($isAdmin && ($lowStock->count() || $expiringSoon->count() || $expiredCount))
        <div class="alert-row">
            @if ($lowStock->count() || $outOfStock->count())
                <a class="alert-chip" href="{{ route('stock.low') }}">
                    ⚠ {{ $lowStock->count() + $outOfStock->count() }} low stock {{ ($lowStock->count() + $outOfStock->count()) === 1 ? 'product' : 'products' }}
                </a>
            @endif
            @if ($expiringSoon->count() || $expiredCount)
                <a class="alert-chip danger" href="{{ route('stock.expired') }}">
                    ⚠ {{ $expiredCount }} expired · {{ $expiringSoon->count() }} expiring soon
                </a>
            @endif
        </div>
    @endif

    <div class="info-grid">
        <div class="card">
            <div class="card-pad card-head-row">
                <div>
                    <h2 class="card-title">Low stock</h2>
                    <div class="card-kicker">Needs restock soon</div>
                </div>
                @if ($isAdmin)
                    <a class="view-all" href="{{ route('stock.low') }}">View all →</a>
                @else
                    <a class="view-all" href="{{ route('products.index', ['stock' => 'low']) }}">View all →</a>
                @endif
            </div>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Qty</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($lowStock->take(6) as $product)
                            <tr>
                                <td><a href="{{ route('products.show', $product) }}" style="color:var(--purple);font-weight:600;">{{ $product->name }}</a></td>
                                <td><span class="stock-pill stock-pill-low">{{ rtrim(rtrim(number_format($product->quantity, 2), '0'), '.') }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="empty">No low-stock items.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card">
            @if ($isAdmin)
                <div class="card-pad card-head-row">
                    <div>
                        <h2 class="card-title">Expiring soon</h2>
                        <div class="card-kicker">Next 14 days</div>
                    </div>
                    <a class="view-all" href="{{ route('stock.expired') }}">View all →</a>
                </div>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Expiry</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($expiringSoon->take(6) as $product)
                                <tr>
                                    <td><a href="{{ route('products.show', $product) }}" style="color:var(--purple);font-weight:600;">{{ $product->name }}</a></td>
                                    <td>{{ $product->expiry_date->format('d M') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="empty">Nothing expiring soon.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @else
                <div class="card-pad" style="display:flex;gap:16px;align-items:center;">
                    <div class="icon-circle bg-yellow">
                        <span class="material-symbols-outlined">warning</span>
                    </div>
                    <div>
                        <h3 class="card-title">Low stock alerts</h3>
                        <p class="muted">{{ $lowStock->count() }} products are at or below the minimum. {{ $outOfStock->count() }} are out of stock.</p>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="chart-grid" style="margin-top:18px;">
        <div class="card">
            <div class="card-pad card-head-row">
                <div>
                    <h2 class="card-title">Recent sales</h2>
                    <div class="card-kicker">Latest POS receipts</div>
                </div>
                <a class="view-all" href="{{ route('sales.index') }}">View all →</a>
            </div>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Sale</th>
                            <th>Cashier</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentSales as $sale)
                            <tr>
                                <td><a href="{{ route('sales.show', $sale) }}" style="color:var(--purple);font-weight:600;">{{ $sale->sale_number }}</a></td>
                                <td>{{ $sale->user?->name }}</td>
                                <td>{{ money($sale->total) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="empty">No POS sales yet. Open the till to record the first one.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card storage-card">
            <div class="storage-copy" style="flex:1;">
                <h3>Stock on hand</h3>
                <p>
                    @if ($isAdmin)
                        Inventory cost value {{ money($stockValue) }} across {{ $productCount }} active products.
                    @else
                        {{ $productCount }} active products in the catalog.
                    @endif
                </p>
                @if ($overdueCount)
                    <p class="muted">{{ $overdueCount }} overdue invoices still need follow-up.</p>
                @endif
            </div>
            <div class="cloud-art" aria-hidden="true"></div>
        </div>
    </div>
</x-app-layout>
