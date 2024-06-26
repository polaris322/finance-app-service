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
        Schema::create('project_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects', 'id')->onDelete('cascade');
            $table->string('name');
            $table->double('amount');
            $table->enum('payment_method', array_column(Enum\PaymentMethodEnum::cases(), 'value'))->default(Enum\PaymentMethodEnum::SCOTIBANK->value);
            $table->enum('status', array_column(Enum\StatusEnum::cases(), 'value'))->default(Enum\StatusEnum::FINISHED->value);
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_tasks');
    }
};
