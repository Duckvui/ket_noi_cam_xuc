<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gui_ket_bans', function (Blueprint $table) {
            $table->id('idKetBan');
            $table->foreignId('idGuiKetBan')->constrained('tai_khoans', 'idTaiKhoan')->cascadeOnDelete();
            $table->foreignId('idDuocKetBan')->constrained('tai_khoans', 'idTaiKhoan')->cascadeOnDelete();
            $table->enum('TrangThaiLoiMoi', ['Cho_Xac_Nhan', 'Da_Chap_Nhan', 'Da_Tu_Choi'])->default('Cho_Xac_Nhan');
            $table->timestamp('NgayTao')->useCurrent();
            $table->unique(['idGuiKetBan', 'idDuocKetBan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gui_ket_bans');
    }
};
