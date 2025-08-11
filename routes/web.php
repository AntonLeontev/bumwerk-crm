<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\LeadStatusController;
use App\Http\Controllers\UserController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

if (config('app.url') === 'http://127.0.0.1:8000') {
    Route::get('test', function () {
        //
    });
}

Route::post('login', [AuthController::class, 'login'])
    ->middleware(['throttle:10,5'])
    ->name('login');

Route::controller(UserController::class)->group(function () {
    Route::get('user', 'currentUser')->middleware('auth');
    Route::post('user/update', 'updateProfile')->middleware('precognitive')->name('user.update');
    Route::post('user/password', 'updatePassword')->middleware('precognitive')->name('user.password');

    Route::get('users', 'index')->name('users.index')->can('view', User::class);
    Route::delete('users/{id}', 'destroy')->name('users.destroy')->can('delete', User::class);
    Route::post('users/', 'create')->name('users.create')->can('create', User::class);
    Route::post('users/{user}/invite', 'sendInvite')
        ->middleware(['throttle:3,1'])
        ->name('users.invite')
        ->can('create', User::class);
});

Route::controller(ContactController::class)->group(function () {
    Route::get('contacts', 'index')->name('contacts.index');
    Route::post('contacts', 'store')->name('contacts.store');
    Route::get('contacts/{contact}', 'show')->name('contacts.show');
    Route::put('contacts/{contact}', 'update')->name('contacts.update');
    Route::delete('contacts/{contact}', 'destroy')->name('contacts.destroy');
});

Route::controller(LeadController::class)->group(function () {
    Route::get('leads', 'index')->name('leads.index');
    Route::post('leads', 'store')->name('leads.store');
    Route::get('leads/{lead}', 'show')->name('leads.show');
    Route::put('leads/{lead}', 'update')->name('leads.update');
    Route::delete('leads/{lead}', 'destroy')->name('leads.destroy');
});

Route::controller(LeadStatusController::class)->group(function () {
    Route::get('lead-statuses', 'index')->name('lead-statuses.index');
    Route::post('lead-statuses', 'store')->name('lead-statuses.store');
    Route::get('lead-statuses/{status}', 'show')->name('lead-statuses.show');
    Route::put('lead-statuses/{status}', 'update')->name('lead-statuses.update');
    Route::delete('lead-statuses/{status}', 'destroy')->name('lead-statuses.destroy');
    Route::post('lead-statuses/save-new-order', 'saveNewOrder')->name('lead-statuses.save-new-order');
});

Route::view('reset-password', 'app')->name('password.reset');
Route::view('create-password/{email}/{password}', 'app')->name('password.create');
Route::post('create-password', [UserController::class, 'storePassword'])
    ->middleware(['precognitive', 'throttle:10,5'])
    ->name('password.store');

Route::fallback(function () {
    return view('app');
});
