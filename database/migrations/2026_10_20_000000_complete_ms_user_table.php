<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('ms_user', 'id') && ! Schema::hasColumn('ms_user', 'id_user')) {
            Schema::table('ms_user', function (Blueprint $table): void {
                $table->renameColumn('id', 'id_user');
            });
        }

        Schema::table('ms_user', function (Blueprint $table): void {
            if (! Schema::hasColumn('ms_user', 'id_user')) {
                $table->integer('id_user')->primary();
            }
            if (! Schema::hasColumn('ms_user', 'id_shift')) {
                $table->unsignedInteger('id_shift')->nullable();
            }
            if (! Schema::hasColumn('ms_user', 'nama_lengkap')) {
                $table->string('nama_lengkap', 30)->nullable();
            }
            if (! Schema::hasColumn('ms_user', 'email')) {
                $table->string('email', 45)->nullable();
            }
            if (! Schema::hasColumn('ms_user', 'password')) {
                $table->string('password', 60)->nullable();
            }
            if (! Schema::hasColumn('ms_user', 'no_telepon')) {
                $table->string('no_telepon', 15)->nullable();
            }
            if (! Schema::hasColumn('ms_user', 'alamat')) {
                $table->string('alamat', 100)->nullable();
            }
            if (! Schema::hasColumn('ms_user', 'status')) {
                $table->string('status', 12)->nullable();
            }
            if (! Schema::hasColumn('ms_user', 'role')) {
                $table->string('role', 20)->nullable();
            }
            if (! Schema::hasColumn('ms_user', 'remember_token')) {
                $table->rememberToken();
            }
        });
    }

    public function down(): void
    {
        Schema::table('ms_user', function (Blueprint $table): void {
            $table->dropColumn([
                'id_shift',
                'nama_lengkap',
                'email',
                'password',
                'no_telepon',
                'alamat',
                'status',
                'role',
                'remember_token',
            ]);
        });

        if (Schema::hasColumn('ms_user', 'id_user') && ! Schema::hasColumn('ms_user', 'id')) {
            Schema::table('ms_user', function (Blueprint $table): void {
                $table->renameColumn('id_user', 'id');
            });
        }
    }
};
