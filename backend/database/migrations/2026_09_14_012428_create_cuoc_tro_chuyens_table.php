<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cuoc_tro_chuyens', function (Blueprint $table) {
            $table->id('idCuocTroChuyen');
            $table->enum('LoaiCuocTroChuyen', ['Ca_Nhan', 'Nhom']);
            $table->timestamp('NgayTao')->useCurrent();
            $table->timestamp('NgayCapNhat')->useCurrent()->useCurrentOnUpdate();
            $table->enum('TrangThai', ['Dang_Hoat_Dong', 'Da_Ket_Thuc'])->default('Dang_Hoat_Dong');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cuoc_tro_chuyens');
    }
};
