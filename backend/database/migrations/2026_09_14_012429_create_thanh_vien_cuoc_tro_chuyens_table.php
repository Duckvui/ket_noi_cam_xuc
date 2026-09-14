<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('thanh_vien_cuoc_tro_chuyens', function (Blueprint $table) {
            $table->id('idThanhVien');
            $table->foreignId('idCuocTroChuyen')->constrained('cuoc_tro_chuyens', 'idCuocTroChuyen')->cascadeOnDelete();
            $table->foreignId('idTaiKhoan')->constrained('tai_khoans', 'idTaiKhoan')->cascadeOnDelete();
            $table->timestamp('NgayThamGia')->useCurrent();
            $table->enum('TrangThai', ['Dang_Tham_Gia', 'Da_Roi'])->default('Dang_Tham_Gia');
            $table->unique(['idCuocTroChuyen', 'idTaiKhoan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('thanh_vien_cuoc_tro_chuyens');
    }
};
