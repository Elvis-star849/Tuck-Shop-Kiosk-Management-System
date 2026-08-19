<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EcocashPaymentController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::post('/payments/ecocash/webhook', [EcocashPaymentController::class, 'webhook'])
    ->name('payments.ecocash.webhook');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/pos', [PosController::class, 'index'])->name('pos.index');
    Route::post('/pos', [PosController::class, 'store'])->name('pos.store');
    Route::get('/sales', [SaleController::class, 'index'])->name('sales.index');
    Route::get('/sales/export.pdf', [SaleController::class, 'exportPdf'])->name('sales.export');
    Route::get('/sales/{sale}', [SaleController::class, 'show'])->name('sales.show');
    Route::get('/sales/{sale}/receipt', [SaleController::class, 'receipt'])->name('sales.receipt');
    Route::get('/sales/{sale}/pdf', [SaleController::class, 'downloadPdf'])->name('sales.pdf');
    Route::post('/sales/{sale}/cancel-request', [SaleController::class, 'requestCancel'])->name('sales.cancel-request');
    Route::post('/sales/{sale}/void-pending', [SaleController::class, 'voidPending'])->name('sales.void-pending');

    Route::get('/sales/{sale}/ecocash', [EcocashPaymentController::class, 'createForSale'])->name('sales.ecocash.create');
    Route::post('/sales/{sale}/ecocash', [EcocashPaymentController::class, 'storeForSale'])->name('sales.ecocash.store');
    Route::post('/sales/{sale}/paynow', [EcocashPaymentController::class, 'startPaynowForSale'])->name('sales.paynow.start');

    Route::get('/products', [ProductController::class, 'index'])->name('products.index');

    Route::middleware('admin')->group(function () {
        Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
        Route::post('/products', [ProductController::class, 'store'])->name('products.store');
        Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
        Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
    });

    Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');

    Route::resource('customers', CustomerController::class);
    Route::resource('invoices', InvoiceController::class);
    Route::post('/invoices/{invoice}/send', [InvoiceController::class, 'markSent'])->name('invoices.send');
    Route::post('/invoices/{invoice}/cancel', [InvoiceController::class, 'cancel'])->name('invoices.cancel');
    Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'downloadPdf'])->name('invoices.pdf');
    Route::get('/invoices/{invoice}/print', [InvoiceController::class, 'printPdf'])->name('invoices.print');

    Route::get('/invoices/{invoice}/ecocash', [EcocashPaymentController::class, 'create'])->name('payments.ecocash.create');
    Route::post('/invoices/{invoice}/ecocash', [EcocashPaymentController::class, 'store'])->name('payments.ecocash.store');
    Route::get('/payments/ecocash/{transaction}', [EcocashPaymentController::class, 'show'])->name('payments.ecocash.show');
    Route::get('/payments/ecocash/{transaction}/poll', [EcocashPaymentController::class, 'poll'])->name('payments.ecocash.poll');
    Route::get('/payments/ecocash/{transaction}/return', [EcocashPaymentController::class, 'returnFromPaynow'])->name('payments.ecocash.return');
    Route::post('/payments/ecocash/{transaction}/simulate', [EcocashPaymentController::class, 'simulate'])->name('payments.ecocash.simulate');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::middleware('admin')->group(function () {
        Route::get('/stock', [StockController::class, 'manage'])->name('stock.manage');
        Route::get('/stock/history', [StockController::class, 'index'])->name('stock.history');
        Route::get('/stock/in', [StockController::class, 'createIn'])->name('stock.in');
        Route::post('/stock/in', [StockController::class, 'storeIn'])->name('stock.in.store');
        Route::get('/stock/out', [StockController::class, 'createOut'])->name('stock.out');
        Route::post('/stock/out', [StockController::class, 'storeOut'])->name('stock.out.store');
        Route::get('/stock/adjust', [StockController::class, 'createAdjust'])->name('stock.adjust');
        Route::post('/stock/adjust', [StockController::class, 'storeAdjust'])->name('stock.adjust.store');
        Route::get('/stock/expired', [StockController::class, 'expired'])->name('stock.expired');
        Route::get('/stock/low', [StockController::class, 'low'])->name('stock.low');

        Route::resource('categories', CategoryController::class);
        Route::resource('suppliers', SupplierController::class);
        Route::resource('purchases', PurchaseController::class)->except(['destroy']);
        Route::resource('expenses', ExpenseController::class);
        Route::resource('payments', PaymentController::class);
        Route::resource('users', UserController::class)->except(['show', 'destroy']);

        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
        Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit');
        Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');

        Route::post('/sales/{sale}/cancel-approve', [SaleController::class, 'approveCancel'])->name('sales.cancel-approve');
        Route::post('/sales/{sale}/cancel-reject', [SaleController::class, 'rejectCancel'])->name('sales.cancel-reject');
    });
});

require __DIR__.'/auth.php';
