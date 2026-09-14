<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('avatars', function (Blueprint $table) {
            $table->id('idAvatar');
            $table->foreignId('idTaiKhoan')->constrained('tai_khoans', 'idTaiKhoan')->cascadeOnDelete();
            $table->string('AnhAvatar')->nullable();
            $table->string('AnhBia')->nullable();
            $table->timestamp('NgayCapNhatAvatar')->nullable();
            $table->timestamp('NgayCapNhatAnhBia')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avatars');
    }
};
