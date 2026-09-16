<?php

namespace App\Policies;

use App\Models\Pet;
use App\Models\TaiKhoan;

class PetPolicy
{
    public function view(TaiKhoan $user, Pet $pet): bool
    {
        return $pet->idTaiKhoan2 !== null && $pet->idTaiKhoan !== $pet->idTaiKhoan2
            && in_array($user->idTaiKhoan, [$pet->idTaiKhoan, $pet->idTaiKhoan2], true);
    }

    public function update(TaiKhoan $user, Pet $pet): bool
    {
        return $user->TrangThaiTaiKhoan === 'Hoat_Dong' && $this->view($user, $pet);
    }
}
