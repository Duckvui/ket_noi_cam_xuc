<?php

namespace App\Services;

class QuyDoiDiemPet
{
    public function trangThai(int $diem): string
    {
        return match (true) {
            $diem <= 20 => 'rat_buon', $diem <= 40 => 'buon', $diem <= 60 => 'binh_thuong',
            $diem <= 80 => 'vui', default => 'rat_vui',
        };
    }

    public function gioiHan(int $diem): int
    {
        return max(0, min(100, $diem));
    }

    public function diemCamXuc(string $ten): int
    {
        return config('cam_xuc.cam_xucs.'.$ten.'.diem', 0);
    }
}
