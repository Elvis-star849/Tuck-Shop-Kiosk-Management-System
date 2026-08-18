<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $year = (int) $request->input('year', now()->year);
        $period = $request->input('period', 'year');
        $cashierId = $request->integer('cashier_id') ?: null;
        $tab = $request->input('tab', 'sales');
        if (! in_array($tab, ['sales', 'profit', 'inventory', 'purchases', 'expenses', 'cashiers'], true)) {
            $tab = 'sales';
        }

        [$start, $end, $label] = match ($period) {
            'daily' => [now()->startOfDay(), now()->endOfDay(), 'Today'],
            'weekly' => [now()->startOfWeek(), now()->endOfWeek(), 'This week'],
            'monthly' => [now()->startOfMonth(), now()->endOfMonth(), 'This month'],
            default => [now()->setYear($year)->startOfYear(), now()->setYear($year)->endOfYear(), (string) $year],
        };

        $salesQuery = Sale::query()
            ->whereBetween('sold_at', [$start, $end])
            ->counted()
            ->when($cashierId, fn ($query) => $query->where('user_id', $cashierId));

        $posSales = (float) (clone $salesQuery)->sum('total');
        $posProfit = (clone $salesQuery)->with('items')->get()->sum(fn (Sale $sale) => $sale->profit());
        $expenses = (float) Expense::query()->whereBetween('expense_date', [$start, $end])->sum('amount');
        $purchases = (float) Purchase::query()->whereBetween('purchase_date', [$start, $end])->sum('total');
        $collected = (float) Payment::query()->whereBetween('payment_date', [$start, $end])->sum('amount');

        $monthly = collect(range(1, 12))->map(function (int $month) use ($year, $cashierId) {
            $monthStart = now()->setYear($year)->month($month)->startOfMonth();
            $monthEnd = (clone $monthStart)->endOfMonth();
            $monthSales = Sale::query()
                ->whereBetween('sold_at', [$monthStart, $monthEnd])
                ->counted()
                ->when($cashierId, fn ($query) => $query->where('user_id', $cashierId));

            return [
                'month' => $monthStart->format('F'),
                'sales' => (float) (clone $monthSales)->sum('total'),
                'profit' => (clone $monthSales)->with('items')->get()->sum(fn (Sale $sale) => $sale->profit()),
                'purchases' => (float) Purchase::query()->whereBetween('purchase_date', [$monthStart, $monthEnd])->sum('total'),
                'expenses' => (float) Expense::query()->whereBetween('expense_date', [$monthStart, $monthEnd])->sum('amount'),
            ];
        });

        $topSellers = SaleItem::query()
            ->selectRaw('product_id, description, SUM(quantity) as qty, SUM(line_total) as revenue')
            ->whereHas('sale', function ($query) use ($start, $end, $cashierId) {
                $query->whereBetween('sold_at', [$start, $end])->counted();
                if ($cashierId) {
                    $query->where('user_id', $cashierId);
                }
            })
            ->groupBy('product_id', 'description')
            ->orderByDesc('qty')
            ->limit(8)
            ->get();

        $cashierReport = Sale::query()
            ->selectRaw('user_id, COUNT(*) as txns, SUM(total) as sales')
            ->whereBetween('sold_at', [$start, $end])
            ->counted()
            ->groupBy('user_id')
            ->with('user')
            ->get();

        $lowStock = Product::query()->active()->whereColumn('quantity', '<=', 'min_stock')->orderBy('quantity')->get();
        $stockValue = (float) Product::query()->active()->selectRaw('SUM(quantity * cost_price) as value')->value('value');

        $outstandingInvoices = Invoice::query()
            ->with('customer')
            ->whereNotIn('status', ['paid', 'cancelled', 'draft'])
            ->orderBy('due_date')
            ->get();

        $recentPurchases = Purchase::query()
            ->with('supplier')
            ->whereBetween('purchase_date', [$start, $end])
            ->latest('purchase_date')
            ->limit(15)
            ->get();

        $recentExpenses = Expense::query()
            ->whereBetween('expense_date', [$start, $end])
            ->latest('expense_date')
            ->limit(15)
            ->get();

        $years = collect([now()->year])
            ->merge(Sale::query()->get(['sold_at'])->map(fn (Sale $sale) => $sale->sold_at?->year))
            ->merge(Invoice::query()->get(['invoice_date'])->map(fn (Invoice $invoice) => $invoice->invoice_date?->year))
            ->filter()
            ->unique()
            ->sortDesc()
            ->values();

        return view('reports.index', [
            'year' => $year,
            'years' => $years,
            'period' => $period,
            'tab' => $tab,
            'label' => $label,
            'cashierId' => $cashierId,
            'cashiers' => User::query()->orderBy('name')->get(['id', 'name', 'role']),
            'posSales' => $posSales,
            'posProfit' => $posProfit,
            'expenses' => $expenses,
            'netProfit' => $posProfit - $expenses,
            'purchases' => $purchases,
            'collected' => $collected,
            'monthly' => $monthly,
            'topSellers' => $topSellers,
            'cashierReport' => $cashierReport,
            'lowStock' => $lowStock,
            'stockValue' => $stockValue,
            'outstandingInvoices' => $outstandingInvoices,
            'recentPurchases' => $recentPurchases,
            'recentExpenses' => $recentExpenses,
        ]);
    }
}
