<?php

use App\Http\Controllers\Admin\BookController;
use App\Http\Controllers\Admin\BorrowingController as AdminBorrowingController;
use App\Http\Controllers\Admin\BorrowRequestController as AdminBorrowRequestController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MemberController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Member\BorrowingController as MemberBorrowingController;
use App\Http\Controllers\Member\BorrowRequestController as MemberBorrowRequestController;
use App\Http\Controllers\Member\DashboardController as MemberDashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('member.borrow-request');
});

Route::controller(LoginController::class)->group(function () {
    Route::get('/login', 'showLogin')->name('login');
    Route::post('/login', 'login')->name('login.store');
});

Route::redirect('/admin/login', '/login')->name('admin.login');
Route::redirect('/member/login', '/login')->name('member.login');

Route::post('/admin/logout', [LoginController::class, 'logout'])
    ->defaults('guard', 'admin')
    ->name('admin.logout');

Route::post('/member/logout', [LoginController::class, 'logout'])
    ->defaults('guard', 'member')
    ->name('member.logout');

Route::prefix('member')->name('member.')->group(function () {
    Route::controller(MemberBorrowRequestController::class)->group(function () {
        Route::get('/borrow-request', 'create')->name('borrow-request');
        Route::post('/borrow-request', 'store')->name('borrow-request.store');
        Route::get('/borrow-request/latest', 'latestRequests')->name('borrow-request.latest');
        Route::get('/books/search', 'searchBooks')->name('books.search');
    });
});

Route::prefix('admin')->name('admin.')->middleware('auth:admin')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::controller(BookController::class)->prefix('books')->name('books')->group(function () {
        Route::get('/', 'index');
        Route::get('/data', 'data')->name('.data');
        Route::post('/', 'store')->name('.store');
        Route::put('/{book}', 'update')->name('.update');
        Route::delete('/{book}', 'destroy')->name('.destroy');
    });

    Route::controller(MemberController::class)->prefix('members')->name('members')->group(function () {
        Route::get('/', 'index');
        Route::get('/data', 'data')->name('.data');
        Route::post('/', 'store')->name('.store');
        Route::put('/{member}', 'update')->name('.update');
        Route::delete('/{member}', 'destroy')->name('.destroy');
    });

    Route::controller(AdminBorrowRequestController::class)->prefix('borrow-requests')->name('borrow-requests')->group(function () {
        Route::get('/', 'index');
        Route::get('/data', 'data')->name('.data');
        Route::post('/{borrowRequest}/approve', 'approve')->name('.approve');
        Route::post('/{borrowRequest}/reject', 'reject')->name('.reject');
    });

    Route::controller(AdminBorrowingController::class)->prefix('borrowings')->name('borrowings')->group(function () {
        Route::get('/', 'index');
        Route::get('/data', 'data')->name('.data');
        Route::post('/{borrowing}/returned', 'markReturned')->name('.returned');
    });
});

Route::prefix('member')->name('member.')->middleware('auth:member')->group(function () {
    Route::get('/', [MemberDashboardController::class, 'index'])->name('dashboard');

    Route::controller(MemberBorrowingController::class)->prefix('borrowings')->name('borrowings')->group(function () {
        Route::get('/', 'index');
        Route::get('/data', 'data')->name('.data');
    });
});
