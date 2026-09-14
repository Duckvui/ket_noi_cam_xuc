<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bai_viets', function (Blueprint $table) {
            $table->id('idBaiViet');
            $table->foreignId('idTaiKhoan')->constrained('tai_khoans', 'idTaiKhoan')->cascadeOnDelete();
            $table->text('NoiDung')->nullable();
            $table->string('HinhAnh')->nullable();
            $table->foreignId('idCamXuc')->nullable()->constrained('cam_xucs', 'idCamXuc')->nullOnDelete();
            $table->enum('CheDoHienThi', ['Cong_Khai', 'Ban_Be', 'Chi_Minh_Toi'])->default('Cong_Khai');
            $table->timestamp('NgayDang')->useCurrent();
            $table->timestamp('NgayCapNhat')->useCurrent()->useCurrentOnUpdate();
            $table->enum('TrangThaiBaiViet', ['Binh_Thuong', 'Da_An', 'Da_Xoa'])->default('Binh_Thuong');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bai_viets');
    }
};
