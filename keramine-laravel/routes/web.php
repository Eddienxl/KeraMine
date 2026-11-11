<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PortalController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PortalController::class, 'home'])->name('home');
Route::get('/stores', [PortalController::class, 'stores'])->name('stores');
Route::match(['get', 'post'], '/recommender', [PortalController::class, 'recommender'])->name('recommender');
Route::get('/rules', [PortalController::class, 'rules'])->name('rules');

Route::get('/history', [PortalController::class, 'history'])->name('history');
Route::get('/history/{history}/export-csv', [PortalController::class, 'exportHistoryCsv'])->name('history.export');
Route::delete('/history/{history}', [PortalController::class, 'destroyHistory'])->name('history.destroy');

Route::get('/pipeline', [PortalController::class, 'pipeline'])->name('pipeline');
Route::post('/pipeline/mine', [PortalController::class, 'triggerMine'])->name('pipeline.mine');
Route::post('/pipeline/simulate', [PortalController::class, 'simulatePipeline'])->name('pipeline.simulate');
Route::post('/pipeline/reset', [PortalController::class, 'resetDemo'])->name('pipeline.reset');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
