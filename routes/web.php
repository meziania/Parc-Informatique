<?php

use App\Http\Controllers\Admin\AuditLogController as AdminAuditLogController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Ai\AiHealthController;
use App\Http\Controllers\Ai\TicketFaqDraftController;
use App\Http\Controllers\Ai\TicketResolutionAssistController;
use App\Http\Controllers\Ai\TicketSuggestionController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\AssetImportController;
use App\Http\Controllers\AssetInstalledSoftwareController;
use App\Http\Controllers\ConsumableController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SoftwareLicenseController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TicketCommentController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\TicketTaskController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return Inertia::render('Welcome');
});

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth'])
    ->name('dashboard');

Route::get('/reports/weekly.pdf', [ReportController::class, 'weekly'])
    ->middleware(['auth', 'role:technician'])
    ->name('reports.weekly');

Route::get('/analytics', AnalyticsController::class)
    ->middleware(['auth', 'role:technician'])
    ->name('analytics.index');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])
        ->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])
        ->name('notifications.read-all');

    Route::get('/search', SearchController::class)
        ->middleware('throttle:60,1')
        ->name('search');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [AdminUserController::class, 'create'])->name('users.create');
    Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
    Route::get('/users/{user}/edit', [AdminUserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
    Route::patch('/users/{user}/toggle-active', [AdminUserController::class, 'toggleActive'])->name('users.toggle-active');
    Route::post('/users/{user}/reset-password', [AdminUserController::class, 'resetPassword'])->name('users.reset-password');
    Route::get('/audit-logs', [AdminAuditLogController::class, 'index'])->name('audit-logs.index');
});

