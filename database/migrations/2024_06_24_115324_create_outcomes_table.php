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
        Schema::create('outcomes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users', 'id')->onDelete('cascade');;
            $table->string('name');
            $table->double('amount');
            $table->string('cuotas')->nullable();
            $table->enum('category', array_column(Enum\CategoryEnum::cases(), 'value'))->default(Enum\CategoryEnum::PRESTAMOS->value);
            $table->enum('type', array_column(Enum\TypeEnum::cases(), 'value'))->default(Enum\TypeEnum::FIXED->value);
            $table->enum('frequency', array_column(Enum\FrequencyEnum::cases(), 'value'))->default(Enum\FrequencyEnum::MONTHLY->value);
            $table->enum('payment_method', array_column(Enum\PaymentMethodEnum::cases(), 'value'))->default(Enum\PaymentMethodEnum::SCOTIBANK->value);
            $table->timestamp('start_date')->nullable();
            $table->longText('note')->nullable();
            $table->string('attachment')->nullable();
            $table->enum('status', array_column(Enum\StatusEnum::cases(), 'value'))->default(Enum\StatusEnum::FINISHED->value);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outcomes');
    }
};
