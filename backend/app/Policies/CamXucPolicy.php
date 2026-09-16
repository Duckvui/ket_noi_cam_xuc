<?php

namespace App\Policies;

use App\Models\BanBe;
use App\Models\TaiKhoan;
use App\Models\TrangThaiCamXuc;

class CamXucPolicy
{
    public function update(TaiKhoan $user, TaiKhoan $owner): bool
    {
        return $user->idTaiKhoan === $owner->idTaiKhoan && $user->TrangThaiTaiKhoan === 'Hoat_Dong';
    }

    public function view(TaiKhoan $user, TrangThaiCamXuc $state): bool
    {
        if ($user->idTaiKhoan === $state->idTaiKhoan) {
            return true;
        }
        if ($state->CheDoHienThi !== 'Ban_Be') {
            return false;
        }

        return BanBe::where('TrangThai', 'Dang_La_Ban')->where(fn ($q) => $q->where(fn ($q) => $q->where('idTaiKhoan1', $user->idTaiKhoan)->where('idTaiKhoan2', $state->idTaiKhoan))
            ->orWhere(fn ($q) => $q->where('idTaiKhoan2', $user->idTaiKhoan)->where('idTaiKhoan1', $state->idTaiKhoan))
        )->exists();
    }
}
