<?php

use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OrderPageController;
use App\Http\Controllers\ProductManagementController;
use App\Http\Controllers\ReportExportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::redirect('dashboard', '/');

    Route::get('orders', [OrderPageController::class, 'index'])->name('orders.index');
    Route::get('orders/{order:order_number}', [OrderPageController::class, 'show'])->name('orders.show');
    Route::post('orders/{order:order_number}/remarks', [OrderPageController::class, 'remark'])
        ->middleware('throttle:order-remarks')
        ->name('orders.remarks.store');
    Route::post('orders/{order:order_number}/fulfill', [OrderPageController::class, 'fulfill'])->middleware('throttle:write-actions')->name('orders.fulfill');
    Route::post('orders/{order:order_number}/confirm', [OrderPageController::class, 'confirm'])->middleware('throttle:write-actions')->name('orders.confirm');
    Route::patch('orders/{order:order_number}/status', [OrderPageController::class, 'updateStatus'])->middleware('throttle:write-actions')->name('orders.status.update');
    Route::post('orders/{order:order_number}/cancellation-requests', [OrderPageController::class, 'requestCancellation'])->middleware('throttle:write-actions')->name('orders.cancellation-requests.store');
    Route::post('orders/{order:order_number}/cancel', [OrderPageController::class, 'cancel'])->middleware('throttle:write-actions')->name('orders.cancel');
    Route::post('order-items/{orderItem}/partial-cancel', [OrderPageController::class, 'partialCancel'])->middleware('throttle:write-actions')->name('order-items.partial-cancel');
    Route::post('orders/{order:order_number}/refunds', [OrderPageController::class, 'storeRefund'])->middleware('throttle:write-actions')->name('orders.refunds.store');
    Route::patch('refunds/{refund}/processing', [OrderPageController::class, 'markRefundProcessing'])->middleware('throttle:write-actions')->name('refunds.processing');
    Route::patch('refunds/{refund}/completed', [OrderPageController::class, 'markRefundCompleted'])->middleware('throttle:write-actions')->name('refunds.completed');

    Route::resource('products', AdminProductController::class)
        ->middlewareFor(['store', 'update', 'destroy'], 'throttle:write-actions')
        ->except(['create', 'edit', 'show']);

    Route::get('reports', [AdminReportController::class, 'index'])->name('reports.index');
    Route::get('reports/export', ReportExportController::class)->middleware('throttle:report-exports')->name('reports.export');

    Route::get('product-management', [ProductManagementController::class, 'index'])->name('product-management.index');
    Route::post('product-management/{module}', [ProductManagementController::class, 'store'])
        ->middleware('throttle:write-actions')
        ->whereIn('module', ['categories', 'brands', 'units', 'sizes', 'colors', 'tags'])
        ->name('product-management.store');
    Route::patch('product-management/{module}/{record}', [ProductManagementController::class, 'update'])
        ->middleware('throttle:write-actions')
        ->whereIn('module', ['categories', 'brands', 'units', 'sizes', 'colors', 'tags'])
        ->name('product-management.update');
    Route::delete('product-management/{module}/{record}', [ProductManagementController::class, 'destroy'])
        ->middleware('throttle:write-actions')
        ->whereIn('module', ['categories', 'brands', 'units', 'sizes', 'colors', 'tags'])
        ->name('product-management.destroy');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::resource('products', AdminProductController::class)
            ->middlewareFor(['store', 'update', 'destroy'], 'throttle:write-actions')
            ->except(['create', 'edit', 'show']);

        Route::get('orders', [AdminOrderController::class, 'index'])->name('orders.index');
        Route::get('orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
        Route::post('orders/{order}/fulfill', [AdminOrderController::class, 'fulfill'])->middleware('throttle:write-actions')->name('orders.fulfill');
        Route::post('orders/{order}/confirm', [AdminOrderController::class, 'confirm'])->middleware('throttle:write-actions')->name('orders.confirm');
        Route::patch('orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->middleware('throttle:write-actions')->name('orders.status.update');
        Route::post('orders/{order}/cancel', [AdminOrderController::class, 'cancel'])->middleware('throttle:write-actions')->name('orders.cancel');
        Route::post('order-items/{orderItem}/partial-cancel', [AdminOrderController::class, 'partialCancel'])->middleware('throttle:write-actions')->name('order-items.partial-cancel');
        Route::post('orders/{order}/refunds', [AdminOrderController::class, 'storeRefund'])->middleware('throttle:write-actions')->name('orders.refunds.store');
        Route::patch('refunds/{refund}/processing', [AdminOrderController::class, 'markRefundProcessing'])->middleware('throttle:write-actions')->name('refunds.processing');
        Route::patch('refunds/{refund}/completed', [AdminOrderController::class, 'markRefundCompleted'])->middleware('throttle:write-actions')->name('refunds.completed');

        Route::get('reports', [AdminReportController::class, 'index'])->name('reports.index');
    });
});

require __DIR__.'/settings.php';
