<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pets', function (Blueprint $table) {
            $table->id('idPet');
            $table->foreignId('idTaiKhoan')->constrained('tai_khoans', 'idTaiKhoan')->cascadeOnDelete();
            $table->string('TenPet');
            $table->string('LoaiPet');
            $table->string('AnhPet')->nullable();
            $table->foreignId('idCamXucHienTai')->nullable()->constrained('cam_xucs', 'idCamXuc')->nullOnDelete();
            $table->string('TrangThaiPet')->nullable();
            $table->unsignedInteger('CapDo')->default(1);
            $table->unsignedInteger('KinhNghiem')->default(0);
            $table->timestamp('NgayTao')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pets');
    }
};
