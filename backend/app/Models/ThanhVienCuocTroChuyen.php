<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ThanhVienCuocTroChuyen extends Model
{
    protected $table = 'thanh_vien_cuoc_tro_chuyens';

    protected $primaryKey = 'idThanhVien';

    public $timestamps = false;

    protected $fillable = ['idCuocTroChuyen', 'idTaiKhoan', 'NgayThamGia', 'TrangThai'];

    protected function casts(): array
    {
        return ['NgayThamGia' => 'datetime'];
    }

    public function cuocTroChuyen()
    {
        return $this->belongsTo(CuocTroChuyen::class, 'idCuocTroChuyen');
    }

    public function taiKhoan()
    {
        return $this->belongsTo(TaiKhoan::class, 'idTaiKhoan');
    }
}
