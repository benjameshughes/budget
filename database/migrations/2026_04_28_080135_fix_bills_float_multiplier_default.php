<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('bills_float_multiplier', 3, 1)->nullable()->default(0.0)->change();
        });

        DB::table('users')
            ->where('bills_float_multiplier', 1.0)
            ->update(['bills_float_multiplier' => 0.0]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('bills_float_multiplier', 3, 1)->nullable()->default(1.0)->change();
        });
    }
};
