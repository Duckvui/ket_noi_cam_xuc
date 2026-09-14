<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TuongTacBaiViet extends Model
{
    protected $table = 'tuong_tac_bai_viets';

    protected $primaryKey = 'idTuongTac';

    public $timestamps = false;

    protected $fillable = ['idBaiViet', 'idTaiKhoan', 'LoaiTuongTac', 'ThoiGianTuongTac'];

    protected function casts(): array
    {
        return ['ThoiGianTuongTac' => 'datetime'];
    }

    public function baiViet()
    {
        return $this->belongsTo(BaiViet::class, 'idBaiViet');
    }

    public function taiKhoan()
    {
        return $this->belongsTo(TaiKhoan::class, 'idTaiKhoan');
    }
}
