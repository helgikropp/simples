<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;

use App\Http\Controllers\LoginController;
use App\Http\Controllers\MainController;
use App\Http\Controllers\PdfController;
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

Route::get('/lng/{lng}', function ($lng) {
    Session::put('locale',$lng);
    return redirect()->back();
});

...

Route::group(['middleware'=>'lang'],function(){
    Route::get('/', [LoginController::class,'showLoginPage'])->name('login');
    ...
    Route::get('/help', function () { return view('help'); })->name('help');
    ...
    Route::group(['middleware'=>'auth.company'],function(){
        Route::post('logon', function(){ return redirect()->route('card'); })->name('logon');
        ...
        Route::get('/card',  [MainController::class, 'getCardPage'])->name('card');   
        ...
        Route::match(['get', 'post'],'/pay',   [MainController::class, 'getPaymentsPage'])->name('pay');   
        ...
        Route::post('/invoice/pdf/get',  [PdfController::class, 'getInvoicePdf'])->name('invoice.pdf');   
        ...
        Route::post('/bill/{id}/xls/get',  [MainController::class, 'getBillXls'])->name('bill.xls');   
        ...
    });
});
