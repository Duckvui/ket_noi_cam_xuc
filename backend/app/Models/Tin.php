<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tin extends Model
{
    protected $table = 'tins';

    protected $primaryKey = 'idTin';

    public $timestamps = false;

    protected $fillable = ['idTaiKhoan', 'NoiDung', 'LoaiTin', 'MauNen', 'DuongDanMedia', 'CheDoHienThi', 'ThoiGianDang', 'ThoiGianHetHan', 'TrangThai'];

    protected function casts(): array
    {
        return ['ThoiGianDang' => 'datetime', 'ThoiGianHetHan' => 'datetime'];
    }

    public function taiKhoan()
    {
        return $this->belongsTo(TaiKhoan::class, 'idTaiKhoan');
    }

    public function luotXems()
    {
        return $this->hasMany(LuotXemTin::class, 'idTin');
    }

    public function tuongTacs()
    {
        return $this->hasMany(TuongTacTin::class, 'idTin');
    }

    public function tinNhans()
    {
        return $this->hasMany(TinNhan::class, 'idTin');
    }
}
