<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('login_activities', function (Blueprint $table) {
            $table->id();
            $table->string('email')->index();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->string('status', 20); // success, failed, locked
            $table->string('reason')->nullable();
            $table->timestamp('attempted_at')->useCurrent();
            $table->index(['email', 'attempted_at']);
            $table->index(['status', 'attempted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_activities');
    }
};
