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
        $relations = app(\App\Services\QuanHeNguoiDung::class);
        $status = $relations->status($viewer->idTaiKhoan, $taiKhoan->idTaiKhoan);
        $taiKhoan->load('thongTinCaNhan');
        $avatar = Avatar::where('idTaiKhoan', $taiKhoan->idTaiKhoan)->latest('idAvatar')->first();
        $isFriend = BanBe::where('TrangThai', 'Dang_La_Ban')->where(fn ($q) => $q->where('idTaiKhoan1', $viewer->idTaiKhoan)->where('idTaiKhoan2', $taiKhoan->idTaiKhoan)->orWhere('idTaiKhoan1', $taiKhoan->idTaiKhoan)->where('idTaiKhoan2', $viewer->idTaiKhoan))->exists();
        $isFollowing = TheoDoi::where('idTaiKhoan', $viewer->idTaiKhoan)->where('idTaiKhoanTheoDoi', $taiKhoan->idTaiKhoan)->where('TrangThai', true)->exists();
        $posts = $status['bi_chan'] ? collect() : BaiViet::where('idTaiKhoan', $taiKhoan->idTaiKhoan)->where('TrangThaiBaiViet', 'Binh_Thuong')->where('CheDoHienThi', 'Cong_Khai')->latest('NgayDang')->limit(30)->get();

        return response()->json(['data' => [
            'id' => $taiKhoan->idTaiKhoan, 'ten_hien_thi' => trim(($taiKhoan->thongTinCaNhan?->Ho ?? '').' '.($taiKhoan->thongTinCaNhan?->Ten ?? '')) ?: $taiKhoan->TaiKhoan,
            'tai_khoan' => $taiKhoan->TaiKhoan, 'gioi_thieu' => $taiKhoan->thongTinCaNhan?->GioiThieu,
            'avatar' => $avatar?->AnhAvatar, 'anh_bia' => $avatar?->AnhBia, 'la_ban' => $isFriend, 'dang_theo_doi' => $isFollowing,
            'posts' => $posts->map(fn ($post) => ['id' => $post->idBaiViet, 'content' => $post->NoiDung, 'published_at' => $post->NgayDang, 'image_url' => $post->HinhAnh ? route('bai-viets.media', $post->idBaiViet, false) : null]),
            ...$status,
        ]]);
    }

    public function follow(Request $request, TaiKhoan $taiKhoan)
    {
        app(\App\Services\QuanHeNguoiDung::class)->change($request->user('tai_khoan')->idTaiKhoan, $taiKhoan->idTaiKhoan, 'follow');
        return response()->json(['message' => 'Đã theo dõi người dùng này.']);
    }

    public function relationship(Request $request, TaiKhoan $taiKhoan)
    {
        $data = $request->validate(['action' => ['required', \Illuminate\Validation\Rule::in(['request', 'accept', 'reject', 'cancel', 'unfriend', 'follow', 'unfollow', 'block', 'unblock'])]]);
        $service = app(\App\Services\QuanHeNguoiDung::class);
        $id = $request->user('tai_khoan')->idTaiKhoan;
        if ($data['action'] === 'request') $service->request($id, $taiKhoan->idTaiKhoan);
        elseif (in_array($data['action'], ['accept', 'reject'], true)) {
            $invite = \App\Models\GuiKetBan::where('idGuiKetBan', $taiKhoan->idTaiKhoan)->where('idDuocKetBan', $id)->where('TrangThaiLoiMoi', 'Cho_Xac_Nhan')->firstOrFail();
            $service->respond($id, $invite, $data['action'] === 'accept' ? 'chap_nhan' : 'tu_choi');
        } else $service->change($id, $taiKhoan->idTaiKhoan, $data['action']);
        return $this->show($request, $taiKhoan);
    }
}
