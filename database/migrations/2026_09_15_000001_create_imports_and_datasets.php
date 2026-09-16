<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('datasets', function (Blueprint $table) {
            $table->string('type', 8)->primary();
            $table->unsignedBigInteger('active_import_id')->nullable();
            $table->unsignedBigInteger('record_count')->default(0);
            $table->timestamp('published_at')->nullable();
        });
        Schema::create('imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->string('type', 8);
            $table->string('original_name');
            $table->string('private_path');
            $table->string('status', 20)->default('queued');
            $table->unsignedBigInteger('lines_read')->default(0);
            $table->unsignedBigInteger('lines_imported')->default(0);
            $table->unsignedBigInteger('lines_rejected')->default(0);
            $table->json('errors')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
            $table->index(['type', 'status']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('imports');
        Schema::dropIfExists('datasets');
    }
};
