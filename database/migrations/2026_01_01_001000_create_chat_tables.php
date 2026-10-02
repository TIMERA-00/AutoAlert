<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            // Lets visitors chat before registering, and lets us keep sessions apart.
            $table->string('session_id', 64)->index();
            $table->string('status', 20)->default('OPEN');
            $table->string('locale', 5)->default('fr');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'updated_at']);
        });

        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_conversation_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20); // USER, ASSISTANT, SYSTEM
            $table->text('content');
            // Photos and other files produced by the assistant tool chain.
            $table->json('attachments')->nullable();
            // Vehicles the assistant cited, so the UI can render cards inline.
            $table->json('vehicle_ids')->nullable();
            $table->string('engine', 30)->nullable();
            $table->unsignedInteger('latency_ms')->nullable();
            $table->timestamps();

            $table->index(['chat_conversation_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('chat_conversations');
    }
};
