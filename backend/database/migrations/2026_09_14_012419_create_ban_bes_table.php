<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ban_bes', function (Blueprint $table) {
            $table->id('idBanBe');
            $table->foreignId('idTaiKhoan1')->constrained('tai_khoans', 'idTaiKhoan')->cascadeOnDelete();
            $table->foreignId('idTaiKhoan2')->constrained('tai_khoans', 'idTaiKhoan')->cascadeOnDelete();
            $table->enum('TrangThai', ['Dang_La_Ban', 'Da_Huy'])->default('Dang_La_Ban');
            $table->timestamp('NgayTao')->useCurrent();
            $table->unique(['idTaiKhoan1', 'idTaiKhoan2']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ban_bes');
    }
};
