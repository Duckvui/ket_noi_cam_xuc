<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pet extends Model
{
    protected $table = 'pets';

    protected $primaryKey = 'idPet';

    public $timestamps = false;

    protected $fillable = ['idTaiKhoan', 'TenPet', 'LoaiPet', 'AnhPet', 'idCamXucHienTai', 'TrangThaiPet', 'CapDo', 'KinhNghiem', 'NgayTao'];

    protected function casts(): array
    {
        return ['NgayTao' => 'datetime'];
    }

    public function taiKhoan()
    {
        return $this->belongsTo(TaiKhoan::class, 'idTaiKhoan');
    }

    public function camXucHienTai()
    {
        return $this->belongsTo(CamXuc::class, 'idCamXucHienTai');
    }

    public function tuongTacs()
    {
        return $this->hasMany(TuongTacPet::class, 'idPet');
    }
}
