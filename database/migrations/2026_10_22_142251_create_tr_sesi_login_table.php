<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tr_sesi_login', function (Blueprint $table) {
            $table->increments('id_sesi');
            $table->string('nama_user', 30);
            $table->unsignedInteger('id_shift')->nullable();
            $table->timestamp('waktu_login')->useCurrent();
            $table->timestamp('waktu_logout')->nullable();

            $table->index('nama_user');
            $table->index('id_shift');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tr_sesi_login');
    }
};
