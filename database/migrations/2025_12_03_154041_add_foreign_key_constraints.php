<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (! $this->foreignKeyExists('transactions', 'transactions_category_id_foreign')) {
                $table->foreign('category_id')
                    ->references('id')
                    ->on('categories')
                    ->nullOnDelete();
            }

            if (! $this->foreignKeyExists('transactions', 'transactions_credit_card_id_foreign')) {
                $table->foreign('credit_card_id')
                    ->references('id')
                    ->on('credit_cards')
                    ->nullOnDelete();
            }
        });

        Schema::table('bills', function (Blueprint $table) {
            if (! $this->foreignKeyExists('bills', 'bills_category_id_foreign')) {
                $table->foreign('category_id')
                    ->references('id')
                    ->on('categories')
                    ->nullOnDelete();
            }
        });
    }

    private function foreignKeyExists(string $table, string $keyName): bool
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            // SQLite doesn't track constraint names the same way, just skip the check
            // Foreign keys are re-created on fresh migrations anyway
            return false;
        }

        $database = DB::getDatabaseName();

        return DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', $database)
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $keyName)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropForeign(['credit_card_id']);
        });

        Schema::table('bills', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
        });
    }
};
