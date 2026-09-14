<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tuong_tac_bai_viets', function (Blueprint $table) {
            $table->id('idTuongTac');
            $table->foreignId('idBaiViet')->constrained('bai_viets', 'idBaiViet')->cascadeOnDelete();
            $table->foreignId('idTaiKhoan')->constrained('tai_khoans', 'idTaiKhoan')->cascadeOnDelete();
            $table->enum('LoaiTuongTac', ['Thich', 'Yeu_Thich', 'Haha', 'Buon', 'Tuc_Gian']);
            $table->timestamp('ThoiGianTuongTac')->useCurrent();
            $table->unique(['idBaiViet', 'idTaiKhoan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tuong_tac_bai_viets');
    }
};
