<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('owners', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index();
            $table->string('type')->default('Individual');
            $table->text('address')->nullable();
            $table->string('contact_number')->nullable();
            $table->string('email')->nullable();
            $table->string('tin')->nullable();
            $table->timestamps();
        });

        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->string('pin')->unique();
            $table->string('property_index_number')->nullable()->index();
            $table->string('lot_number')->index();
            $table->string('survey_number')->nullable()->index();
            $table->string('title_number')->nullable()->index();
            $table->string('barangay')->index();
            $table->string('municipality')->index();
            $table->string('province')->default('Province');
            $table->string('classification')->index();
            $table->string('actual_use')->nullable();
            $table->decimal('land_area', 14, 2)->nullable();
            $table->string('unit_of_measure')->default('sqm');
            $table->string('status')->default('Active')->index();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('tax_declarations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('owner_id')->constrained();
            $table->foreignId('previous_tax_declaration_id')->nullable()->constrained('tax_declarations')->nullOnDelete();
            $table->string('td_number')->unique();
            $table->string('arp_number')->nullable()->index();
            $table->unsignedSmallInteger('effectivity_year')->index();
            $table->string('classification');
            $table->string('actual_use')->nullable();
            $table->decimal('market_value', 14, 2)->default(0);
            $table->decimal('assessed_value', 14, 2)->default(0);
            $table->decimal('assessment_level', 5, 2)->nullable();
            $table->string('status')->default('Active')->index();
            $table->string('transaction_type')->default('Original');
            $table->text('memoranda')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tax_declaration_id')->nullable()->constrained()->nullOnDelete();
            $table->string('document_type')->index();
            $table->string('reference_number')->nullable()->index();
            $table->string('file_name');
            $table->string('file_path');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->date('issued_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action')->index();
            $table->text('description');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('documents');
        Schema::dropIfExists('tax_declarations');
        Schema::dropIfExists('properties');
        Schema::dropIfExists('owners');
    }
};
