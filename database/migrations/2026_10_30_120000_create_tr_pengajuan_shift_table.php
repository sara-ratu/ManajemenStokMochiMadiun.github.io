<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tr_pengajuan_shift', function (Blueprint $table): void {
            $table->bigIncrements('id_pengajuan');
            $table->unsignedInteger('id_user');
            $table->unsignedInteger('id_shift');
            $table->string('status', 12)->default('Menunggu');
            $table->unsignedInteger('id_admin')->nullable();
            $table->dateTime('waktu_diproses')->nullable();
            $table->timestamps();
            $table->index(['status', 'id_user']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tr_pengajuan_shift');
    }
};