Route::middleware('auth')->group(function () {
    // Gestion du parc : techniciens et admins
    Route::middleware('role:technician')->group(function () {
        Route::get('assets/create', [AssetController::class, 'create'])->name('assets.create');
        Route::post('assets', [AssetController::class, 'store'])->name('assets.store');
        Route::get('assets/{asset}/edit', [AssetController::class, 'edit'])->name('assets.edit');
        Route::put('assets/{asset}', [AssetController::class, 'update'])->name('assets.update');
        Route::delete('assets/{asset}', [AssetController::class, 'destroy'])->name('assets.destroy');

        Route::get('assets/import', [AssetImportController::class, 'create'])->name('assets.import');
        Route::get('assets/import/template', [AssetImportController::class, 'template'])->name('assets.import.template');
        Route::post('assets/import', [AssetImportController::class, 'store'])->name('assets.import.store');

        Route::post('assets/{asset}/softwares', [AssetInstalledSoftwareController::class, 'store'])->name('assets.softwares.store');
        Route::delete('assets/{asset}/softwares/{software}', [AssetInstalledSoftwareController::class, 'destroy'])->name('assets.softwares.destroy');

        Route::resource('locations', LocationController::class)->except(['show', 'create']);

        Route::resource('suppliers', SupplierController::class)->except(['show']);
        Route::get('suppliers/{supplier}', [SupplierController::class, 'show'])->name('suppliers.show');

        Route::get('contracts/create', [ContractController::class, 'create'])->name('contracts.create');
        Route::post('contracts', [ContractController::class, 'store'])->name('contracts.store');
        Route::get('contracts/{contract}/edit', [ContractController::class, 'edit'])->name('contracts.edit');
        Route::put('contracts/{contract}', [ContractController::class, 'update'])->name('contracts.update');
        Route::delete('contracts/{contract}', [ContractController::class, 'destroy'])->name('contracts.destroy');
        Route::post('contracts/{contract}/assets', [ContractController::class, 'attachAsset'])->name('contracts.assets.attach');
        Route::delete('contracts/{contract}/assets/{asset}', [ContractController::class, 'detachAsset'])->name('contracts.assets.detach');

        Route::resource('consumables', ConsumableController::class)->except(['show']);
        Route::post('consumables/{consumable}/adjust', [ConsumableController::class, 'adjust'])->name('consumables.adjust');

        Route::get('licenses/create', [SoftwareLicenseController::class, 'create'])->name('licenses.create');
        Route::post('licenses', [SoftwareLicenseController::class, 'store'])->name('licenses.store');
        Route::get('licenses/{license}/edit', [SoftwareLicenseController::class, 'edit'])->name('licenses.edit');
        Route::put('licenses/{license}', [SoftwareLicenseController::class, 'update'])->name('licenses.update');
        Route::delete('licenses/{license}', [SoftwareLicenseController::class, 'destroy'])->name('licenses.destroy');
        Route::post('licenses/{license}/assets', [SoftwareLicenseController::class, 'attachAsset'])->name('licenses.assets.attach');
        Route::delete('licenses/{license}/assets/{asset}', [SoftwareLicenseController::class, 'detachAsset'])->name('licenses.assets.detach');

        Route::post('reservations/{reservation}/approve', [ReservationController::class, 'approve'])->name('reservations.approve');
        Route::post('reservations/{reservation}/reject', [ReservationController::class, 'reject'])->name('reservations.reject');

        Route::post('tickets/{ticket}/approve', [TicketController::class, 'approve'])->name('tickets.approve');
        Route::post('tickets/{ticket}/reject-approval', [TicketController::class, 'reject'])->name('tickets.reject-approval');

        Route::post('tickets/{ticket}/tasks', [TicketTaskController::class, 'store'])->name('tickets.tasks.store');
        Route::put('tickets/{ticket}/tasks/{task}', [TicketTaskController::class, 'update'])->name('tickets.tasks.update');
        Route::post('tickets/{ticket}/tasks/{task}/toggle', [TicketTaskController::class, 'toggle'])->name('tickets.tasks.toggle');
        Route::delete('tickets/{ticket}/tasks/{task}', [TicketTaskController::class, 'destroy'])->name('tickets.tasks.destroy');
    });

    // Consultation : tout utilisateur connecté (son propre parc pour les utilisateurs)
    Route::get('assets', [AssetController::class, 'index'])->name('assets.index');
    Route::get('assets/export', [AssetController::class, 'export'])->name('assets.export');
    Route::get('assets/{asset}', [AssetController::class, 'show'])->name('assets.show');

    Route::get('licenses', [SoftwareLicenseController::class, 'index'])->name('licenses.index');
    Route::get('licenses/{license}', [SoftwareLicenseController::class, 'show'])->name('licenses.show');

    Route::get('contracts', [ContractController::class, 'index'])->name('contracts.index');
    Route::get('contracts/{contract}', [ContractController::class, 'show'])->name('contracts.show');

    Route::get('reservations', [ReservationController::class, 'index'])->name('reservations.index');
    Route::get('reservations/create', [ReservationController::class, 'create'])->name('reservations.create');
    Route::post('reservations', [ReservationController::class, 'store'])->name('reservations.store');
    Route::get('reservations/{reservation}', [ReservationController::class, 'show'])->name('reservations.show');
    Route::post('reservations/{reservation}/cancel', [ReservationController::class, 'cancel'])->name('reservations.cancel');

    // Documents attachés (équipements / tickets)
    Route::post('documents', [DocumentController::class, 'store'])->name('documents.store');
    Route::get('documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::delete('documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');

    // Assistant IA (création de ticket)
    Route::get('ai/health', AiHealthController::class)
        ->middleware('throttle:30,1')
        ->name('ai.health');
    Route::post('ai/ticket-suggestions', TicketSuggestionController::class)
        ->middleware('throttle:20,1')
        ->name('ai.ticket-suggestions');

    // Tickets : création et suivi pour tous, traitement pour les techniciens
    Route::get('tickets/export', [TicketController::class, 'export'])->name('tickets.export');
    Route::resource('tickets', TicketController::class)->only(['index', 'create', 'store', 'show']);
    Route::post('tickets/{ticket}/comments', [TicketCommentController::class, 'store'])->name('tickets.comments.store');
    Route::post('tickets/{ticket}/close', [TicketController::class, 'close'])->name('tickets.close');
    Route::post('tickets/{ticket}/reopen', [TicketController::class, 'reopen'])->name('tickets.reopen');
    Route::post('tickets/{ticket}/satisfaction', [TicketController::class, 'rate'])->name('tickets.satisfaction');

    Route::middleware('role:technician')->group(function () {
        Route::post('tickets/{ticket}/assign', [TicketController::class, 'assign'])->name('tickets.assign');
        Route::post('tickets/{ticket}/start', [TicketController::class, 'start'])->name('tickets.start');
        Route::post('tickets/{ticket}/resolve', [TicketController::class, 'resolve'])->name('tickets.resolve');

        Route::post('ai/tickets/{ticket}/resolution-assist', TicketResolutionAssistController::class)
            ->middleware('throttle:20,1')
            ->name('ai.ticket-resolution-assist');

        Route::post('ai/tickets/{ticket}/faq-draft', [TicketFaqDraftController::class, 'suggest'])
            ->middleware('throttle:20,1')
            ->name('ai.ticket-faq-draft');

        Route::post('tickets/{ticket}/faq', [TicketFaqDraftController::class, 'store'])
            ->name('tickets.faq.store');
    });

    // FAQ : consultation pour tous, gestion pour l'IT
    Route::get('faq', [FaqController::class, 'index'])->name('faq.index');
    Route::get('faq/{faq}', [FaqController::class, 'show'])->name('faq.show');

    Route::middleware('role:technician')->group(function () {
        Route::get('faq-manage/create', [FaqController::class, 'create'])->name('faq.create');
        Route::post('faq', [FaqController::class, 'store'])->name('faq.store');
        Route::get('faq/{faq}/edit', [FaqController::class, 'edit'])->name('faq.edit');
        Route::put('faq/{faq}', [FaqController::class, 'update'])->name('faq.update');
        Route::delete('faq/{faq}', [FaqController::class, 'destroy'])->name('faq.destroy');
    });
});

require __DIR__.'/auth.php';
