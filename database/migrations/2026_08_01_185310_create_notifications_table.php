<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('transaction_id')
                ->constrained('transactions')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('customer_id')
                ->constrained('customers')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->enum('channel', ['email', 'sms'])->default('email');
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->json('response_payload')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'attempts']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
