<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('automation_rule_logs');
        Schema::dropIfExists('automation_rules');
        Schema::dropIfExists('bank_pots');

        if (Schema::hasColumn('transactions', 'connected_account_id')) {
            Schema::table('transactions', function (Blueprint $table) {
                if (Schema::getConnection()->getDriverName() !== 'sqlite') {
                    $table->dropForeign(['connected_account_id']);
                    $table->dropIndex('transactions_provider_external_id_unique');
                }
            });

            Schema::table('transactions', function (Blueprint $table) {
                $table->dropColumn(['external_id', 'provider', 'connected_account_id']);
            });
        }

        Schema::dropIfExists('connected_accounts');
    }

    public function down(): void {}
};
