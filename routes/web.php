<?php

use App\Http\Controllers\Auth\ConfirmPasswordController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\VerificationController;
use App\Http\Controllers\BulkMailController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ErrorController;
use App\Http\Controllers\FrontIndexController;
use App\Http\Controllers\LegalController;
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

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/onkentes-regisztracio', [FrontIndexController::class, 'volunteer_form'])->name('onkentes-regisztracio');
Route::get('/segitseg-keres', [FrontIndexController::class, 'helprequest_form'])->name('segitseg-keres');
Route::post('/postcode-autocomplete', [FrontIndexController::class, 'post_code_ajax'])->middleware('throttle:30,1')->name('post_code_autocomplete');
Route::post('/form-submit', [FrontIndexController::class, 'register'])->middleware('throttle:10,1');

// Authentication (laravel/ui). Kept explicit for Laravel 12 (no Auth::routes magic).
Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('login', [LoginController::class, 'login']);
Route::post('logout', [LoginController::class, 'logout'])->name('logout');

Route::get('register', [RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('register', [RegisterController::class, 'register']);

Route::get('password/reset', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
Route::post('password/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
Route::get('password/reset/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
Route::post('password/reset', [ResetPasswordController::class, 'reset'])->name('password.update');

Route::get('password/confirm', [ConfirmPasswordController::class, 'showConfirmForm'])->name('password.confirm');
Route::post('password/confirm', [ConfirmPasswordController::class, 'confirm']);

Route::get('email/verify', [VerificationController::class, 'show'])->name('verification.notice');
Route::get('email/verify/{id}/{hash}', [VerificationController::class, 'verify'])->middleware(['signed'])->name('verification.verify');
Route::post('email/resend', [VerificationController::class, 'resend'])->middleware(['throttle:6,1'])->name('verification.resend');

Route::middleware(['verified'])->group(function () {
    Route::group(['prefix' => 'admin'], function () {
        Route::get('/', [DashboardController::class, 'index'])->name('admin_home');
        Route::get('/sajat-segitsegkeresek', [DashboardController::class, 'own_help_request_list'])->name('admin_own_help_request');
        Route::get('/leadott-segitsegkeresek', [DashboardController::class, 'my_help_request_list'])->name('admin_my_help_request');
        Route::get('/nyitott-segitsegkeresek', [DashboardController::class, 'open_help_request_list'])->name('admin_open_help_request');
        Route::get('/uj-segitsegkeres', [DashboardController::class, 'new_help_request'])->name('admin_new_help_request');
        Route::get('/vigyazok', [DashboardController::class, 'volunteers_list'])->name('admin_volunteers_list');
        Route::get('/profile', [DashboardController::class, 'profile'])->name('admin_profile');

        Route::post('/profile', [DashboardController::class, 'save_profile'])->name('admin_save_profile');
        Route::post('/save_support_data', [DashboardController::class, 'save_support_data'])->name('admin_save_support_data');
        Route::post('/volunteer_for_support_ajax', [DashboardController::class, 'volunteer_for_support_ajax'])->name('volunteer_for_support_ajax');
        Route::post('/get_more_user_details_ajax', [DashboardController::class, 'get_more_user_details_ajax'])->name('get_more_user_details_ajax');
        Route::post('/send_volunteer_notification_ajax', [DashboardController::class, 'send_volunteer_notification_ajax'])->name('send_volunteer_notification_ajax');

        Route::get('/korlevelek', [BulkMailController::class, 'index'])->name('bulk_mail_index');
        Route::get('/korlevelek/uj', [BulkMailController::class, 'create'])->name('bulk_mail_create');
        Route::post('/korlevelek', [BulkMailController::class, 'store'])->name('bulk_mail_store')->middleware('throttle:10,1');
    });
});

Route::get('/jogi_nyilatkozat', [LegalController::class, 'legal_text'])->name('legal');
Route::get('/adatvedelmi_nyilatkozat', [LegalController::class, 'privacy_text'])->name('privacy');
Route::get('/cookie_hasznalati_szabalyzat', [LegalController::class, 'cookie_text'])->name('cookie');
Route::get('/hibabejelentes', [ErrorController::class, 'index'])->name('error_reporting');
Route::post('/hiba_bekuldes', [ErrorController::class, 'submit'])->middleware('throttle:10,1')->name('error_sending');
