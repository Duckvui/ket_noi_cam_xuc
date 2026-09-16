<?php

namespace App\Services;

use App\Events\CamXucDaThayDoi;
use App\Models\Avatar;
use App\Models\CamXuc;
use App\Models\TaiKhoan;
use App\Models\TrangThaiCamXuc;
use App\Policies\CamXucPolicy;
use Illuminate\Support\Facades\DB;

class DichVuCamXuc
{
    public function danhMuc(): array
    {
        return CamXuc::where('TrangThai', 'Dang_Su_Dung')->whereIn('TenCamXuc', array_keys(config('cam_xuc.cam_xucs')))->get()
            ->map(fn ($mood) => ['idCamXuc' => $mood->idCamXuc, ...config('cam_xuc.cam_xucs.'.$mood->TenCamXuc)])->all();
    }

    public function hienThi(TaiKhoan $owner, TaiKhoan $viewer): array
    {
        $owner->loadMissing('avatarHienTai', 'camXucHienTai.camXuc');
        $state = $owner->camXucHienTai;
        $mood = $state && (new CamXucPolicy)->view($viewer, $state)
            ? config('cam_xuc.cam_xucs.'.$state->camXuc?->TenCamXuc) : null;

        return [
            'idTaiKhoan' => $owner->idTaiKhoan,
            'ma_avatar' => $owner->avatarHienTai?->MaAvatarAnimation,
            'anh_avatar' => $owner->avatarHienTai?->AnhAvatar,
            'cam_xuc' => $mood ? ['idCamXuc' => $state->idCamXuc, 'ma' => $mood['ma'], 'ten' => $mood['ten'], 'thoi_gian' => $state->ThoiGianTao?->toISOString(), 'che_do' => $state->CheDoHienThi] : null,
        ];
    }

    public function doiAvatar(TaiKhoan $owner, ?string $ma): void
    {
        DB::transaction(function () use ($owner, $ma) {
            TaiKhoan::whereKey($owner->idTaiKhoan)->lockForUpdate()->firstOrFail();
            $avatar = Avatar::where('idTaiKhoan', $owner->idTaiKhoan)->latest('idAvatar')->first()
                ?? new Avatar(['idTaiKhoan' => $owner->idTaiKhoan]);
            $avatar->MaAvatarAnimation = $ma;
            $avatar->NgayCapNhatAvatar = now();
            $avatar->save();
        }, 3);
        CamXucDaThayDoi::dispatch($owner->idTaiKhoan);
    }

    public function capNhat(TaiKhoan $owner, array $data): void
    {
        DB::transaction(function () use ($owner, $data) {
            TaiKhoan::whereKey($owner->idTaiKhoan)->lockForUpdate()->firstOrFail();
            TrangThaiCamXuc::where('idTaiKhoan', $owner->idTaiKhoan)->where('TrangThai', 'Hien_Tai')->update(['TrangThai' => 'Da_Cu']);
            $state = TrangThaiCamXuc::create([
                'idTaiKhoan' => $owner->idTaiKhoan, 'idCamXuc' => $data['idCamXuc'],
                'CheDoHienThi' => $data['CheDoHienThi'], 'ThoiGianTao' => now(), 'TrangThai' => 'Hien_Tai',
            ]);
            app(DichVuPet::class)->tacDong($state);
        }, 3);
        CamXucDaThayDoi::dispatch($owner->idTaiKhoan);
    }
}
