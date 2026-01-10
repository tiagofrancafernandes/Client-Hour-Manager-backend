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
        Schema::create('timers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('clients')->nullOnDelete();
            $table->string('state'); // running, paused, stopped, cancelled
            $table->text('description')->nullable();
            $table->boolean('is_hidden')->default(false);
            $table->timestamp('started_at');
            $table->timestamp('paused_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->integer('total_minutes')->default(0); // Accumulated time
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('timers');
    }
};
