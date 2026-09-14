<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TheoDoi extends Model
{
    protected $table = 'theo_dois';

    protected $primaryKey = 'idTheoDoi';

    public $timestamps = false;

    protected $fillable = ['idTaiKhoan', 'idTaiKhoanTheoDoi', 'TrangThai', 'NgayTao'];

    protected function casts(): array
    {
        return ['TrangThai' => 'boolean', 'NgayTao' => 'datetime'];
    }

    public function nguoiTheoDoi()
    {
        return $this->belongsTo(TaiKhoan::class, 'idTaiKhoan');
    }

    public function dangTheoDoi()
    {
        return $this->belongsTo(TaiKhoan::class, 'idTaiKhoanTheoDoi');
    }
}
