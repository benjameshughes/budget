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
        Schema::table('users', function (Blueprint $table) {
            // Day of week: 0=Sunday, 1=Monday, ..., 4=Thursday, 6=Saturday
            // Default to Thursday (4), nullable so code handles default
            $table->unsignedTinyInteger('pay_day')->nullable()->default(4)->after('pay_cadence');

            // Weekly savings goal amount
            $table->decimal('weekly_savings_goal', 10, 2)->nullable()->after('weekly_budget');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['pay_day', 'weekly_savings_goal']);
        });
    }
};
