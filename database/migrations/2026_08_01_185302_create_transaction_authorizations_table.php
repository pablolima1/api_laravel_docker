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
        Schema::create('transaction_authorizations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('transaction_id')
                ->constrained('transactions')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->boolean('authorized');
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->json('response_payload')->nullable();
            $table->string('failure_reason')->nullable();
            $table->timestamp('requested_at');
            $table->timestamps();

            $table->index(['transaction_id', 'requested_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_authorizations');
    }
};
