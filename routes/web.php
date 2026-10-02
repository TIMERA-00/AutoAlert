<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminStatsController;
use App\Http\Controllers\Admin\AdminVehiclePreviewController;
use App\Http\Controllers\BrandRedirectController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SourceRedirectController;
use App\Livewire\Admin\AlertMonitor;
use App\Livewire\Admin\NotificationLog;
use App\Livewire\Admin\SourceManager;
use App\Livewire\Admin\UserManager;
use App\Livewire\Admin\VehicleForm;
use App\Livewire\Admin\VehicleImporter;
use App\Livewire\Admin\VehicleTable;
use App\Livewire\Alerts\AlertForm;
use App\Livewire\Alerts\AlertsList;
use App\Livewire\Favorites\FavoritesList;
use App\Livewire\Notifications\NotificationsList;
use App\Livewire\Profile\NotificationPreferences;
use App\Livewire\VehicleCatalog;
use App\Livewire\VehicleShow;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public catalogue
|--------------------------------------------------------------------------
*/

Route::view('/', 'home')->name('home');

Route::get('/vehicles', VehicleCatalog::class)->name('vehicles.index');
Route::get('/vehicles/{vehicle:slug}', VehicleShow::class)->name('vehicles.show');

Route::get('/marques/{brand}', [BrandRedirectController::class, 'show'])
    ->name('brands.show');

/** Tracks the outbound click to the original listing before redirecting. */
Route::get('/source/{vehicle:slug}', [SourceRedirectController::class, 'show'])
    ->name('source.redirect');

Route::post('/source/{vehicle:slug}', [SourceRedirectController::class, 'store'])
    ->name('source.click');

Route::get('/aide', PageController::class)->name('help');
Route::get('/mentions-legales', PageController::class)->name('legal');
Route::get('/contact', PageController::class)->name('contact');
Route::post('/contact', [PageController::class, 'store'])->middleware('auth')->name('contact.store');

/*
|--------------------------------------------------------------------------
| Authenticated user area
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/favoris', FavoritesList::class)->name('favorites.index');

    Route::get('/alertes', AlertsList::class)->name('alerts.index');
    Route::get('/alertes/nouvelle', AlertForm::class)->name('alerts.create');
    Route::get('/alertes/{alert}/modifier', AlertForm::class)->name('alerts.edit');

    Route::get('/notifications', NotificationsList::class)->name('notifications.index');

    Route::get('/preferences', NotificationPreferences::class)->name('preferences.edit');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/tableau-de-bord', DashboardController::class)->name('user.dashboard');
});

/*
|--------------------------------------------------------------------------
| Admin area
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'active', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', AdminDashboardController::class)->name('dashboard');

    Route::get('/vehicules', VehicleTable::class)->name('vehicles.index');
    Route::get('/vehicules/nouveau', VehicleForm::class)->name('vehicles.create');
    Route::get('/vehicules/import', VehicleImporter::class)->name('vehicles.import');
    Route::get('/vehicules/{vehicle:slug}/modifier', VehicleForm::class)->name('vehicles.edit');
    Route::get('/vehicules/{vehicle:slug}', AdminVehiclePreviewController::class)->name('vehicles.preview');

    Route::get('/sources', SourceManager::class)->name('sources.index');
    Route::get('/utilisateurs', UserManager::class)->name('users.index');
    Route::get('/alertes', AlertMonitor::class)->name('alerts.index');
    Route::get('/notifications', NotificationLog::class)->name('notifications.index');
    Route::get('/statistiques', AdminStatsController::class)->name('stats');
});

/*
|--------------------------------------------------------------------------
| Auth (Breeze)
|--------------------------------------------------------------------------
*/

require __DIR__.'/auth.php';
