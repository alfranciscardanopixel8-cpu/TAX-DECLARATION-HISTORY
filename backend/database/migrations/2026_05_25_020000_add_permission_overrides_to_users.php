<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'permission_grants')) {
                $table->json('permission_grants')->nullable()->after('status');
            }
            if (! Schema::hasColumn('users', 'permission_denies')) {
                $table->json('permission_denies')->nullable()->after('permission_grants');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'permission_denies')) {
                $table->dropColumn('permission_denies');
            }
            if (Schema::hasColumn('users', 'permission_grants')) {
                $table->dropColumn('permission_grants');
            }
        });
    }
};
