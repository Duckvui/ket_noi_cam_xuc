<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pet extends Model
{
    protected $table = 'pets';

    protected $primaryKey = 'idPet';

    public $timestamps = false;

    protected $fillable = ['idTaiKhoan', 'idTaiKhoan2', 'DiemCamXuc', 'NgayCapNhat', 'TenPet', 'LoaiPet', 'AnhPet', 'idCamXucHienTai', 'TrangThaiPet', 'CapDo', 'KinhNghiem', 'NgayTao'];

    protected function casts(): array
    {
        return ['NgayTao' => 'datetime', 'NgayCapNhat' => 'datetime', 'DiemCamXuc' => 'integer', 'last_activity_date' => 'date', 'current_streak' => 'integer', 'longest_streak' => 'integer'];
    }

    public function taiKhoan()
    {
        return $this->belongsTo(TaiKhoan::class, 'idTaiKhoan');
    }

    public function camXucHienTai()
    {
        return $this->belongsTo(CamXuc::class, 'idCamXucHienTai');
    }

    public function taiKhoan2()
    {
        return $this->belongsTo(TaiKhoan::class, 'idTaiKhoan2');
    }

    public function tuongTacs()
    {
        return $this->hasMany(TuongTacPet::class, 'idPet');
    }

    public function lichSuCamXucs()
    {
        return $this->hasMany(LichSuCamXucPet::class, 'idPet');
    }
}
