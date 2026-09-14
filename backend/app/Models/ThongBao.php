<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ThongBao extends Model
{
    protected $table = 'thong_baos';

    protected $primaryKey = 'idThongBao';

    public $timestamps = false;

    protected $fillable = ['idTaiKhoanNhan', 'idTaiKhoanGui', 'LoaiThongBao', 'NoiDung', 'idDoiTuong', 'DaDoc', 'ThoiGianTao'];

    protected function casts(): array
    {
        return ['DaDoc' => 'boolean', 'ThoiGianTao' => 'datetime'];
    }

    public function taiKhoanNhan()
    {
        return $this->belongsTo(TaiKhoan::class, 'idTaiKhoanNhan');
    }

    public function taiKhoanGui()
    {
        return $this->belongsTo(TaiKhoan::class, 'idTaiKhoanGui');
    }
}
