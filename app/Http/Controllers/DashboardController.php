<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();
        $admin = $request->user()->isAdmin();

        $todaySales = Sale::query()
            ->whereBetween('sold_at', [$todayStart, $todayEnd])
            ->counted()
            ->when(! $admin, fn ($query) => $query->where('user_id', $request->user()->id));

        $todayRevenue = (float) (clone $todaySales)->sum('total');
        $todayCount = (clone $todaySales)->count();
        $todayProfit = $admin
            ? Sale::query()
                ->whereBetween('sold_at', [$todayStart, $todayEnd])
                ->counted()
                ->with('items')
                ->get()
                ->sum(fn (Sale $sale) => $sale->profit())
            : 0;
        $todayExpenses = $admin ? (float) Expense::query()->whereDate('expense_date', today())->sum('amount') : 0;

        $lowStock = Product::query()->active()->whereColumn('quantity', '<=', 'min_stock')->where('quantity', '>', 0)->orderBy('quantity')->get();
        $outOfStock = Product::query()->active()->where('quantity', '<=', 0)->get();
        $expiringSoon = Product::query()->active()->expiringSoon()->orderBy('expiry_date')->get();
        $expiredCount = Product::query()->active()->whereNotNull('expiry_date')->whereDate('expiry_date', '<=', now())->count();

        $stockValue = $admin
            ? (float) Product::query()->active()->selectRaw('SUM(quantity * cost_price) as value')->value('value')
            : 0;

        $paymentSplit = Payment::query()
            ->whereDate('payment_date', today())
            ->when(! $admin, fn ($query) => $query->whereHas('sale', fn ($sale) => $sale->where('user_id', $request->user()->id)))
            ->selectRaw('payment_method, SUM(amount) as total')
            ->groupBy('payment_method')
            ->pluck('total', 'payment_method');

        $topSellers = SaleItem::query()
            ->selectRaw('product_id, description, SUM(quantity) as qty')
            ->whereHas('sale', function ($query) use ($todayStart, $todayEnd, $admin, $request) {
                $query->whereBetween('sold_at', [$todayStart, $todayEnd])->counted();
                if (! $admin) {
                    $query->where('user_id', $request->user()->id);
                }
            })
            ->groupBy('product_id', 'description')
            ->orderByDesc('qty')
            ->limit(5)
            ->get();

        $recentSales = Sale::query()
            ->with('user')
            ->when(! $admin, fn ($query) => $query->where('user_id', $request->user()->id))
            ->latest('sold_at')
            ->limit(6)
            ->get();

        $pendingCancels = $admin ? Sale::query()->where('status', 'cancel_requested')->count() : 0;

        return view('dashboard', [
            'isAdmin' => $admin,
            'todayRevenue' => $todayRevenue,
            'todayProfit' => $todayProfit,
            'todayCount' => $todayCount,
            'todayExpenses' => $todayExpenses,
            'netProfit' => $todayProfit - $todayExpenses,
            'productCount' => Product::query()->active()->count(),
            'lowStock' => $lowStock,
            'outOfStock' => $outOfStock,
            'expiringSoon' => $expiringSoon,
            'expiredCount' => $expiredCount,
            'stockValue' => $stockValue,
            'paymentSplit' => $paymentSplit,
            'topSellers' => $topSellers,
            'recentSales' => $recentSales,
            'overdueCount' => Invoice::query()->where('status', 'overdue')->count(),
            'pendingCancels' => $pendingCancels,
        ]);
    }
}
