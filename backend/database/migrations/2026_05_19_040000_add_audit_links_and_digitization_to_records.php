<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->foreignId('tax_declaration_id')->nullable()->after('property_id')->constrained()->nullOnDelete();
            $table->foreignId('document_id')->nullable()->after('tax_declaration_id')->constrained()->nullOnDelete();
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->timestamp('digitized_at')->nullable()->index();
            $table->foreignId('digitized_by_user_id')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('digitized_by_user_id');
            $table->dropColumn('digitized_at');
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('document_id');
            $table->dropConstrainedForeignId('tax_declaration_id');
        });
    }
};
