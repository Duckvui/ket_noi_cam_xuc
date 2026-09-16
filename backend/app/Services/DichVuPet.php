<?php

namespace App\Services;

use App\Events\PetDaThayDoi;
use App\Models\BanBe;
use App\Models\LoiMoiNuoiPet;
use App\Models\Pet;
use App\Models\TaiKhoan;
use App\Models\TrangThaiCamXuc;
use App\Models\TuongTacPet;
use App\Policies\PetPolicy;
use Illuminate\Support\Facades\DB;

class DichVuPet
{
    public function __construct(private QuyDoiDiemPet $quyDoi) {}

    public function cuaToi(TaiKhoan $user)
    {
        return Pet::whereNotNull('idTaiKhoan2')->where(fn ($q) => $q->where('idTaiKhoan', $user->idTaiKhoan)->orWhere('idTaiKhoan2', $user->idTaiKhoan));
    }

    private function laBan(int $first, int $second): bool
    {
        return BanBe::where('TrangThai', 'Dang_La_Ban')->where(fn ($q) => $q->where(fn ($q) => $q->where('idTaiKhoan1', $first)->where('idTaiKhoan2', $second))
            ->orWhere(fn ($q) => $q->where('idTaiKhoan1', $second)->where('idTaiKhoan2', $first)))->exists();
    }

    private function khoaCap(int $first, int $second): void
    {
        $accounts = TaiKhoan::whereIn('idTaiKhoan', [$first, $second])->orderBy('idTaiKhoan')->lockForUpdate()->get();
        abort_unless($accounts->count() === 2 && $accounts->every(fn ($a) => $a->TrangThaiTaiKhoan === 'Hoat_Dong'), 422, 'Hai tài khoản phải đang hoạt động.');
    }

    public function moi(TaiKhoan $user, array $data): LoiMoiNuoiPet
    {
        $target = (int) $data['idTaiKhoan'];
        abort_if($target === $user->idTaiKhoan, 422, 'Không thể mời chính mình.');

        return DB::transaction(function () use ($user, $target, $data) {
            $first = min($target, $user->idTaiKhoan);
            $second = max($target, $user->idTaiKhoan);
            $this->khoaCap($first, $second);
            abort_unless($this->laBan($first, $second), 403, 'Chỉ được mời bạn bè nuôi pet chung.');
            abort_if(Pet::where('idTaiKhoan', $first)->where('idTaiKhoan2', $second)->exists(), 422, 'Hai bạn đã có pet chung.');
            $invite = LoiMoiNuoiPet::where('idTaiKhoan1', $first)->where('idTaiKhoan2', $second)->first();
            abort_if($invite?->TrangThai === 'Cho_Xac_Nhan', 422, 'Cặp này đã có lời mời đang chờ.');

            return LoiMoiNuoiPet::updateOrCreate(['idTaiKhoan1' => $first, 'idTaiKhoan2' => $second], [
                'idNguoiGui' => $user->idTaiKhoan, 'TenPet' => trim($data['TenPet']), 'LoaiPet' => $data['LoaiPet'],
                'TrangThai' => 'Cho_Xac_Nhan', 'NgayTao' => now(), 'NgayPhanHoi' => null,
            ]);
        }, 3);
    }

    public function phanHoi(TaiKhoan $user, LoiMoiNuoiPet $invite, string $action): ?Pet
    {
        abort_unless(in_array($user->idTaiKhoan, [$invite->idTaiKhoan1, $invite->idTaiKhoan2], true) && $user->idTaiKhoan !== $invite->idNguoiGui, 404);

        return DB::transaction(function () use ($user, $invite, $action) {
            $this->khoaCap($invite->idTaiKhoan1, $invite->idTaiKhoan2);
            $invite = LoiMoiNuoiPet::whereKey($invite->idLoiMoiPet)->lockForUpdate()->firstOrFail();
            abort_unless($user->idTaiKhoan !== $invite->idNguoiGui, 404);
            abort_unless($invite->TrangThai === 'Cho_Xac_Nhan', 422, 'Lời mời đã được xử lý.');
            $pet = null;
            if ($action === 'chap_nhan') {
                abort_unless($this->laBan($invite->idTaiKhoan1, $invite->idTaiKhoan2), 403, 'Hai người không còn là bạn bè.');
                $pet = Pet::create([
                    'idTaiKhoan' => $invite->idTaiKhoan1, 'idTaiKhoan2' => $invite->idTaiKhoan2,
                    'TenPet' => $invite->TenPet, 'LoaiPet' => $invite->LoaiPet, 'DiemCamXuc' => 50,
                    'TrangThaiPet' => 'binh_thuong', 'NgayTao' => now(), 'NgayCapNhat' => now(),
                ]);
                PetDaThayDoi::dispatch($pet->idPet);
            }
            $invite->update(['TrangThai' => $pet ? 'Da_Chap_Nhan' : 'Da_Tu_Choi', 'NgayPhanHoi' => now()]);

            return $pet;
        }, 3);
    }

    public function tacDong(TrangThaiCamXuc $state): void
    {
        // Called inside the same transaction as the personal emotion update.
        $pets = Pet::whereNotNull('idTaiKhoan2')->where(fn ($q) => $q->where('idTaiKhoan', $state->idTaiKhoan)->orWhere('idTaiKhoan2', $state->idTaiKhoan))
            ->orderBy('idPet')->lockForUpdate()->get();
        $mood = $state->camXuc;
        foreach ($pets as $pet) {
            $before = $pet->DiemCamXuc;
            $score = $this->quyDoi->gioiHan($before + $this->quyDoi->diemCamXuc($mood->TenCamXuc));
            $pet->update(['DiemCamXuc' => $score, 'TrangThaiPet' => $this->quyDoi->trangThai($score), 'idCamXucHienTai' => $state->idCamXuc, 'NgayCapNhat' => now()]);
            DB::table('lich_su_cam_xuc_pets')->insert(['idPet' => $pet->idPet, 'idTrangThaiCamXuc' => $state->idTrangThaiCamXuc, 'DiemThayDoi' => $score - $before, 'DiemSau' => $score, 'ThoiGianTao' => now()]);
            PetDaThayDoi::dispatch($pet->idPet);
        }
    }

