<?php

use App\Enums\AlertFrequency;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 80);
            $table->string('brand', 60)->nullable();
            $table->string('model', 60)->nullable();
            $table->unsignedSmallInteger('min_year')->nullable();
            $table->unsignedSmallInteger('max_year')->nullable();
            $table->unsignedBigInteger('min_price')->nullable();
            $table->unsignedBigInteger('max_price')->nullable();
            $table->unsignedBigInteger('max_mileage')->nullable();
            $table->string('fuel', 20)->nullable();
            $table->string('transmission', 20)->nullable();
            $table->string('body_type', 30)->nullable();
            $table->string('location', 80)->nullable();
            $table->string('frequency', 20)->default(AlertFrequency::Immediate->value);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_notified_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
            $table->index(['is_active', 'frequency']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
