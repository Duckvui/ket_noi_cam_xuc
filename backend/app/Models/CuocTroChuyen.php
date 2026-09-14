<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CuocTroChuyen extends Model
{
    protected $table = 'cuoc_tro_chuyens';

    protected $primaryKey = 'idCuocTroChuyen';

    public $timestamps = false;

    protected $fillable = ['LoaiCuocTroChuyen', 'NgayTao', 'NgayCapNhat', 'TrangThai'];

    protected function casts(): array
    {
        return ['NgayTao' => 'datetime', 'NgayCapNhat' => 'datetime'];
    }

    public function thanhViens()
    {
        return $this->hasMany(ThanhVienCuocTroChuyen::class, 'idCuocTroChuyen');
    }

    public function tinNhans()
    {
        return $this->hasMany(TinNhan::class, 'idCuocTroChuyen');
    }
}
