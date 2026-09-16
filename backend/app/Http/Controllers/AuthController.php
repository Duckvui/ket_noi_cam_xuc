<?php

namespace App\Http\Controllers;

use App\Models\TaiKhoan;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'HoTen' => ['required', 'string', 'max:255'],
            'NamSinh' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:1000-01-01', 'before_or_equal:today'],
            'GioiThieu' => ['nullable', 'string', 'max:2000'],
            'TaiKhoan' => ['required', 'string', 'max:255', 'regex:/^\S+$/u', 'unique:tai_khoans,TaiKhoan'],
            'MatKhau' => ['required', 'string', 'min:8', 'max:72', 'confirmed'],
        ], [
            'HoTen.required' => 'Vui lòng nhập họ và tên.',
            'HoTen.max' => 'Họ và tên tối đa 255 ký tự.',
            'NamSinh.date_format' => 'Ngày sinh không hợp lệ.',
            'NamSinh.after_or_equal' => 'Ngày sinh không hợp lệ.',
            'NamSinh.before_or_equal' => 'Ngày sinh không được ở tương lai.',
            'GioiThieu.string' => 'Giới thiệu phải là văn bản.',
            'GioiThieu.max' => 'Giới thiệu tối đa 2000 ký tự.',
            'TaiKhoan.required' => 'Vui lòng nhập tài khoản.',
            'TaiKhoan.max' => 'Tài khoản tối đa 255 ký tự.',
            'TaiKhoan.regex' => 'Tài khoản không được chứa khoảng trắng.',
            'TaiKhoan.unique' => 'Tài khoản này đã được sử dụng.',
            'MatKhau.required' => 'Vui lòng nhập mật khẩu.',
            'MatKhau.min' => 'Mật khẩu phải có ít nhất 8 ký tự.',
            'MatKhau.max' => 'Mật khẩu tối đa 72 ký tự.',
            'MatKhau.confirmed' => 'Mật khẩu xác nhận không khớp.',
        ]);

        // Bcrypt limits input to 72 bytes, which may be fewer than 72 Unicode characters.
        if (strlen($data['MatKhau']) > 72) {
            throw ValidationException::withMessages(['MatKhau' => 'Mật khẩu quá dài, vui lòng dùng tối đa 72 byte.']);
        }

        try {
            $taiKhoan = DB::transaction(function () use ($data): TaiKhoan {
                $account = TaiKhoan::create([
                    'TaiKhoan' => $data['TaiKhoan'],
                    'MatKhau' => Hash::make($data['MatKhau']),
                    'LoaiTaiKhoan' => 'NguoiDung',
                    'TrangThaiTaiKhoan' => 'Hoat_Dong',
                ]);
                $parts = preg_split('/\s+/u', trim($data['HoTen']));
                $ten = array_pop($parts);
                $account->thongTinCaNhan()->create([
                    'Ho' => implode(' ', $parts),
                    'Ten' => $ten,
                    'NamSinh' => $data['NamSinh'] ?? null,
                    'GioiThieu' => $data['GioiThieu'] ?? null,
                ]);

                return $account;
            });
        } catch (UniqueConstraintViolationException $exception) {
            if (! TaiKhoan::where('TaiKhoan', $data['TaiKhoan'])->exists()) {
                throw $exception;
            }
            throw ValidationException::withMessages(['TaiKhoan' => 'Tài khoản này đã được sử dụng.']);
        }

        return response()->json([
            'message' => 'Đăng ký thành công. Bạn có thể đăng nhập ngay.',
            'data' => $this->userData($taiKhoan),
        ], 201);
    }

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
