<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('thong_baos', function (Blueprint $table) {
            $table->id('idThongBao');
            $table->foreignId('idTaiKhoanNhan')->constrained('tai_khoans', 'idTaiKhoan')->cascadeOnDelete();
            $table->foreignId('idTaiKhoanGui')->nullable()->constrained('tai_khoans', 'idTaiKhoan')->nullOnDelete();
            $table->enum('LoaiThongBao', ['Ket_Ban', 'Binh_Luan', 'Tuong_Tac', 'Tin_Nhan', 'Cam_Xuc', 'Pet']);
            $table->text('NoiDung');
            $table->unsignedBigInteger('idDoiTuong')->nullable();
            $table->boolean('DaDoc')->default(false);
            $table->timestamp('ThoiGianTao')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('thong_baos');
    }
};
