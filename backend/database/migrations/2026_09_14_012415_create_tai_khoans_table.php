<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tai_khoans', function (Blueprint $table) {
            $table->id('idTaiKhoan');
            $table->string('TaiKhoan')->unique();
            $table->string('MatKhau');
            $table->enum('LoaiTaiKhoan', ['Admin', 'NguoiDung'])->default('NguoiDung');
            $table->enum('TrangThaiTaiKhoan', ['Hoat_Dong', 'Tam_Khoa', 'Bi_Khoa'])->default('Hoat_Dong');
            $table->timestamp('NgayTao')->useCurrent();
            $table->timestamp('NgayCapNhat')->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tai_khoans');
    }
};
