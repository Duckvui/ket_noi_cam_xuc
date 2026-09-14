<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('luot_xem_tins', function (Blueprint $table) {
            $table->id('idLuotXem');
            $table->foreignId('idTin')->constrained('tins', 'idTin')->cascadeOnDelete();
            $table->foreignId('idTaiKhoan')->constrained('tai_khoans', 'idTaiKhoan')->cascadeOnDelete();
            $table->timestamp('ThoiGianXem')->useCurrent();
            $table->unique(['idTin', 'idTaiKhoan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('luot_xem_tins');
    }
};
