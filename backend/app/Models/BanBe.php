<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BanBe extends Model
{
    protected $table = 'ban_bes';

    protected $primaryKey = 'idBanBe';

    public $timestamps = false;

    protected $fillable = ['idTaiKhoan1', 'idTaiKhoan2', 'TrangThai', 'NgayTao'];

    protected function casts(): array
    {
        return ['NgayTao' => 'datetime'];
    }

    public function taiKhoan1()
    {
        return $this->belongsTo(TaiKhoan::class, 'idTaiKhoan1');
    }

    public function taiKhoan2()
    {
        return $this->belongsTo(TaiKhoan::class, 'idTaiKhoan2');
    }
}
