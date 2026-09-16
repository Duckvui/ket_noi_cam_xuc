<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pets', function (Blueprint $table) {
            $table->unsignedInteger('current_streak')->default(0);
            $table->unsignedInteger('longest_streak')->default(0);
            $table->date('last_activity_date')->nullable();
        });
        Schema::table('tuong_tac_pets', function (Blueprint $table) {
            $table->string('CamXucTruoc', 30)->nullable();
            $table->string('CamXucSau', 30)->nullable();
        });
        Schema::table('tin_nhans', fn (Blueprint $table) => $table->string('DuongDanTep')->nullable());
        Schema::create('chan_nguoi_dungs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('idNguoiChan')->constrained('tai_khoans', 'idTaiKhoan')->cascadeOnDelete();
            $table->foreignId('idBiChan')->constrained('tai_khoans', 'idTaiKhoan')->cascadeOnDelete();
            $table->timestamp('NgayTao')->useCurrent();
            $table->unique(['idNguoiChan', 'idBiChan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chan_nguoi_dungs');
        Schema::table('tin_nhans', fn (Blueprint $table) => $table->dropColumn('DuongDanTep'));
        Schema::table('tuong_tac_pets', fn (Blueprint $table) => $table->dropColumn(['CamXucTruoc', 'CamXucSau']));
        Schema::table('pets', fn (Blueprint $table) => $table->dropColumn(['current_streak', 'longest_streak', 'last_activity_date']));
    }
};
