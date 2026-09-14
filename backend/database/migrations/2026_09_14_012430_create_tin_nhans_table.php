<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tin_nhans', function (Blueprint $table) {
            $table->id('idTinNhan');
            $table->foreignId('idCuocTroChuyen')->constrained('cuoc_tro_chuyens', 'idCuocTroChuyen')->cascadeOnDelete();
            $table->foreignId('idNguoiGui')->constrained('tai_khoans', 'idTaiKhoan')->cascadeOnDelete();
            $table->text('NoiDung')->nullable();
            $table->enum('LoaiTinNhan', ['Van_Ban', 'Anh', 'Sticker', 'Pet'])->default('Van_Ban');
            $table->timestamp('ThoiGianGui')->useCurrent();
            $table->enum('TrangThaiTinNhan', ['Da_Gui', 'Da_Nhan', 'Da_Xem'])->default('Da_Gui');
            $table->timestamp('NgayXoa')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tin_nhans');
    }
};
