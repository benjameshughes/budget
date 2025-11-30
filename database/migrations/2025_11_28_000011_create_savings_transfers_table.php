<?php

use App\Enums\TransferDirection;
use App\Models\SavingsAccount;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('savings_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(SavingsAccount::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Transaction::class)->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('direction'); // cast to TransferDirection
            $table->date('transfer_date')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('savings_transfers');
    }
};
