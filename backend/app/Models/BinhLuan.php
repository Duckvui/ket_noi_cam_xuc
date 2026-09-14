<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BinhLuan extends Model
{
    protected $table = 'binh_luans';

    protected $primaryKey = 'idBinhLuan';

    public $timestamps = false;

    protected $fillable = ['idBaiViet', 'idTaiKhoan', 'NoiDung', 'ThoiGianBinhLuan', 'idBinhLuanCha', 'TrangThai'];

    protected function casts(): array
    {
        return ['ThoiGianBinhLuan' => 'datetime'];
    }

    public function baiViet()
    {
        return $this->belongsTo(BaiViet::class, 'idBaiViet');
    }

    public function taiKhoan()
    {
        return $this->belongsTo(TaiKhoan::class, 'idTaiKhoan');
    }

    public function binhLuanCha()
    {
        return $this->belongsTo(self::class, 'idBinhLuanCha');
    }

    public function phanHois()
    {
        return $this->hasMany(self::class, 'idBinhLuanCha');
    }
}
