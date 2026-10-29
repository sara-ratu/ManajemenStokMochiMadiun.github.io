<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ms_shift', function (Blueprint $table): void {
            $table->time('jam_mulai')->nullable()->change();
            $table->time('jam_selesai')->nullable()->change();
        });

        $nextId = (int) DB::table('ms_shift')->max('id_shift') + 1;

        foreach (['Pagi', 'Siang', 'Oncalling'] as $namaShift) {
            if (DB::table('ms_shift')->where('nama_shift', $namaShift)->exists()) {
                continue;
            }

            if ($nextId > 4294967295) {
                throw new RuntimeException('ID shift telah mencapai batas maksimum database.');
            }

            DB::table('ms_shift')->insert([
                'id_shift' => $nextId++,
                'nama_shift' => $namaShift,
                'jam_mulai' => null,
                'jam_selesai' => null,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $addedShifts = DB::table('ms_shift')
            ->whereIn('nama_shift', ['Pagi', 'Siang', 'Oncalling'])
            ->whereNull('jam_mulai')
            ->whereNull('jam_selesai')
            ->get(['id_shift']);

        foreach ($addedShifts as $shift) {
            if (DB::table('ms_user')->where('id_shift', $shift->id_shift)->exists()) {
                throw new RuntimeException('Shift yang akan dihapus masih digunakan oleh data karyawan.');
            }
        }

        if ($addedShifts->isNotEmpty()) {
            DB::table('ms_shift')->whereIn('id_shift', $addedShifts->pluck('id_shift'))->delete();
        }

        Schema::table('ms_shift', function (Blueprint $table): void {
            $table->time('jam_mulai')->nullable(false)->change();
            $table->time('jam_selesai')->nullable(false)->change();
        });
    }
};
