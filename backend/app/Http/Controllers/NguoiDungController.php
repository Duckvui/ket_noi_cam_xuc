<?php

namespace App\Http\Controllers;

use App\Models\Avatar;
use App\Models\BaiViet;
use App\Models\BanBe;
use App\Models\TaiKhoan;
use App\Models\TheoDoi;
use Illuminate\Http\Request;

class NguoiDungController extends Controller
{
    public function show(Request $request, TaiKhoan $taiKhoan)
    {
        $viewer = $request->user('tai_khoan');
        abort_if($viewer->idTaiKhoan === $taiKhoan->idTaiKhoan, 404);
        $taiKhoan->load('thongTinCaNhan');
        $avatar = Avatar::where('idTaiKhoan', $taiKhoan->idTaiKhoan)->latest('idAvatar')->first();
        $isFriend = BanBe::where('TrangThai', 'Dang_La_Ban')->where(fn ($q) => $q->where('idTaiKhoan1', $viewer->idTaiKhoan)->where('idTaiKhoan2', $taiKhoan->idTaiKhoan)->orWhere('idTaiKhoan1', $taiKhoan->idTaiKhoan)->where('idTaiKhoan2', $viewer->idTaiKhoan))->exists();
        $isFollowing = TheoDoi::where('idTaiKhoan', $viewer->idTaiKhoan)->where('idTaiKhoanTheoDoi', $taiKhoan->idTaiKhoan)->where('TrangThai', true)->exists();
        $posts = BaiViet::where('idTaiKhoan', $taiKhoan->idTaiKhoan)->where('TrangThaiBaiViet', 'Binh_Thuong')->where('CheDoHienThi', 'Cong_Khai')->latest('NgayDang')->limit(10)->get();

        return response()->json(['data' => [
            'id' => $taiKhoan->idTaiKhoan, 'ten_hien_thi' => trim(($taiKhoan->thongTinCaNhan?->Ho ?? '').' '.($taiKhoan->thongTinCaNhan?->Ten ?? '')) ?: $taiKhoan->TaiKhoan,
            'tai_khoan' => $taiKhoan->TaiKhoan, 'gioi_thieu' => $taiKhoan->thongTinCaNhan?->GioiThieu,
            'avatar' => $avatar?->AnhAvatar, 'anh_bia' => $avatar?->AnhBia, 'la_ban' => $isFriend, 'dang_theo_doi' => $isFollowing,
            'posts' => $posts->map(fn ($post) => ['id' => $post->idBaiViet, 'content' => $post->NoiDung, 'published_at' => $post->NgayDang]),
        ]]);
    }

    public function follow(Request $request, TaiKhoan $taiKhoan)
    {
        $viewer = $request->user('tai_khoan');
        abort_if($viewer->idTaiKhoan === $taiKhoan->idTaiKhoan, 422);
        TheoDoi::updateOrCreate(['idTaiKhoan' => $viewer->idTaiKhoan, 'idTaiKhoanTheoDoi' => $taiKhoan->idTaiKhoan], ['TrangThai' => true, 'NgayTao' => now()]);
        return response()->json(['message' => 'Đã theo dõi người dùng này.']);
    }
}
