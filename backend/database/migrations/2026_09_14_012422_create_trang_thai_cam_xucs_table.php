<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trang_thai_cam_xucs', function (Blueprint $table) {
            $table->id('idTrangThaiCamXuc');
            $table->foreignId('idTaiKhoan')->constrained('tai_khoans', 'idTaiKhoan')->cascadeOnDelete();
            $table->foreignId('idCamXuc')->constrained('cam_xucs', 'idCamXuc')->restrictOnDelete();
            $table->text('NoiDung')->nullable();
            $table->unsignedTinyInteger('MucDoCamXuc')->nullable();
            $table->timestamp('ThoiGianTao')->useCurrent();
            $table->enum('TrangThai', ['Hien_Tai', 'Da_Cu', 'Da_An'])->default('Hien_Tai');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trang_thai_cam_xucs');
    }
};
