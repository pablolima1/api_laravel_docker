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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('payer_id')
                ->constrained('customers')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('payee_id')
                ->constrained('customers')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->decimal('amount', 18, 2);
            $table->foreignId('transaction_status_id')
                ->constrained('transaction_statuses')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->timestamps();

            $table->index(['payer_id', 'created_at']);
            $table->index(['payee_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
