<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\ConfigurationController;
use App\Http\Controllers\Admin\ContentController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LoginController;
use App\Http\Controllers\Admin\MasterDataController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\TableController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Middleware\EnsureAdmin;
use Illuminate\Support\Facades\Route;

Route::get('/admin/login', [LoginController::class, 'create'])->name('admin.login');
Route::post('/admin/login', [LoginController::class, 'store'])->middleware('throttle:6,1')->name('admin.login.store');

Route::middleware(EnsureAdmin::class)->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/outlets', [MasterDataController::class, 'index'])->name('outlets.index');
    Route::post('/outlets', [MasterDataController::class, 'storeOutlet'])->name('outlets.store');
    Route::post('/areas', [MasterDataController::class, 'storeArea'])->name('areas.store');
    Route::get('/tables', [TableController::class, 'index'])->name('tables.index');
    Route::post('/tables', [TableController::class, 'store'])->name('tables.store');
    Route::put('/tables/{table}', [TableController::class, 'update'])->whereNumber('table')->name('tables.update');
    Route::get('/menu', [MenuController::class, 'index'])->name('menu.index');
    Route::post('/menu', [MenuController::class, 'store'])->name('menu.store');
    Route::put('/menu/{menuItem}', [MenuController::class, 'update'])->whereNumber('menuItem')->name('menu.update');
    Route::post('/menu/categories', [MenuController::class, 'storeCategory'])->name('menu.categories.store');
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::post('/users/{user}/toggle', [UserController::class, 'toggle'])->whereNumber('user')->name('users.toggle');
    Route::get('/content', [ContentController::class, 'index'])->name('content.index');
    Route::post('/content', [ContentController::class, 'store'])->name('content.store');
    Route::put('/content/{outletContent}', [ContentController::class, 'update'])->whereNumber('outletContent')->name('content.update');
    Route::get('/configuration', [ConfigurationController::class, 'index'])->name('configuration.index');
    Route::post('/configuration', [ConfigurationController::class, 'store'])->name('configuration.store');
    Route::get('/audit', [AuditLogController::class, 'index'])->name('audit.index');
});
