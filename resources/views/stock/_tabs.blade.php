<x-page-tabs :tabs="[
    ['label' => 'Overview', 'url' => route('stock.manage'), 'active' => request()->routeIs('stock.manage')],
    ['label' => 'Stock In', 'url' => route('stock.in'), 'active' => request()->routeIs('stock.in')],
    ['label' => 'Stock Out', 'url' => route('stock.out'), 'active' => request()->routeIs('stock.out')],
    ['label' => 'Adjustments', 'url' => route('stock.adjust'), 'active' => request()->routeIs('stock.adjust')],
    ['label' => 'History', 'url' => route('stock.history'), 'active' => request()->routeIs('stock.history')],
    ['label' => 'Low Stock', 'url' => route('stock.low'), 'active' => request()->routeIs('stock.low')],
    ['label' => 'Expiry', 'url' => route('stock.expired'), 'active' => request()->routeIs('stock.expired')],
]" />
