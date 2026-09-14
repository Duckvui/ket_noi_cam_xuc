<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrangThaiCamXuc extends Model
{
    protected $table = 'trang_thai_cam_xucs';

    protected $primaryKey = 'idTrangThaiCamXuc';

    public $timestamps = false;

    protected $fillable = ['idTaiKhoan', 'idCamXuc', 'NoiDung', 'MucDoCamXuc', 'ThoiGianTao', 'TrangThai'];

    protected function casts(): array
    {
        return ['ThoiGianTao' => 'datetime'];
    }

    public function taiKhoan()
    {
        return $this->belongsTo(TaiKhoan::class, 'idTaiKhoan');
    }

    public function camXuc()
    {
        return $this->belongsTo(CamXuc::class, 'idCamXuc');
    }
}
