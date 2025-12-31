<?php

declare(strict_types=1);

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
        Schema::create('penny_challenge_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penny_challenge_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('day_number');
            $table->timestamp('deposited_at')->nullable();
            $table->foreignId('transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->unique(['penny_challenge_id', 'day_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penny_challenge_days');
    }
};
