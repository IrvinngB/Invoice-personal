<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\CatalogItemController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\RecurringInvoiceController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ThemeController;
use Illuminate\Support\Facades\Route;

// Localization Switcher
Route::get('locale/{locale}', [LocaleController::class, 'switch'])->name('locale.switch');

// Authentication
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Core App Routes
Route::get('/', [DashboardController::class, 'index'])->name('home');
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// Clients
Route::resource('clients', ClientController::class);

// Catalog Items (Services / Products)
Route::resource('catalog', CatalogItemController::class)->except(['show']);

// Invoices & Quotes & Credit Notes
Route::resource('invoices', InvoiceController::class);
Route::post('invoices/{invoice}/finalize', [InvoiceController::class, 'finalize'])->name('invoices.finalize');
Route::post('invoices/{invoice}/void', [InvoiceController::class, 'void'])->name('invoices.void');
Route::post('invoices/{invoice}/duplicate', [InvoiceController::class, 'duplicate'])->name('invoices.duplicate');
Route::post('invoices/{invoice}/convert', [InvoiceController::class, 'convert'])->name('invoices.convert');
Route::post('invoices/{invoice}/credit-note', [InvoiceController::class, 'creditNote'])->name('invoices.credit-note');
Route::get('invoices/{invoice}/preview', [InvoiceController::class, 'preview'])->name('invoices.preview');
Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');
Route::post('invoices/{invoice}/send-email', [InvoiceController::class, 'sendEmail'])->name('invoices.send-email');

// Payments
Route::post('invoices/{invoice}/payments', [PaymentController::class, 'store'])->name('invoices.payments.store');
Route::delete('payments/{payment}', [PaymentController::class, 'destroy'])->name('payments.destroy');

// Recurring Invoices
Route::resource('recurring', RecurringInvoiceController::class)->except(['show', 'edit', 'update']);
Route::post('recurring/{recurring}/generate-now', [RecurringInvoiceController::class, 'generateNow'])->name('recurring.generate-now');

// Settings & Profile
Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');

// Custom invoice themes (preview must be registered before the {theme} routes)
Route::get('settings/themes', [ThemeController::class, 'index'])->name('settings.themes.index');
Route::get('settings/themes/create', [ThemeController::class, 'create'])->name('settings.themes.create');
Route::get('settings/themes/preview', [ThemeController::class, 'preview'])->name('settings.themes.preview');
Route::post('settings/themes', [ThemeController::class, 'store'])->name('settings.themes.store');
Route::get('settings/themes/{theme}/edit', [ThemeController::class, 'edit'])->name('settings.themes.edit');
Route::put('settings/themes/{theme}', [ThemeController::class, 'update'])->name('settings.themes.update');
Route::delete('settings/themes/{theme}', [ThemeController::class, 'destroy'])->name('settings.themes.destroy');

// Backup & Data Safety
Route::get('backup/download-sqlite', [BackupController::class, 'downloadSqlite'])->name('backup.download-sqlite');
Route::post('backup/restore-sqlite', [BackupController::class, 'restoreSqlite'])->name('backup.restore-sqlite');
Route::get('backup/export-json', [BackupController::class, 'exportJson'])->name('backup.export-json');
Route::get('backup/export-csv', [BackupController::class, 'exportInvoicesCsv'])->name('backup.export-csv');
