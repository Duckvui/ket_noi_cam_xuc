<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LuotXemTin extends Model
{
    protected $table = 'luot_xem_tins';

    protected $primaryKey = 'idLuotXem';

    public $timestamps = false;

    protected $fillable = ['idTin', 'idTaiKhoan', 'ThoiGianXem'];

    protected function casts(): array
    {
        return ['ThoiGianXem' => 'datetime'];
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
