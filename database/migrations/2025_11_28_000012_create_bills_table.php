<?php

use App\Enums\BillCadence;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('amount', 12, 2);
            $table->foreignIdFor(Category::class)->nullable();
            $table->string('cadence'); // cast to BillCadence
            $table->unsignedTinyInteger('day_of_month')->nullable();
            $table->unsignedTinyInteger('weekday')->nullable(); // 0-6 (Sun-Sat)
            $table->unsignedSmallInteger('interval_every')->default(1);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('next_due_date')->index();
            $table->boolean('autopay')->default(false);
            $table->boolean('active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bills');
    }
};

