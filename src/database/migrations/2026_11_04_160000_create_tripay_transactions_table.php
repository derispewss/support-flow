<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tripay_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 64)->unique();
            $table->string('merchant_ref', 64)->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->string('organization_name', 120);
            $table->string('plan', 20);
            $table->string('expected_agents', 50)->nullable();
            $table->string('payment_method', 30);
            $table->string('payment_name', 80)->nullable();
            $table->unsignedInteger('amount');
            $table->unsignedInteger('fee')->default(0);
            $table->unsignedInteger('total_amount');
            $table->string('pay_code', 100)->nullable();
            $table->text('qr_url')->nullable();
            $table->string('status', 20)->default('UNPAID');
            $table->timestamp('paid_at')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tripay_transactions');
    }
};
