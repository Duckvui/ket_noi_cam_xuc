<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoiMoiNuoiPet extends Model
{
    protected $table = 'loi_moi_nuoi_pets';

    protected $primaryKey = 'idLoiMoiPet';

    public $timestamps = false;

    protected $fillable = ['idTaiKhoan1', 'idTaiKhoan2', 'idNguoiGui', 'TenPet', 'LoaiPet', 'TrangThai', 'NgayTao', 'NgayPhanHoi'];

    protected function casts(): array
    {
        return ['NgayTao' => 'datetime', 'NgayPhanHoi' => 'datetime'];
    }

    public function nguoiGui()
    {
        return $this->belongsTo(TaiKhoan::class, 'idNguoiGui');
    }

    public function taiKhoan1()
    {
        return $this->belongsTo(TaiKhoan::class, 'idTaiKhoan1');
    }

    public function taiKhoan2()
    {
        return $this->belongsTo(TaiKhoan::class, 'idTaiKhoan2');
    }
}
