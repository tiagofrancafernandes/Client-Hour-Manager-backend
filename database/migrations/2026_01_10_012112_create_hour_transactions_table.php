<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('hour_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // credit, debit, transfer_in, transfer_out
            $table->integer('minutes'); // Always positive, stored as integer
            $table->text('description')->nullable(); // Visible to client
            $table->text('internal_note')->nullable(); // Admin-only, not visible to client
            $table->timestamp('occurred_at'); // Separate from created_at
            $table->timestamps(); // Immutable after creation
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hour_transactions');
    }
};
