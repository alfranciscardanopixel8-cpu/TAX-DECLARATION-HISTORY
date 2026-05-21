<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->string('physical_copy_status')->default('On File')->index();
            $table->string('storage_location')->nullable()->index();
            $table->string('shelf_number')->nullable();
            $table->string('box_number')->nullable()->index();
            $table->string('folder_number')->nullable();
            $table->string('custodian')->nullable();
            $table->date('received_at')->nullable();
            $table->date('released_at')->nullable();
            $table->date('returned_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn([
                'physical_copy_status',
                'storage_location',
                'shelf_number',
                'box_number',
                'folder_number',
                'custodian',
                'received_at',
                'released_at',
                'returned_at',
            ]);
        });
    }
};
