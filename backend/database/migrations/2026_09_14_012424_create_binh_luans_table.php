<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('binh_luans', function (Blueprint $table) {
            $table->id('idBinhLuan');
            $table->foreignId('idBaiViet')->constrained('bai_viets', 'idBaiViet')->cascadeOnDelete();
            $table->foreignId('idTaiKhoan')->constrained('tai_khoans', 'idTaiKhoan')->cascadeOnDelete();
            $table->text('NoiDung');
            $table->timestamp('ThoiGianBinhLuan')->useCurrent();
            $table->foreignId('idBinhLuanCha')->nullable()->constrained('binh_luans', 'idBinhLuan')->nullOnDelete();
            $table->enum('TrangThai', ['Binh_Thuong', 'Da_An', 'Da_Xoa'])->default('Binh_Thuong');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('binh_luans');
    }
};
