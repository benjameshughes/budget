<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('external_id')->nullable()->after('category_id');
            $table->string('provider')->nullable()->after('external_id');
            $table->foreignId('connected_account_id')->nullable()->constrained()->nullOnDelete()->after('provider');

            $table->unique(['provider', 'external_id']);
        });

        Schema::dropIfExists('bank_transactions');
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropUnique(['provider', 'external_id']);
            $table->dropConstrainedForeignId('connected_account_id');
            $table->dropColumn(['external_id', 'provider']);
        });
    }
};
