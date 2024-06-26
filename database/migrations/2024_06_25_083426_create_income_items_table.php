<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enum;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('income_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('income_id')->constrained('incomes', 'id')->onDelete('cascade');
            $table->double('amount');
            $table->timestamp('payment_date');
            $table->enum('status', array_column(Enum\StatusEnum::cases(), 'value'))->default(Enum\StatusEnum::FINISHED->value);
            $table->enum('type', array_column(Enum\TypeEnum::cases(), 'value'))->default(Enum\TypeEnum::DYNAMIC->value);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('income_items');
    }
};
