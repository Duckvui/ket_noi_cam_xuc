<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ThongTinCaNhan extends Model
{
    protected $table = 'thong_tin_ca_nhans';

    protected $primaryKey = 'idTTCN';

    public $timestamps = false;

    protected $fillable = ['idTaiKhoan', 'Ho', 'Ten', 'NamSinh', 'GioiThieu', 'NgayCapNhat'];

    protected function casts(): array
    {
        return ['NamSinh' => 'date', 'NgayCapNhat' => 'datetime'];
    }

    public function taiKhoan()
    {
        return $this->belongsTo(TaiKhoan::class, 'idTaiKhoan');
    }
}
