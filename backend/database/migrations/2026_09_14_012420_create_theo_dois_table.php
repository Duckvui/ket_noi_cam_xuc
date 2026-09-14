<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('theo_dois', function (Blueprint $table) {
            $table->id('idTheoDoi');
            $table->foreignId('idTaiKhoan')->constrained('tai_khoans', 'idTaiKhoan')->cascadeOnDelete();
            $table->foreignId('idTaiKhoanTheoDoi')->constrained('tai_khoans', 'idTaiKhoan')->cascadeOnDelete();
            $table->boolean('TrangThai')->default(true);
            $table->timestamp('NgayTao')->useCurrent();
            $table->unique(['idTaiKhoan', 'idTaiKhoanTheoDoi']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('theo_dois');
    }
};
