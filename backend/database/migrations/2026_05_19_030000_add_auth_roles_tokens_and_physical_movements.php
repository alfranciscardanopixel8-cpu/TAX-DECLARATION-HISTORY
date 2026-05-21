<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('viewer')->index();
            $table->string('status')->default('Active')->index();
        });

        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('physical_record_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('movement_type')->default('Location Update')->index();
            $table->string('from_status')->nullable();
            $table->string('to_status')->index();
            $table->string('from_location')->nullable();
            $table->string('to_location')->nullable()->index();
            $table->string('from_box_number')->nullable();
            $table->string('to_box_number')->nullable()->index();
            $table->string('from_folder_number')->nullable();
            $table->string('to_folder_number')->nullable();
            $table->string('released_to')->nullable();
            $table->string('custodian')->nullable();
            $table->date('movement_date')->nullable()->index();
            $table->date('expected_return_at')->nullable();
            $table->date('returned_at')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('physical_record_movements');
        Schema::dropIfExists('personal_access_tokens');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'status']);
        });
    }
};
