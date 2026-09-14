<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tins', function (Blueprint $table) {
            $table->id('idTin');
            $table->foreignId('idTaiKhoan')->constrained('tai_khoans', 'idTaiKhoan')->cascadeOnDelete();
            $table->text('NoiDung')->nullable();
            $table->enum('LoaiTin', ['ANH', 'VIDEO', 'VAN_BAN']);
            $table->string('DuongDanMedia')->nullable();
            $table->enum('CheDoHienThi', ['Cong_Khai', 'Ban_Be', 'Chi_Minh_Toi'])->default('Cong_Khai');
            $table->timestamp('ThoiGianDang')->useCurrent();
            $table->timestamp('ThoiGianHetHan')->index();
            $table->enum('TrangThai', ['Binh_Thuong', 'Da_An', 'Da_Xoa'])->default('Binh_Thuong');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tins');
    }
};
