<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

//Route::get('/', function () {
//    return view('welcome');
//});

Route::get('/', [UserController::class, 'welcome']);

// Route::resource('users', 'UserController');

//Route::get('/user', function () {
//    return 'Hello World';
//});

//Route::group(['middleware' => ['web']], function () {
    // your routes here
    Route::get('/user', [UserController::class, 'index']);
    Route::post('/user/api_key', [UserController::class, 'apiKey']);
    Route::get('/user/datatables', [UserController::class, 'apiDatatables']);
    Route::get('/user/create', [UserController::class, 'create']);
    Route::post('/user/store', [UserController::class, 'store']);
    Route::get('/user/delete', [UserController::class, 'delete']);
    Route::get('/user/logout', [UserController::class, 'logout']);
//});
