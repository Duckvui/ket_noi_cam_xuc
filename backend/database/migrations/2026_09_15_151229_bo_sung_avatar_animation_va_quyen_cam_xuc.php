<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('avatars', function (Blueprint $table) {
            $table->string('MaAvatarAnimation', 30)->nullable();
        });
        Schema::table('trang_thai_cam_xucs', function (Blueprint $table) {
            $table->string('CheDoHienThi', 20)->default('Chi_Minh_Toi');
            $table->index(['idTaiKhoan', 'TrangThai', 'idTrangThaiCamXuc'], 'cam_xuc_hien_tai_index');
        });
        foreach (['Vui' => '😊', 'Buon' => '😢', 'Met_Moi' => '😴', 'Tuc_Gian' => '😠', 'Lo_Lang' => '😟', 'Binh_Thuong' => '🙂'] as $ten => $icon) {
            if (! DB::table('cam_xucs')->where('TenCamXuc', $ten)->exists()) {
                DB::table('cam_xucs')->insert(['TenCamXuc' => $ten, 'BieuTuong' => $icon, 'TrangThai' => 'Dang_Su_Dung']);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('avatars', fn (Blueprint $table) => $table->dropColumn('MaAvatarAnimation'));
        Schema::table('trang_thai_cam_xucs', function (Blueprint $table) {
            $table->dropIndex('cam_xuc_hien_tai_index');
            $table->dropColumn('CheDoHienThi');
        });
        // Keep emotion catalog entries: existing posts may reference them.
    }
};
