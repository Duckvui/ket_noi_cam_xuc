<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TuongTacPet extends Model
{
    protected $table = 'tuong_tac_pets';

    protected $primaryKey = 'idTuongTacPet';

    public $timestamps = false;

    protected $fillable = ['idNguoiGui', 'idNguoiNhan', 'idPet', 'LoaiTuongTac', 'NoiDung', 'ThoiGianTuongTac', 'MaHanhDong', 'DiemThayDoi', 'DiemSau', 'CamXucTruoc', 'CamXucSau'];

    protected function casts(): array
    {
        return ['ThoiGianTuongTac' => 'datetime'];
    }

    public function nguoiGui()
    {
        return $this->belongsTo(TaiKhoan::class, 'idNguoiGui');
    }

    public function nguoiNhan()
    {
        return $this->belongsTo(TaiKhoan::class, 'idNguoiNhan');
    }

    public function pet()
    {
        return $this->belongsTo(Pet::class, 'idPet');
    }
}
