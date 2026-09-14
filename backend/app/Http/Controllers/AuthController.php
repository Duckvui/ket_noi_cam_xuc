<?php

namespace App\Http\Controllers;

use App\Models\TaiKhoan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'tai_khoan' => ['required', 'string'],
            'mat_khau' => ['required', 'string'],
        ]);

        $taiKhoan = TaiKhoan::where('TaiKhoan', $credentials['tai_khoan'])->first();

        if (! $taiKhoan || ! Hash::check($credentials['mat_khau'], $taiKhoan->MatKhau)) {
            return response()->json(['message' => 'Tài khoản hoặc mật khẩu không đúng.'], 422);
        }

        if ($taiKhoan->TrangThaiTaiKhoan !== 'Hoat_Dong') {
            return response()->json(['message' => 'Tài khoản hiện không thể đăng nhập.'], 403);
        }

        Auth::guard('tai_khoan')->login($taiKhoan);
        $request->session()->regenerate();

        return response()->json(['message' => 'Đăng nhập thành công.', 'data' => $this->userData($taiKhoan)]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->userData($request->user('tai_khoan'))]);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('tai_khoan')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Đã đăng xuất.']);
    }

    private function userData(TaiKhoan $taiKhoan): array
    {
        $taiKhoan->load('thongTinCaNhan');

        return [
            'id' => $taiKhoan->idTaiKhoan,
            'tai_khoan' => $taiKhoan->TaiKhoan,
            'loai_tai_khoan' => $taiKhoan->LoaiTaiKhoan,
            'ten_hien_thi' => trim(($taiKhoan->thongTinCaNhan?->Ho ?? '').' '.($taiKhoan->thongTinCaNhan?->Ten ?? '')) ?: $taiKhoan->TaiKhoan,
        ];
    }
}
