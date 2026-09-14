<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cam_xucs', function (Blueprint $table) {
            $table->id('idCamXuc');
            $table->string('TenCamXuc')->unique();
            $table->string('BieuTuong')->nullable();
            $table->string('MoTa')->nullable();
            $table->enum('TrangThai', ['Dang_Su_Dung', 'Ngung_Su_Dung'])->default('Dang_Su_Dung');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cam_xucs');
    }
};
