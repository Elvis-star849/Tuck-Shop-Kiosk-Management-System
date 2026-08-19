<x-app-layout>
    <x-slot name="header">Reports</x-slot>
    <x-slot name="subtitle">Sales, profit, inventory, purchases, expenses, and cashiers</x-slot>
    <x-slot name="title">Reports</x-slot>
    <x-slot name="actions">
        <form method="GET" class="filters">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <select class="field" name="period" onchange="this.form.submit()">
                <option value="daily" @selected($period === 'daily')>Daily</option>
                <option value="weekly" @selected($period === 'weekly')>Weekly</option>
                <option value="monthly" @selected($period === 'monthly')>Monthly</option>
                <option value="year" @selected($period === 'year')>Year</option>
            </select>
            <select class="field" name="year" onchange="this.form.submit()">
                @foreach ($years as $option)
                    <option value="{{ $option }}" @selected((int) $year === (int) $option)>{{ $option }}</option>
                @endforeach
            </select>
            <select class="field" name="cashier_id" onchange="this.form.submit()">
                <option value="">All cashiers</option>
                @foreach ($cashiers as $cashier)
                    <option value="{{ $cashier->id }}" @selected((int) $cashierId === (int) $cashier->id)>{{ $cashier->name }}</option>
                @endforeach
            </select>
        </form>
    </x-slot>

    @php
        $tabQuery = request()->except('tab');
    @endphp
    <x-page-tabs :tabs="[
        ['label' => 'Sales', 'url' => route('reports.index', $tabQuery + ['tab' => 'sales']), 'active' => $tab === 'sales'],
        ['label' => 'Profit', 'url' => route('reports.index', $tabQuery + ['tab' => 'profit']), 'active' => $tab === 'profit'],
        ['label' => 'Inventory', 'url' => route('reports.index', $tabQuery + ['tab' => 'inventory']), 'active' => $tab === 'inventory'],
        ['label' => 'Purchases', 'url' => route('reports.index', $tabQuery + ['tab' => 'purchases']), 'active' => $tab === 'purchases'],
        ['label' => 'Expenses', 'url' => route('reports.index', $tabQuery + ['tab' => 'expenses']), 'active' => $tab === 'expenses'],
        ['label' => 'Cashiers', 'url' => route('reports.index', $tabQuery + ['tab' => 'cashiers']), 'active' => $tab === 'cashiers'],
    ]" />

    @if ($tab === 'sales')
        <div class="stat-grid">
            <div class="card stat-card">
                <div>
                    <div class="stat-value">{{ money($posSales) }}</div>
                    <div class="stat-label">POS sales · {{ $label }}</div>
                </div>
                <div class="icon-circle bg-purple"><span class="material-symbols-outlined">point_of_sale</span></div>
            </div>
            <div class="card stat-card teal">
                <div>
                    <div class="stat-value">{{ money($collected) }}</div>
                    <div class="stat-label">Collected</div>
                </div>
                <div class="icon-circle bg-teal"><span class="material-symbols-outlined">payments</span></div>
            </div>
        </div>

        <div class="chart-grid">
            <div class="card">
                <div class="card-pad"><h2 class="card-title">Monthly sales</h2></div>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th>Sales</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($monthly as $row)
                                <tr>
                                    <td>{{ $row['month'] }}</td>
                                    <td>{{ money($row['sales']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card">
                <div class="card-pad"><h2 class="card-title">Top sellers</h2></div>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Qty</th>
                                <th>Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($topSellers as $row)
                                <tr>
                                    <td>{{ $row->description }}</td>
                                    <td>{{ rtrim(rtrim(number_format($row->qty, 2), '0'), '.') }}</td>
                                    <td>{{ money($row->revenue) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="empty">No POS sales this period.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card" style="margin-top:18px;">
            <div class="card-pad"><h2 class="card-title">Open invoices</h2></div>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Invoice</th>
                            <th>Customer</th>
                            <th>Due</th>
                            <th>Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($outstandingInvoices as $invoice)
                            <tr>
                                <td><a href="{{ route('invoices.show', $invoice) }}" style="color:var(--purple);font-weight:600;">{{ $invoice->invoice_number }}</a></td>
                                <td>{{ $invoice->customer?->displayName() }}</td>
                                <td>{{ $invoice->due_date->format('d M Y') }}</td>
                                <td>{{ money($invoice->balance()) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="empty">No outstanding invoices.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if ($tab === 'profit')
        <div class="stat-grid">
            <div class="card stat-card">
                <div>
                    <div class="stat-value">{{ money($posSales) }}</div>
                    <div class="stat-label">Sales · {{ $label }}</div>
                </div>
                <div class="icon-circle bg-purple"><span class="material-symbols-outlined">point_of_sale</span></div>
            </div>
            <div class="card stat-card yellow">
                <div>
                    <div class="stat-value">{{ money($purchases) }}</div>
                    <div class="stat-label">Purchases</div>
                </div>
                <div class="icon-circle bg-yellow"><span class="material-symbols-outlined">local_shipping</span></div>
            </div>
            <div class="card stat-card teal">
                <div>
                    <div class="stat-value">{{ money_profit($posProfit) }}</div>
                    <div class="stat-label">{{ $posProfit < 0 ? 'Loss' : 'Profit' }} · sales − purchases</div>
                </div>
                <div class="icon-circle bg-teal"><span class="material-symbols-outlined">trending_up</span></div>
            </div>
            <div class="card stat-card magenta">
                <div>
                    <div class="stat-value">{{ money_profit($netProfit) }}</div>
                    <div class="stat-label">Net after expenses</div>
                </div>
                <div class="icon-circle bg-magenta"><span class="material-symbols-outlined">account_balance</span></div>
            </div>
        </div>
        <div class="card">
            <div class="card-pad">
                <h2 class="card-title">Daily profit history</h2>
                <div class="card-kicker">Each day: sales total − purchases total{{ $period === 'daily' ? ' · last 30 days' : '' }}</div>
            </div>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Sales</th>
                            <th>Purchases</th>
                            <th>Profit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($dailyProfit as $row)
                            <tr>
                                <td>{{ $row['date']->format('d M Y') }}</td>
                                <td>{{ money($row['sales']) }}</td>
                                <td>{{ money($row['purchases']) }}</td>
                                <td>{{ money_profit($row['profit']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="empty">No sales or purchases in this period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card" style="margin-top:18px;">
            <div class="card-pad"><h2 class="card-title">Monthly profit ({{ $year }})</h2></div>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th>Sales</th>
                            <th>Purchases</th>
                            <th>Profit</th>
                            <th>Expenses</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($monthly as $row)
                            <tr>
                                <td>{{ $row['month'] }}</td>
                                <td>{{ money($row['sales']) }}</td>
                                <td>{{ money($row['purchases']) }}</td>
                                <td>{{ money_profit($row['profit']) }}</td>
                                <td>{{ money($row['expenses']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if ($tab === 'inventory')
        <div class="stat-grid">
            <div class="card stat-card">
                <div>
                    <div class="stat-value">{{ money($stockValue) }}</div>
                    <div class="stat-label">Stock value (cost)</div>
                </div>
                <div class="icon-circle bg-purple"><span class="material-symbols-outlined">inventory_2</span></div>
            </div>
            <div class="card stat-card yellow">
                <div>
                    <div class="stat-value">{{ $lowStock->count() }}</div>
                    <div class="stat-label">Low / out of stock</div>
                </div>
                <div class="icon-circle bg-yellow"><span class="material-symbols-outlined">warning</span></div>
            </div>
        </div>
        <div class="card">
            <div class="card-pad card-head-row">
                <h2 class="card-title">Low / out of stock</h2>
                <a class="view-all" href="{{ route('stock.low') }}">View all →</a>
            </div>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Available</th>
                            <th>Min</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($lowStock as $product)
                            <tr>
                                <td>{{ $product->name }}</td>
                                <td>{{ rtrim(rtrim(number_format($product->quantity, 2), '0'), '.') }}</td>
                                <td>{{ rtrim(rtrim(number_format($product->min_stock, 2), '0'), '.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="empty">Stock levels look healthy.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if ($tab === 'purchases')
        <div class="stat-grid">
            <div class="card stat-card">
                <div>
                    <div class="stat-value">{{ money($purchases) }}</div>
                    <div class="stat-label">Purchases · {{ $label }}</div>
                </div>
                <div class="icon-circle bg-purple"><span class="material-symbols-outlined">local_shipping</span></div>
            </div>
        </div>
        <div class="chart-grid">
            <div class="card">
                <div class="card-pad"><h2 class="card-title">Monthly purchases</h2></div>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($monthly as $row)
                                <tr>
                                    <td>{{ $row['month'] }}</td>
                                    <td>{{ money($row['purchases']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card">
                <div class="card-pad card-head-row">
                    <h2 class="card-title">Recent purchases</h2>
                    <a class="view-all" href="{{ route('purchases.index') }}">View all →</a>
                </div>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Purchase</th>
                                <th>Supplier</th>
                                <th>Date</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentPurchases as $purchase)
                                <tr>
                                    <td><a href="{{ route('purchases.show', $purchase) }}" style="color:var(--purple);font-weight:600;">{{ $purchase->purchase_number }}</a></td>
                                    <td>{{ $purchase->supplier?->name }}</td>
                                    <td>{{ $purchase->purchase_date->format('d M Y') }}</td>
                                    <td>{{ money($purchase->total) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="empty">No purchases this period.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    @if ($tab === 'expenses')
        <div class="stat-grid">
            <div class="card stat-card yellow">
                <div>
                    <div class="stat-value">{{ money($expenses) }}</div>
                    <div class="stat-label">Expenses · {{ $label }}</div>
                </div>
                <div class="icon-circle bg-yellow"><span class="material-symbols-outlined">payments</span></div>
            </div>
        </div>
        <div class="card">
            <div class="card-pad card-head-row">
                <h2 class="card-title">Expenses this period</h2>
                <a class="view-all" href="{{ route('expenses.index') }}">View all →</a>
            </div>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Category</th>
                            <th>Description</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentExpenses as $expense)
                            <tr>
                                <td>{{ $expense->expense_date->format('d M Y') }}</td>
                                <td>{{ $expense->category }}</td>
                                <td>{{ $expense->description }}</td>
                                <td>{{ money($expense->amount) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="empty">No expenses this period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if ($tab === 'cashiers')
        <div class="card">
            <div class="card-pad" style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                <h2 class="card-title" style="margin:0;">Cashier report · {{ $label }}</h2>
                <a class="btn btn-outline" href="{{ route('sales.export', [
                    'from' => $periodStart,
                    'to' => $periodEnd,
                ]) }}">Download all sales</a>
            </div>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Cashier</th>
                            <th>Transactions</th>
                            <th>Sales</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($cashierReport as $row)
                            <tr>
                                <td>{{ $row->user?->name ?: 'Unknown' }}</td>
                                <td>{{ $row->txns }}</td>
                                <td>{{ money($row->sales) }}</td>
                                <td class="table-actions-cell">
                                    @if ($row->user_id)
                                        <div class="table-actions">
                                            <a class="btn btn-ghost" href="{{ route('sales.index', [
                                                'cashier_id' => $row->user_id,
                                                'from' => $periodStart,
                                                'to' => $periodEnd,
                                            ]) }}">View</a>
                                            <a class="btn btn-outline" href="{{ route('sales.export', [
                                                'cashier_id' => $row->user_id,
                                                'from' => $periodStart,
                                                'to' => $periodEnd,
                                            ]) }}">Download</a>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="empty">No cashier sales in this period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</x-app-layout>
