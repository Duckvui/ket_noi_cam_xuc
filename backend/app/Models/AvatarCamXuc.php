<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AvatarCamXuc extends Model
{
    protected $table = 'avatar_cam_xucs';

    protected $primaryKey = 'idAvatarCamXuc';

    public $timestamps = false;

    protected $fillable = ['idTaiKhoan', 'idCamXuc', 'AnhAvatarCamXuc', 'NgayTao', 'TrangThai'];

    protected function casts(): array
    {
        return ['NgayTao' => 'datetime'];
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
