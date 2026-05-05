<?php

use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::resource('products', AdminProductController::class)
            ->except(['create', 'edit', 'show']);

        Route::get('orders', [AdminOrderController::class, 'index'])->name('orders.index');
        Route::get('orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
        Route::post('orders/{order}/confirm', [AdminOrderController::class, 'confirm'])->name('orders.confirm');
        Route::patch('orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->name('orders.status.update');
        Route::post('orders/{order}/cancel', [AdminOrderController::class, 'cancel'])->name('orders.cancel');
        Route::post('order-items/{orderItem}/partial-cancel', [AdminOrderController::class, 'partialCancel'])->name('order-items.partial-cancel');
        Route::post('orders/{order}/refunds', [AdminOrderController::class, 'storeRefund'])->name('orders.refunds.store');
        Route::patch('refunds/{refund}/processing', [AdminOrderController::class, 'markRefundProcessing'])->name('refunds.processing');
        Route::patch('refunds/{refund}/completed', [AdminOrderController::class, 'markRefundCompleted'])->name('refunds.completed');

        Route::get('reports', [AdminReportController::class, 'index'])->name('reports.index');
    });
});

require __DIR__.'/settings.php';
