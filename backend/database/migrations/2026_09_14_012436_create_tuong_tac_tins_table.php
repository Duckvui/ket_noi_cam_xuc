<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tuong_tac_tins', function (Blueprint $table) {
            $table->id('idTuongTacTin');
            $table->foreignId('idTin')->constrained('tins', 'idTin')->cascadeOnDelete();
            $table->foreignId('idTaiKhoan')->constrained('tai_khoans', 'idTaiKhoan')->cascadeOnDelete();
            $table->string('LoaiTuongTac');
            $table->text('NoiDung')->nullable();
            $table->timestamp('ThoiGianTuongTac')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tuong_tac_tins');
    }
};
