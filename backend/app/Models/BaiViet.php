<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BaiViet extends Model
{
    protected $table = 'bai_viets';

    protected $primaryKey = 'idBaiViet';

    public $timestamps = false;

    protected $fillable = ['idTaiKhoan', 'NoiDung', 'HinhAnh', 'idCamXuc', 'CheDoHienThi', 'NgayDang', 'NgayCapNhat', 'TrangThaiBaiViet'];

    protected function casts(): array
    {
        return ['NgayDang' => 'datetime', 'NgayCapNhat' => 'datetime'];
    }

    public function taiKhoan()
    {
        return $this->belongsTo(TaiKhoan::class, 'idTaiKhoan');
    }

    public function camXuc()
    {
        return $this->belongsTo(CamXuc::class, 'idCamXuc');
    }

    public function binhLuans()
    {
        return $this->hasMany(BinhLuan::class, 'idBaiViet');
    }

    public function tuongTacs()
    {
        return $this->hasMany(TuongTacBaiViet::class, 'idBaiViet');
    }
}
