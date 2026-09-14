<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TuongTacTin extends Model
{
    protected $table = 'tuong_tac_tins';

    protected $primaryKey = 'idTuongTacTin';

    public $timestamps = false;

    protected $fillable = ['idTin', 'idTaiKhoan', 'LoaiTuongTac', 'NoiDung', 'ThoiGianTuongTac'];

    protected function casts(): array
    {
        return ['ThoiGianTuongTac' => 'datetime'];
    }

    public function tin()
    {
        return $this->belongsTo(Tin::class, 'idTin');
    }

    public function taiKhoan()
    {
        return $this->belongsTo(TaiKhoan::class, 'idTaiKhoan');
    }
}
