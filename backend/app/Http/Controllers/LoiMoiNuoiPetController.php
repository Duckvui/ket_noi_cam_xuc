<?php

namespace App\Http\Controllers;

use App\Models\BanBe;
use App\Models\LoiMoiNuoiPet;
use App\Models\TaiKhoan;
use App\Services\DichVuPet;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LoiMoiNuoiPetController extends Controller
{
    public function index(Request $request)
    {
        $id = $request->user('tai_khoan')->idTaiKhoan;
        $friends = BanBe::where('TrangThai', 'Dang_La_Ban')->where(fn ($q) => $q->where('idTaiKhoan1', $id)->orWhere('idTaiKhoan2', $id))->get()
            ->map(fn ($row) => $row->idTaiKhoan1 === $id ? $row->idTaiKhoan2 : $row->idTaiKhoan1);
        $names = TaiKhoan::with('thongTinCaNhan')->whereIn('idTaiKhoan', $friends)->where('TrangThaiTaiKhoan', 'Hoat_Dong')->get()
            ->map(fn ($user) => ['id' => $user->idTaiKhoan, 'ten' => trim(($user->thongTinCaNhan?->Ho ?? '').' '.($user->thongTinCaNhan?->Ten ?? '')) ?: $user->TaiKhoan]);
        $invites = LoiMoiNuoiPet::with(['taiKhoan1.thongTinCaNhan', 'taiKhoan2.thongTinCaNhan'])->where('TrangThai', 'Cho_Xac_Nhan')
            ->where(fn ($q) => $q->where('idTaiKhoan1', $id)->orWhere('idTaiKhoan2', $id))->latest('NgayTao')->get()->map(function ($invite) use ($id) {
                $other = $invite->idTaiKhoan1 === $id ? $invite->taiKhoan2 : $invite->taiKhoan1;

                return ['idLoiMoiPet' => $invite->idLoiMoiPet, 'TenPet' => $invite->TenPet, 'LoaiPet' => $invite->LoaiPet,
                    'idNguoiGui' => $invite->idNguoiGui, 'ten_ban' => trim(($other->thongTinCaNhan?->Ho ?? '').' '.($other->thongTinCaNhan?->Ten ?? '')) ?: $other->TaiKhoan];
            });

        return response()->json(['data' => ['loi_moi' => $invites, 'ban_be' => $names,
            'loai_pet' => collect(config('cam_xuc.avatars'))->map(fn ($ten, $ma) => ['ma' => $ma, 'ten' => $ten])->values(),
            'hanh_dong' => collect(config('pet.hanh_dong'))->map(fn ($data, $ma) => ['ma' => $ma, 'ten' => $data['ten']])->values()]]);
    }

    public function store(Request $request, DichVuPet $service)
    {
        $data = $request->validate(['idTaiKhoan' => ['required', 'integer', Rule::exists('tai_khoans', 'idTaiKhoan')],
            'TenPet' => ['required', 'string', 'max:50'], 'LoaiPet' => ['required', Rule::in(array_keys(config('cam_xuc.avatars')))]]);

        return response()->json(['data' => $service->moi($request->user('tai_khoan'), $data)], 201);
    }

    public function update(Request $request, LoiMoiNuoiPet $loiMoiNuoiPet, DichVuPet $service)
    {
        $data = $request->validate(['action' => ['required', Rule::in(['chap_nhan', 'tu_choi'])]]);
        $pet = $service->phanHoi($request->user('tai_khoan'), $loiMoiNuoiPet, $data['action']);

        return response()->json(['data' => $pet ? $service->duLieu($pet) : null]);
    }
}
