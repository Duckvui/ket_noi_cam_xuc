<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('avatar_cam_xucs', function (Blueprint $table) {
            $table->id('idAvatarCamXuc');
            $table->foreignId('idTaiKhoan')->constrained('tai_khoans', 'idTaiKhoan')->cascadeOnDelete();
            $table->foreignId('idCamXuc')->constrained('cam_xucs', 'idCamXuc')->restrictOnDelete();
            $table->string('AnhAvatarCamXuc');
            $table->timestamp('NgayTao')->useCurrent();
            $table->enum('TrangThai', ['Dang_Su_Dung', 'Khong_Su_Dung'])->default('Dang_Su_Dung');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avatar_cam_xucs');
    }
};
