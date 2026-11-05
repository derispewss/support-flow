<?php

use App\Modules\Identity\Http\Controllers\Api\V1\AuthController;
use App\Modules\Support\Http\Controllers\Api\V1\TicketAttachmentController;
use App\Modules\Support\Http\Controllers\Api\V1\TicketController;
use App\Modules\Support\Http\Controllers\Api\V1\TicketMessageController;
use App\Modules\Support\Http\Controllers\Api\V1\TicketWatcherController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::prefix('auth')->name('auth.')->group(function (): void {
        Route::post('/register', [AuthController::class, 'register'])
            ->middleware('throttle:register')
            ->name('register');

        Route::post('/login', [AuthController::class, 'login'])
            ->middleware('throttle:login')
            ->name('login');

        Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])
            ->middleware('throttle:password')
            ->name('password.email');

        Route::post('/reset-password', [AuthController::class, 'resetPassword'])
            ->middleware('throttle:password')
            ->name('password.store');

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::get('/me', [AuthController::class, 'me'])->name('me');
            Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        });
    });

    Route::middleware(['auth:sanctum', 'tenant', 'organization', 'throttle:api'])->group(function (): void {
        Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
        Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');
        Route::get('/tickets/{ticket}', [TicketController::class, 'show'])
            ->whereNumber('ticket')
            ->name('tickets.show');
        Route::match(['put', 'patch'], '/tickets/{ticket}', [TicketController::class, 'update'])
            ->whereNumber('ticket')
            ->name('tickets.update');
        Route::delete('/tickets/{ticket}', [TicketController::class, 'destroy'])
            ->whereNumber('ticket')
            ->name('tickets.destroy');

        Route::post('/tickets/{ticket}/assign', [TicketController::class, 'assign'])
            ->whereNumber('ticket')
            ->name('tickets.assign');
        Route::patch('/tickets/{ticket}/status', [TicketController::class, 'status'])
            ->whereNumber('ticket')
            ->name('tickets.status');

        Route::get('/tickets/{ticket}/messages', [TicketMessageController::class, 'index'])
            ->whereNumber('ticket')
            ->name('tickets.messages.index');
        Route::post('/tickets/{ticket}/messages', [TicketMessageController::class, 'store'])
            ->whereNumber('ticket')
            ->name('tickets.messages.store');

        Route::get('/tickets/{ticket}/attachments', [TicketAttachmentController::class, 'index'])
            ->whereNumber('ticket')
            ->name('tickets.attachments.index');
        Route::post('/tickets/{ticket}/attachments', [TicketAttachmentController::class, 'store'])
            ->whereNumber('ticket')
            ->middleware('throttle:upload')
            ->name('tickets.attachments.store');
        Route::get('/tickets/{ticket}/attachments/{attachment}/download', [TicketAttachmentController::class, 'download'])
            ->whereNumber('ticket')
            ->whereNumber('attachment')
            ->name('tickets.attachments.download');
        Route::delete('/tickets/{ticket}/attachments/{attachment}', [TicketAttachmentController::class, 'destroy'])
            ->whereNumber('ticket')
            ->whereNumber('attachment')
            ->name('tickets.attachments.destroy');

        Route::post('/tickets/{ticket}/watchers', [TicketWatcherController::class, 'store'])
            ->whereNumber('ticket')
            ->name('tickets.watchers.store');
        Route::delete('/tickets/{ticket}/watchers/{user}', [TicketWatcherController::class, 'destroy'])
            ->whereNumber('ticket')
            ->whereNumber('user')
            ->name('tickets.watchers.destroy');
    });
});
