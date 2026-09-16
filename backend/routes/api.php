<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AvatarController;
use App\Http\Controllers\BaiVietController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\GuiKetBanController;
use App\Http\Controllers\LoiMoiNuoiPetController;
use App\Http\Controllers\NguoiDungController;
use App\Http\Controllers\PetController;
use App\Http\Controllers\TinController;
use App\Http\Controllers\TrangThaiCamXucController;
use App\Http\Controllers\TuongTacPetController;
use App\Models\CamXuc;
use App\Services\DichVuCamXuc;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

/*
 * Guard tai_khoan uses Laravel's session driver. API routes therefore also
 * receive the web middleware so login, /me, and all protected endpoints share
 * the same session cookie while retaining Laravel's automatic /api prefix.
 */
Route::middleware('web')->group(function (): void {
    Route::prefix('auth')->group(function (): void {
        Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
        Route::post('/login', [AuthController::class, 'login']);
        Route::middleware('auth:tai_khoan')->get('/me', [AuthController::class, 'me']);
        Route::middleware('auth:tai_khoan')->post('/logout', [AuthController::class, 'logout']);
    });

    Route::middleware('auth:tai_khoan')->group(function (): void {
        Route::get('loi-moi-nuoi-pet', [LoiMoiNuoiPetController::class, 'index']);
        Route::post('loi-moi-nuoi-pet', [LoiMoiNuoiPetController::class, 'store'])->middleware('throttle:10,1');
        Route::patch('loi-moi-nuoi-pet/{loiMoiNuoiPet}', [LoiMoiNuoiPetController::class, 'update'])->whereNumber('loiMoiNuoiPet');
        Route::get('pet-chung', [PetController::class, 'index']);
        Route::get('pet-chung/{pet}', [PetController::class, 'show'])->whereNumber('pet');
        Route::patch('pet-chung/{pet}', [PetController::class, 'update'])->whereNumber('pet');
        Route::post('pet-chung/{pet}/tuong-tac', [TuongTacPetController::class, 'store'])->whereNumber('pet')->middleware('throttle:12,1');
        Route::get('avatar-animation', [AvatarController::class, 'index']);
        Route::put('avatar-animation/me', [AvatarController::class, 'store'])->middleware('throttle:20,1');
        Route::get('cam-xuc-ca-nhan/danh-muc', fn (DichVuCamXuc $service) => response()->json(['data' => $service->danhMuc()]));
        Route::get('cam-xuc-ca-nhan', [TrangThaiCamXucController::class, 'index']);
        Route::put('cam-xuc-ca-nhan/me', [TrangThaiCamXucController::class, 'store'])->middleware('throttle:12,1');
        Route::post('realtime/auth', fn (Request $request) => Broadcast::auth($request));
        Route::prefix('tins')->group(function (): void {
            Route::get('/', [TinController::class, 'index']);
            Route::post('/', [TinController::class, 'store'])->middleware('throttle:20,1');
            Route::get('/{tin}/media', [TinController::class, 'media'])->whereNumber('tin')->name('tins.media');
            Route::post('/{tin}/xem', [TinController::class, 'view'])->whereNumber('tin');
            Route::post('/{tin}/cam-xuc', [TinController::class, 'react'])->whereNumber('tin');
            Route::post('/{tin}/tra-loi', [TinController::class, 'reply'])->whereNumber('tin')->middleware('throttle:60,1');
        });

        Route::prefix('cuoc-tro-chuyens')->group(function (): void {
            Route::get('/', [ChatController::class, 'index']);
            Route::post('/', [ChatController::class, 'start']);
            Route::get('/{conversation}/tin-nhans', [ChatController::class, 'messages'])->whereNumber('conversation');
            Route::post('/{conversation}/tin-nhans', [ChatController::class, 'send'])->whereNumber('conversation')->middleware('throttle:60,1');
        });

        Route::prefix('bai-viets')->group(function (): void {
            Route::get('/', [BaiVietController::class, 'index']);
            Route::post('/', [BaiVietController::class, 'store'])->middleware('throttle:20,1');
            Route::get('/{baiViet}/media', [BaiVietController::class, 'media'])->whereNumber('baiViet')->name('bai-viets.media');
            Route::post('/{baiViet}/cam-xuc', [BaiVietController::class, 'react'])->whereNumber('baiViet');
            Route::match(['get', 'post'], '/{baiViet}/binh-luans', [BaiVietController::class, 'comments'])->whereNumber('baiViet');
        });

        Route::get('cam-xucs', fn () => response()->json(['data' => CamXuc::where('TrangThai', 'Dang_Su_Dung')->orderBy('TenCamXuc')->get(['idCamXuc', 'TenCamXuc', 'BieuTuong'])]));

        Route::prefix('ket-ban')->group(function (): void {
            Route::get('/', [GuiKetBanController::class, 'index']);
            Route::get('/tim-kiem', [GuiKetBanController::class, 'search']);
            Route::post('/', [GuiKetBanController::class, 'store'])->middleware('throttle:20,1');
            Route::patch('/{guiKetBan}', [GuiKetBanController::class, 'update'])->whereNumber('guiKetBan');
        });

        Route::prefix('nguoi-dung')->group(function (): void {
            Route::get('/{taiKhoan}', [NguoiDungController::class, 'show'])->whereNumber('taiKhoan');
            Route::post('/{taiKhoan}/theo-doi', [NguoiDungController::class, 'follow'])->whereNumber('taiKhoan');
        });
    });
});
