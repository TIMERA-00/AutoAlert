<?php

use App\Enums\BodyType;
use App\Enums\FuelType;
use App\Enums\Transmission;
use App\Enums\VehicleStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_id')->nullable()->constrained('sources')->nullOnDelete();
            $table->string('source_url', 2048)->nullable();
            $table->string('brand', 60)->index();
            $table->string('model', 60)->index();
            $table->unsignedSmallInteger('year')->index();
            $table->unsignedBigInteger('price')->index();
            $table->unsignedBigInteger('mileage')->default(0)->index();
            $table->string('fuel', 20)->default(FuelType::Petrol->value);
            $table->string('transmission', 20)->default(Transmission::Manual->value);
            $table->string('body_type', 30)->default(BodyType::Sedan->value);
            $table->string('color', 40)->nullable();
            $table->string('location', 80)->nullable()->index();
            $table->text('description')->nullable();
            $table->string('status', 20)->default(VehicleStatus::Draft->value)->index();
            $table->string('reference', 20)->nullable()->unique();
            $table->string('slug', 180)->nullable()->unique();
            $table->timestamp('published_at')->nullable()->index();
            $table->unsignedBigInteger('view_count')->default(0);
            $table->unsignedBigInteger('favorite_count')->default(0);
            $table->timestamps();

            $table->index(['status', 'published_at']);
            $table->index(['brand', 'model', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
