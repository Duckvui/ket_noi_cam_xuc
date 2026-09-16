<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pets', function (Blueprint $table) {
            $table->foreignId('idTaiKhoan2')->nullable()->constrained('tai_khoans', 'idTaiKhoan')->cascadeOnDelete();
            $table->unsignedTinyInteger('DiemCamXuc')->default(50);
            $table->timestamp('NgayCapNhat')->nullable();
            $table->unique(['idTaiKhoan', 'idTaiKhoan2'], 'pet_cap_duy_nhat');
        });
        Schema::create('loi_moi_nuoi_pets', function (Blueprint $table) {
            $table->id('idLoiMoiPet');
            $table->foreignId('idTaiKhoan1')->constrained('tai_khoans', 'idTaiKhoan')->cascadeOnDelete();
            $table->foreignId('idTaiKhoan2')->constrained('tai_khoans', 'idTaiKhoan')->cascadeOnDelete();
            $table->foreignId('idNguoiGui')->constrained('tai_khoans', 'idTaiKhoan')->cascadeOnDelete();
            $table->string('TenPet', 50);
            $table->string('LoaiPet', 30);
            $table->string('TrangThai', 30)->default('Cho_Xac_Nhan');
            $table->timestamp('NgayTao')->useCurrent();
            $table->timestamp('NgayPhanHoi')->nullable();
            $table->unique(['idTaiKhoan1', 'idTaiKhoan2'], 'loi_moi_pet_cap_duy_nhat');
        });
        Schema::table('tuong_tac_pets', function (Blueprint $table) {
            $table->string('MaHanhDong', 30)->nullable();
            $table->smallInteger('DiemThayDoi')->nullable();
            $table->unsignedTinyInteger('DiemSau')->nullable();
            $table->index(['idPet', 'idNguoiGui', 'ThoiGianTuongTac'], 'pet_cooldown_index');
        });
        Schema::create('lich_su_cam_xuc_pets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('idPet')->constrained('pets', 'idPet')->cascadeOnDelete();
            $table->foreignId('idTrangThaiCamXuc')->constrained('trang_thai_cam_xucs', 'idTrangThaiCamXuc')->cascadeOnDelete();
            $table->smallInteger('DiemThayDoi');
            $table->unsignedTinyInteger('DiemSau');
            $table->timestamp('ThoiGianTao')->useCurrent();
            $table->unique(['idPet', 'idTrangThaiCamXuc']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lich_su_cam_xuc_pets');
        Schema::dropIfExists('loi_moi_nuoi_pets');
        Schema::table('tuong_tac_pets', function (Blueprint $table) {
            $table->dropIndex('pet_cooldown_index');
            $table->dropColumn(['MaHanhDong', 'DiemThayDoi', 'DiemSau']);
        });
        Schema::table('pets', function (Blueprint $table) {
            $table->dropUnique('pet_cap_duy_nhat');
            $table->dropConstrainedForeignId('idTaiKhoan2');
            $table->dropColumn(['DiemCamXuc', 'NgayCapNhat']);
        });
    }
};
