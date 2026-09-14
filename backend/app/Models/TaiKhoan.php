<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class TaiKhoan extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'tai_khoans';

    protected $primaryKey = 'idTaiKhoan';

    public $timestamps = false;

    protected $fillable = ['TaiKhoan', 'MatKhau', 'LoaiTaiKhoan', 'TrangThaiTaiKhoan', 'NgayTao', 'NgayCapNhat'];

    protected $hidden = ['MatKhau'];

    protected function casts(): array
    {
        return ['NgayTao' => 'datetime', 'NgayCapNhat' => 'datetime'];
    }

    public function thongTinCaNhan()
    {
        return $this->hasOne(ThongTinCaNhan::class, 'idTaiKhoan');
    }

    public function avatars()
    {
        return $this->hasMany(Avatar::class, 'idTaiKhoan');
    }

    public function baiViets()
    {
        return $this->hasMany(BaiViet::class, 'idTaiKhoan');
    }

    public function pets()
    {
        return $this->hasMany(Pet::class, 'idTaiKhoan');
    }

    public function tins()
    {
        return $this->hasMany(Tin::class, 'idTaiKhoan');
    }

    public function luotXemTins()
    {
        return $this->hasMany(LuotXemTin::class, 'idTaiKhoan');
    }

    public function tuongTacTins()
    {
        return $this->hasMany(TuongTacTin::class, 'idTaiKhoan');
    }

    public function trangThaiCamXucs()
    {
        return $this->hasMany(TrangThaiCamXuc::class, 'idTaiKhoan');
    }
}
