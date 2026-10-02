<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('path', 255);
            $table->string('referrer', 500)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->string('session_id', 64)->nullable();
            $table->timestamps();

            $table->index('created_at');
            $table->index('vehicle_id');
        });

        Schema::create('search_queries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('term', 120)->index();
            $table->json('filters')->nullable();
            $table->unsignedInteger('results')->default(0);
            $table->timestamps();

            $table->index('created_at');
        });

        Schema::create('source_clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['vehicle_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('source_clicks');
        Schema::dropIfExists('search_queries');
        Schema::dropIfExists('page_views');
    }
};
