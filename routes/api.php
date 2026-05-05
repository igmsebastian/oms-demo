<?php

use App\Http\Controllers\Api\OrderController as ApiOrderController;
use App\Http\Controllers\Api\ProductController as ApiProductController;
use App\Http\Controllers\Api\ReportController as ApiReportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('products', ApiProductController::class);

    Route::apiResource('orders', ApiOrderController::class)->only(['index', 'store', 'show']);
    Route::post('orders/{order}/confirm', [ApiOrderController::class, 'confirm'])->name('orders.confirm');
    Route::patch('orders/{order}/status', [ApiOrderController::class, 'updateStatus'])->name('orders.status.update');
    Route::post('orders/{order}/cancellation-requests', [ApiOrderController::class, 'requestCancellation'])->name('orders.cancellation-requests.store');
    Route::post('orders/{order}/cancel', [ApiOrderController::class, 'cancel'])->name('orders.cancel');
    Route::post('order-items/{orderItem}/partial-cancel', [ApiOrderController::class, 'partialCancel'])->name('order-items.partial-cancel');
    Route::post('orders/{order}/refunds', [ApiOrderController::class, 'storeRefund'])->name('orders.refunds.store');
    Route::patch('refunds/{refund}/processing', [ApiOrderController::class, 'markRefundProcessing'])->name('refunds.processing');
    Route::patch('refunds/{refund}/completed', [ApiOrderController::class, 'markRefundCompleted'])->name('refunds.completed');

    Route::get('reports', [ApiReportController::class, 'index'])->name('reports.index');
});
