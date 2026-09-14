<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GuiKetBan extends Model
{
    protected $table = 'gui_ket_bans';

    protected $primaryKey = 'idKetBan';

    public $timestamps = false;

    protected $fillable = ['idGuiKetBan', 'idDuocKetBan', 'TrangThaiLoiMoi', 'NgayTao'];

    protected function casts(): array
    {
        return ['NgayTao' => 'datetime'];
    }

    public function nguoiGui()
    {
        return $this->belongsTo(TaiKhoan::class, 'idGuiKetBan');
    }

    public function nguoiNhan()
    {
        return $this->belongsTo(TaiKhoan::class, 'idDuocKetBan');
    }
}
