<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('thong_tin_ca_nhans', function (Blueprint $table) {
            $table->id('idTTCN');
            $table->foreignId('idTaiKhoan')->unique()->constrained('tai_khoans', 'idTaiKhoan')->cascadeOnDelete();
            $table->string('Ho')->nullable();
            $table->string('Ten')->nullable();
            $table->date('NamSinh')->nullable();
            $table->text('GioiThieu')->nullable();
            $table->timestamp('NgayCapNhat')->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('thong_tin_ca_nhans');
    }
};
