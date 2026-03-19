<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('connected_account_id')->constrained()->cascadeOnDelete();
            $table->string('external_id');
            $table->string('provider');
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('GBP');
            $table->string('description');
            $table->string('merchant_name')->nullable();
            $table->string('category')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('transacted_at');
            $table->timestamp('imported_at')->useCurrent();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_transactions');
    }
};
