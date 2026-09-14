<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrangThaiTaiKhoan extends Model
{
    protected $table = 'trang_thai_tai_khoans';

    protected $primaryKey = 'idTTTaiKhoan';

    public $timestamps = false;

    protected $fillable = ['idTaiKhoan', 'idDsLoiMoi', 'idDsTheoDoi', 'TrangThaiTaiKhoan'];

    public function taiKhoan()
    {
        return $this->belongsTo(TaiKhoan::class, 'idTaiKhoan');
    }
}
