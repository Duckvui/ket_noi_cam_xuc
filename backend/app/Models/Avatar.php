<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Avatar extends Model
{
    protected $table = 'avatars';

    protected $primaryKey = 'idAvatar';

    public $timestamps = false;

    protected $fillable = ['idTaiKhoan', 'AnhAvatar', 'AnhBia', 'NgayCapNhatAvatar', 'NgayCapNhatAnhBia'];

    protected function casts(): array
    {
        return ['NgayCapNhatAvatar' => 'datetime', 'NgayCapNhatAnhBia' => 'datetime'];
    }

    public function taiKhoan()
    {
        return $this->belongsTo(TaiKhoan::class, 'idTaiKhoan');
    }
}
