<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BaiVietController;
use App\Http\Controllers\GuiKetBanController;
use App\Http\Controllers\NguoiDungController;
use App\Http\Controllers\TinController;
use App\Http\Controllers\ChatController;
use App\Models\CamXuc;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth:tai_khoan')->prefix('api/tins')->group(function (): void {
    Route::get('/', [TinController::class, 'index']);
    Route::post('/', [TinController::class, 'store'])->middleware('throttle:20,1');
    Route::get('/{tin}/media', [TinController::class, 'media'])->whereNumber('tin')->name('tins.media');
    Route::post('/{tin}/xem', [TinController::class, 'view'])->whereNumber('tin');
    Route::post('/{tin}/cam-xuc', [TinController::class, 'react'])->whereNumber('tin');
});

Route::middleware('auth:tai_khoan')->prefix('api/cuoc-tro-chuyens')->group(function (): void {
    Route::get('/', [ChatController::class, 'index']);
    Route::post('/', [ChatController::class, 'start']);
    Route::get('/{conversation}/tin-nhans', [ChatController::class, 'messages'])->whereNumber('conversation');
    Route::post('/{conversation}/tin-nhans', [ChatController::class, 'send'])->whereNumber('conversation')->middleware('throttle:60,1');
});

Route::middleware('auth:tai_khoan')->prefix('api/bai-viets')->group(function (): void {
    Route::get('/', [BaiVietController::class, 'index']);
    Route::post('/', [BaiVietController::class, 'store'])->middleware('throttle:20,1');
    Route::get('/{baiViet}/media', [BaiVietController::class, 'media'])->whereNumber('baiViet')->name('bai-viets.media');
    Route::post('/{baiViet}/cam-xuc', [BaiVietController::class, 'react'])->whereNumber('baiViet');
    Route::match(['get', 'post'], '/{baiViet}/binh-luans', [BaiVietController::class, 'comments'])->whereNumber('baiViet');
});

Route::middleware('auth:tai_khoan')->get('api/cam-xucs', fn () => response()->json(['data' => CamXuc::where('TrangThai', 'Dang_Su_Dung')->orderBy('TenCamXuc')->get(['idCamXuc', 'TenCamXuc', 'BieuTuong'])]));

Route::middleware('auth:tai_khoan')->prefix('api/ket-ban')->group(function (): void {
    Route::get('/', [GuiKetBanController::class, 'index']);
    Route::get('/tim-kiem', [GuiKetBanController::class, 'search']);
    Route::post('/', [GuiKetBanController::class, 'store'])->middleware('throttle:20,1');
    Route::patch('/{guiKetBan}', [GuiKetBanController::class, 'update'])->whereNumber('guiKetBan');
});

Route::middleware('auth:tai_khoan')->prefix('api/nguoi-dung')->group(function (): void {
    Route::get('/{taiKhoan}', [NguoiDungController::class, 'show'])->whereNumber('taiKhoan');
    Route::post('/{taiKhoan}/theo-doi', [NguoiDungController::class, 'follow'])->whereNumber('taiKhoan');
});

Route::prefix('api/auth')->group(function (): void {
    Route::post('/login', [AuthController::class, 'login']);
    Route::middleware('auth:tai_khoan')->get('/me', [AuthController::class, 'me']);
    Route::middleware('auth:tai_khoan')->post('/logout', [AuthController::class, 'logout']);
});
