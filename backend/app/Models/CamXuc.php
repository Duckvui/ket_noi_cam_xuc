<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CamXuc extends Model
{
    protected $table = 'cam_xucs';

    protected $primaryKey = 'idCamXuc';

    public $timestamps = false;

    protected $fillable = ['TenCamXuc', 'BieuTuong', 'MoTa', 'TrangThai'];

    public function baiViets()
    {
        return $this->hasMany(BaiViet::class, 'idCamXuc');
    }

    public function trangThaiCamXucs()
    {
        return $this->hasMany(TrangThaiCamXuc::class, 'idCamXuc');
    }
}
