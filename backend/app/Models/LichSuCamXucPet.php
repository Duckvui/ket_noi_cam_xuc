<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LichSuCamXucPet extends Model
{
    protected $table = 'lich_su_cam_xuc_pets';

    public $timestamps = false;

    protected $fillable = ['idPet', 'idTrangThaiCamXuc', 'DiemThayDoi', 'DiemSau', 'ThoiGianTao'];

    protected function casts(): array
    {
        return ['ThoiGianTao' => 'datetime'];
    }

    public function pet()
    {
        return $this->belongsTo(Pet::class, 'idPet');
    }

    public function trangThaiCamXuc()
    {
        return $this->belongsTo(TrangThaiCamXuc::class, 'idTrangThaiCamXuc');
    }
}