    public function tuongTac(TaiKhoan $user, Pet $pet, string $ma): void
    {
        abort_unless((new PetPolicy)->update($user, $pet), 404);
        DB::transaction(function () use ($user, $pet, $ma) {
            $pet = Pet::whereKey($pet->idPet)->lockForUpdate()->firstOrFail();
            $last = TuongTacPet::where('idPet', $pet->idPet)->where('idNguoiGui', $user->idTaiKhoan)->latest('ThoiGianTuongTac')->first();
            abort_if($last && $last->ThoiGianTuongTac->gt(now()->subSeconds(config('pet.cooldown'))), 429, 'Hãy chờ 10 giây giữa hai lần chăm sóc pet.');
            $action = config('pet.hanh_dong.'.$ma);
            $before = $pet->DiemCamXuc;
            $score = $this->quyDoi->gioiHan($before + $action['diem']);
            $pet->update(['DiemCamXuc' => $score, 'TrangThaiPet' => $this->quyDoi->trangThai($score), 'idCamXucHienTai' => null, 'NgayCapNhat' => now()]);
            TuongTacPet::create([
                'idPet' => $pet->idPet, 'idNguoiGui' => $user->idTaiKhoan,
                'idNguoiNhan' => $user->idTaiKhoan === $pet->idTaiKhoan ? $pet->idTaiKhoan2 : $pet->idTaiKhoan,
                'LoaiTuongTac' => $action['loai'], 'MaHanhDong' => $ma, 'NoiDung' => $action['ten'],
                'DiemThayDoi' => $score - $before, 'DiemSau' => $score, 'ThoiGianTuongTac' => now(),
            ]);
            PetDaThayDoi::dispatch($pet->idPet);
        }, 3);
    }

    public function doiPet(TaiKhoan $user, Pet $pet, array $data): void
    {
        abort_unless((new PetPolicy)->update($user, $pet), 404);
        DB::transaction(function () use ($pet, $data) {
            $locked = Pet::whereKey($pet->idPet)->lockForUpdate()->firstOrFail();
            $locked->update(['TenPet' => trim($data['TenPet']), 'LoaiPet' => $data['LoaiPet'], 'NgayCapNhat' => now()]);
            PetDaThayDoi::dispatch($pet->idPet);
        }, 3);
    }

    public function duLieu(Pet $pet): array
    {
        $pet->loadMissing('taiKhoan.thongTinCaNhan', 'taiKhoan2.thongTinCaNhan', 'camXucHienTai');
        $members = collect([$pet->taiKhoan, $pet->taiKhoan2])->map(fn ($user) => [
            'id' => $user->idTaiKhoan, 'ten' => trim(($user->thongTinCaNhan?->Ho ?? '').' '.($user->thongTinCaNhan?->Ten ?? '')) ?: $user->TaiKhoan,
        ]);
        $animation = config('cam_xuc.cam_xucs.'.$pet->camXucHienTai?->TenCamXuc.'.ma');

        return ['idPet' => $pet->idPet, 'TenPet' => $pet->TenPet, 'LoaiPet' => $pet->LoaiPet,
            'DiemCamXuc' => $pet->DiemCamXuc, 'TrangThaiPet' => $this->quyDoi->trangThai($pet->DiemCamXuc),
            'Animation' => $animation ?: $this->quyDoi->trangThai($pet->DiemCamXuc), 'NgayCapNhat' => $pet->NgayCapNhat?->toISOString(), 'thanh_vien' => $members];
    }

    public function lichSu(Pet $pet): array
    {
        $emotions = DB::table('lich_su_cam_xuc_pets as l')->join('trang_thai_cam_xucs as t', 't.idTrangThaiCamXuc', '=', 'l.idTrangThaiCamXuc')
            ->join('cam_xucs as c', 'c.idCamXuc', '=', 't.idCamXuc')->where('l.idPet', $pet->idPet)->orderByDesc('l.id')->limit(50)
            ->get(['l.id', 't.idTaiKhoan', 'c.TenCamXuc', 'l.DiemThayDoi', 'l.DiemSau', 'l.ThoiGianTao'])
            ->map(fn ($row) => ['id' => 'cx-'.$row->id, 'idTaiKhoan' => $row->idTaiKhoan, 'noi_dung' => config('cam_xuc.cam_xucs.'.$row->TenCamXuc.'.ten', $row->TenCamXuc), 'diem' => $row->DiemThayDoi, 'diem_sau' => $row->DiemSau, 'thoi_gian' => $row->ThoiGianTao]);
        $actions = $pet->tuongTacs()->latest('idTuongTacPet')->limit(50)->get()->map(fn ($row) => [
            'id' => 'tt-'.$row->idTuongTacPet, 'idTaiKhoan' => $row->idNguoiGui, 'noi_dung' => $row->NoiDung,
            'diem' => $row->DiemThayDoi, 'diem_sau' => $row->DiemSau, 'thoi_gian' => $row->ThoiGianTuongTac?->format('Y-m-d H:i:s'),
        ]);

        return $emotions->concat($actions)->sortByDesc('thoi_gian')->take(50)->values()->all();
    }
}
