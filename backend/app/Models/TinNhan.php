<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TinNhan extends Model
{
    protected $table = 'tin_nhans';

    protected $primaryKey = 'idTinNhan';

    public $timestamps = false;

    protected $fillable = ['idCuocTroChuyen', 'idNguoiGui', 'idTin', 'NoiDung', 'LoaiTinNhan', 'DuongDanTep', 'ThoiGianGui', 'TrangThaiTinNhan', 'NgayXoa'];

    protected function casts(): array
    {
        return ['ThoiGianGui' => 'datetime', 'NgayXoa' => 'datetime'];
    }

    public function cuocTroChuyen()
    {
        return $this->belongsTo(CuocTroChuyen::class, 'idCuocTroChuyen');
    }

    public function nguoiGui()
    {
        return $this->belongsTo(TaiKhoan::class, 'idNguoiGui');
    }

    public function tin()
    {
        return $this->belongsTo(Tin::class, 'idTin');
    }
}
