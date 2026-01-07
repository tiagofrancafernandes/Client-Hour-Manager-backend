<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', fn () => view('welcome'));

Route::get('/db-test', fn () => [
    'database_connection' => config('database.default'),
    'database_hostname' => config('database.connections.' . config('database.default') . '.host'),
    'database_name' => config('database.connections.' . config('database.default') . '.database'),
    'test_conn_pg' => DB::select('SELECT 1 AS result'),
]);

Route::get('/libs', fn () => []);
