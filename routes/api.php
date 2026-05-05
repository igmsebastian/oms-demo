<?php

use App\Http\Controllers\Api\OrderController as ApiOrderController;
use App\Http\Controllers\Api\ProductController as ApiProductController;
use App\Http\Controllers\Api\ReportController as ApiReportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware(['auth:sanctum', 'throttle:api']);

Route::middleware(['auth:sanctum', 'throttle:api'])->name('api.')->group(function () {
    Route::apiResource('products', ApiProductController::class)
        ->middlewareFor(['store', 'update', 'destroy'], 'throttle:write-actions');

    Route::apiResource('orders', ApiOrderController::class)
        ->only(['index', 'store', 'show'])
        ->middlewareFor('store', 'throttle:write-actions');
    Route::post('orders/{order}/remarks', [ApiOrderController::class, 'remark'])->middleware('throttle:order-remarks')->name('orders.remarks.store');
    Route::post('orders/{order}/fulfill', [ApiOrderController::class, 'fulfill'])->middleware('throttle:write-actions')->name('orders.fulfill');
    Route::post('orders/{order}/confirm', [ApiOrderController::class, 'confirm'])->middleware('throttle:write-actions')->name('orders.confirm');
    Route::patch('orders/{order}/status', [ApiOrderController::class, 'updateStatus'])->middleware('throttle:write-actions')->name('orders.status.update');
    Route::post('orders/{order}/cancellation-requests', [ApiOrderController::class, 'requestCancellation'])->middleware('throttle:write-actions')->name('orders.cancellation-requests.store');
    Route::post('orders/{order}/cancel', [ApiOrderController::class, 'cancel'])->middleware('throttle:write-actions')->name('orders.cancel');
    Route::post('order-items/{orderItem}/partial-cancel', [ApiOrderController::class, 'partialCancel'])->middleware('throttle:write-actions')->name('order-items.partial-cancel');
    Route::post('orders/{order}/refunds', [ApiOrderController::class, 'storeRefund'])->middleware('throttle:write-actions')->name('orders.refunds.store');
    Route::patch('refunds/{refund}/processing', [ApiOrderController::class, 'markRefundProcessing'])->middleware('throttle:write-actions')->name('refunds.processing');
    Route::patch('refunds/{refund}/completed', [ApiOrderController::class, 'markRefundCompleted'])->middleware('throttle:write-actions')->name('refunds.completed');

    Route::get('reports', [ApiReportController::class, 'index'])->name('reports.index');
});
