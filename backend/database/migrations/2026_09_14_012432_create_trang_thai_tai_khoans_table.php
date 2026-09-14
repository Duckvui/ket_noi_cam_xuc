<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trang_thai_tai_khoans', function (Blueprint $table) {
            $table->id('idTTTaiKhoan');
            $table->foreignId('idTaiKhoan')->unique()->constrained('tai_khoans', 'idTaiKhoan')->cascadeOnDelete();
            $table->unsignedBigInteger('idDsLoiMoi')->nullable();
            $table->unsignedBigInteger('idDsTheoDoi')->nullable();
            $table->string('TrangThaiTaiKhoan')->default('Hoat_Dong');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trang_thai_tai_khoans');
    }
};
