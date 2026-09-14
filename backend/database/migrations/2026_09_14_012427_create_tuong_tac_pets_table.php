<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tuong_tac_pets', function (Blueprint $table) {
            $table->id('idTuongTacPet');
            $table->foreignId('idNguoiGui')->constrained('tai_khoans', 'idTaiKhoan')->cascadeOnDelete();
            $table->foreignId('idNguoiNhan')->constrained('tai_khoans', 'idTaiKhoan')->cascadeOnDelete();
            $table->foreignId('idPet')->constrained('pets', 'idPet')->cascadeOnDelete();
            $table->enum('LoaiTuongTac', ['Om', 'Gui_Tim', 'Do_Danh', 'Cho_An', 'Choi_Cung']);
            $table->text('NoiDung')->nullable();
            $table->timestamp('ThoiGianTuongTac')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tuong_tac_pets');
    }
};
